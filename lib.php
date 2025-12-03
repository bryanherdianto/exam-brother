<?php

defined('MOODLE_INTERNAL') || die();

function local_myplugin_extend_navigation_course($navigation, $course, $context)
{
    if (has_capability('local/myplugin:monitor', $context)) {

        $url = new moodle_url('/local/myplugin/index.php', ['id' => $course->id]);

        $node = navigation_node::create(
            get_string('pluginname', 'local_myplugin'),
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            'local_myplugin',
            new pix_icon('i/customfield', '')
        );

        $navigation->add_node($node);
    }
}

function local_myplugin_before_footer()
{
    global $PAGE, $USER, $CFG, $DB;

    if ($PAGE->pagetype === 'mod-quiz-review') {
        // Find any active sessions for this user and close them
        $activesessions = $DB->get_records('local_myplugin_sessions', [
            'userid' => $USER->id,
            'status' => 'active'
        ]);

        foreach ($activesessions as $activesession) {
            $activesession->status = 'completed';
            $activesession->endtime = time();
            $DB->update_record('local_myplugin_sessions', $activesession);
        }
        return; // Stop here, monitoring is done
    }

    // ONLY RUN ON QUIZ ATTEMPT PAGES
    if ($PAGE->pagetype !== 'mod-quiz-attempt') {
        return;
    }

    // Get Quiz/Attempt Details
    $attemptid = required_param('attempt', PARAM_INT);

    $activesession = $DB->get_record('local_myplugin_sessions', [
        'userid' => $USER->id,
        'status' => 'active'
    ]);

    if (!$activesession) {
        // Create new exam session
        $session = new stdClass();
        $session->userid = $USER->id;
        // Try to get quiz name if possible, otherwise generic
        $quizname = 'Quiz Attempt ' . $attemptid;
        if ($cm = $PAGE->cm) {
            $quiz = $DB->get_record('quiz', ['id' => $cm->instance]);
            if ($quiz) {
                $quizname = $quiz->name;
            }
        }

        $session->examname = $quizname;
        $session->starttime = time();
        $session->status = 'active';
        $session->timecreated = time();
        $session->timemodified = time();
        $sessionid = $DB->insert_record('local_myplugin_sessions', $session);
    } else {
        $sessionid = $activesession->id;
    }

    // START OUTPUT BUFFERING
    ob_start();
?>

    <style>
        #proctor-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 320px;
            background: white;
            border: 1px solid #ccc;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            z-index: 100001;
            /* Higher than blocker */
            font-family: sans-serif;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        #startup-blocker {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: white;
            z-index: 100000;
            /* Below widget, above content */
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            text-align: center;
        }

        .quiz-locked-content {
            filter: blur(15px) !important;
            pointer-events: none !important;
            user-select: none !important;
            opacity: 0.1 !important;
            overflow: hidden !important;
            height: 100vh !important;
        }

        #proctor-header {
            background: #333;
            color: white;
            padding: 10px;
            font-size: 14px;
            cursor: move;
            display: flex;
            justify-content: space-between;
            user-select: none;
        }

        #proctor-body {
            padding: 10px;
            text-align: center;
        }

        .video-container {
            position: relative;
            width: 100%;
            height: 200px;
            background: #000;
            margin-bottom: 5px;
        }

        #video-feed,
        #canvas-output {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Cheating Overlay (Full Screen Alert) */
        .cheating-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 0, 0, 0.2);
            z-index: 100000;
            pointer-events: none;
            border: 10px solid red;
        }

        .cheating-message {
            position: absolute;
            top: 10%;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            padding: 20px;
            border: 2px solid red;
            font-weight: bold;
            font-size: 20px;
            color: red;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .status-dot {
            height: 10px;
            width: 10px;
            background-color: #bbb;
            border-radius: 50%;
            display: inline-block;
        }

        .status-dot.active {
            background-color: #28a745;
        }
    </style>

    <!-- The Blocker Element -->
    <div id="startup-blocker">
        <h1>⚠️ Exam Paused</h1>
        <p>You must start your camera to view the exam questions.</p>
        <p>Please click "Start Camera" in the widget.</p>
    </div>

    <div id="cheating-overlay" class="cheating-overlay">
        <div class="cheating-message">⚠️ <span id="alert-text">WARNING</span></div>
    </div>

    <div id="proctor-widget">
        <div id="proctor-header">
            <span>Exam Brother Monitor</span>
            <span id="monitoring-status"><span class="status-dot"></span> Offline</span>
        </div>
        <div id="proctor-body">
            <div class="video-container">
                <video id="video-feed" autoplay playsinline style="display:none;"></video>
                <canvas id="canvas-output"></canvas>
            </div>
            <button id="start-camera-btn" class="btn btn-sm btn-success">Start Camera</button>
            <div id="camera-status" class="small text-muted mt-1">Waiting for permission...</div>
        </div>
    </div>

    <script>
        // Pass PHP variables to JS
        window.sesskey = '<?php echo sesskey(); ?>';
        window.wwwroot = '<?php echo $CFG->wwwroot; ?>';
        window.userId = <?php echo $USER->id; ?>;
        window.examSessionId = <?php echo $sessionid; ?>;
        window.attemptId = <?php echo $attemptid; ?>;
    </script>

    <script>
        // --- DRAGGABLE WIDGET LOGIC ---
        document.addEventListener('DOMContentLoaded', function() {

            // Scope the force-submit flag to this specific attempt ID
            const STORAGE_KEY = 'local_myplugin_force_submit_' + window.attemptId;

            // AUTO-SUBMIT SEQUENCE HANDLER (Runs on page load)
            // If we flagged a violation on the previous page, continue the submission process
            if (sessionStorage.getItem(STORAGE_KEY) === '1') {
                const overlay = document.getElementById("cheating-overlay");
                const alertText = document.getElementById("alert-text");

                // Lock screen immediately
                if (overlay && alertText) {
                    overlay.style.display = "block";
                    alertText.innerText = "MAX VIOLATIONS. FINALIZING SUBMISSION...";
                }

                // We need to re-trigger the submission because it was interrupted.
                if (document.body.id === 'page-mod-quiz-attempt') {
                    console.log("Resuming forced submission on attempt page...");
                    const moodleForm = document.getElementById('responseform');
                    if (moodleForm) {
                        // Re-inject inputs and submit
                        if (!moodleForm.querySelector('input[name="finishattempt"]')) {
                            let finishInput = document.createElement('input');
                            finishInput.type = 'hidden';
                            finishInput.name = 'finishattempt';
                            finishInput.value = '1';
                            moodleForm.appendChild(finishInput);
                        }
                        if (!moodleForm.querySelector('input[name="timeup"]')) {
                            let timeupInput = document.createElement('input');
                            timeupInput.type = 'hidden';
                            timeupInput.name = 'timeup';
                            timeupInput.value = '1';
                            moodleForm.appendChild(timeupInput);
                        }
                        moodleForm.submit();
                    }
                }

                // We are on the Summary Page, then Click "Submit all and finish"
                if (document.body.id === 'page-mod-quiz-summary') {
                    console.log("Auto-submitting summary page...");
                    // Find the form that processes the attempt (usually action contains processattempt.php)
                    const summaryForm = document.querySelector('form[action*="processattempt.php"]');
                    if (summaryForm) {
                        // Ensure we are sending the finish signal
                        if (!summaryForm.querySelector('input[name="finishattempt"]')) {
                            let input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'finishattempt';
                            input.value = '1';
                            summaryForm.appendChild(input);
                        }
                        summaryForm.submit();
                    }
                }

                // We reached the Review Page -> Done
                if (document.body.id === 'page-mod-quiz-review') {
                    sessionStorage.removeItem(STORAGE_KEY);
                    // Clear the tab switch counter for this attempt
                    sessionStorage.removeItem('local_myplugin_tab_switches_' + window.attemptId);

                    if (overlay) overlay.style.display = "none";
                    alert("Your exam was automatically submitted due to security violations.");
                }
            }

            const widget = document.getElementById('proctor-widget');
            const header = document.getElementById('proctor-header');

            let isDragging = false;
            let currentX;
            let currentY;
            let initialX;
            let initialY;
            let xOffset = 0;
            let yOffset = 0;

            header.addEventListener("mousedown", dragStart);
            document.addEventListener("mouseup", dragEnd);
            document.addEventListener("mousemove", drag);

            function dragStart(e) {
                initialX = e.clientX - xOffset;
                initialY = e.clientY - yOffset;

                if (e.target === header || header.contains(e.target)) {
                    isDragging = true;
                }
            }

            function dragEnd(e) {
                initialX = currentX;
                initialY = currentY;
                isDragging = false;
            }

            function drag(e) {
                if (isDragging) {
                    e.preventDefault();
                    currentX = e.clientX - initialX;
                    currentY = e.clientY - initialY;

                    xOffset = currentX;
                    yOffset = currentY;

                    setTranslate(currentX, currentY, widget);
                }
            }

            function setTranslate(xPos, yPos, el) {
                el.style.transform = "translate3d(" + xPos + "px, " + yPos + "px, 0)";
            }
        });
    </script>

    <script type="module">
        import {
            FilesetResolver,
            FaceLandmarker
        } from "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.3";

        const video = document.getElementById("video-feed");
        const canvasElement = document.getElementById("canvas-output");
        const canvasCtx = canvasElement.getContext("2d");
        const startBtn = document.getElementById("start-camera-btn");
        const statusDiv = document.getElementById("camera-status");
        const overlay = document.getElementById("cheating-overlay");
        const alertText = document.getElementById("alert-text");
        const monitorStatus = document.getElementById("monitoring-status");
        const blocker = document.getElementById("startup-blocker"); // NEW

        // Robust Locking Mechanism
        let isCameraActive = false;
        const contentArea = document.getElementById('region-main') || document.body;

        // Initial Lock
        contentArea.classList.add('quiz-locked-content');

        // Anti-Tamper Loop
        setInterval(() => {
            if (!isCameraActive) {
                // A. Re-apply CSS class if removed
                if (!contentArea.classList.contains('quiz-locked-content')) {
                    contentArea.classList.add('quiz-locked-content');
                    console.log("Tampering detected: Re-locking content.");
                }

                // B. Check if blocker element was deleted from DOM
                const checkBlocker = document.getElementById("startup-blocker");
                if (!checkBlocker) {
                    alert("Security Violation: Do not remove the overlay. The page will now reload.");
                    window.location.reload();
                }

                // C. Force display block in case they set display:none
                if (checkBlocker && checkBlocker.style.display === 'none') {
                    checkBlocker.style.display = 'flex';
                }
            }
        }, 500);

        let faceLandmarker = undefined;
        let lastVideoTime = -1;
        let results = undefined;
        // Load from storage
        const TAB_COUNT_KEY = 'local_myplugin_tab_switches_' + window.attemptId;
        let tabSwitchCount = parseInt(sessionStorage.getItem(TAB_COUNT_KEY) || '0');
        const MAX_SWITCHES = 3;

        // Initialize AI
        async function createFaceLandmarker() {
            statusDiv.innerText = "Loading AI...";
            try {
                const vision = await FilesetResolver.forVisionTasks("https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.3/wasm");
                faceLandmarker = await FaceLandmarker.createFromOptions(vision, {
                    baseOptions: {
                        modelAssetPath: `https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task`,
                        delegate: "GPU"
                    },
                    outputFaceBlendshapes: true,
                    runningMode: "VIDEO",
                    numFaces: 1
                });
                statusDiv.innerText = "Ready to start.";
                startBtn.disabled = false;
            } catch (error) {
                statusDiv.innerText = "AI Error.";
                console.error(error);
            }
        }
        createFaceLandmarker();

        // Start Camera
        startBtn.addEventListener("click", () => {
            if (!faceLandmarker) return;
            navigator.mediaDevices.getUserMedia({
                video: true
            }).then((stream) => {
                video.srcObject = stream;
                video.addEventListener("loadeddata", predictWebcam);
                startBtn.style.display = "none";
                monitorStatus.innerHTML = '<span class="status-dot active"></span> Active';
                statusDiv.innerText = "";
                isCameraActive = true;
                blocker.style.display = "none";
                contentArea.classList.remove('quiz-locked-content');
            });
        });

        // AI Prediction Loop
        async function predictWebcam() {
            if (isSubmitting) return;

            // Resize canvas to match video
            if (canvasElement.width !== video.videoWidth) {
                canvasElement.width = video.videoWidth;
                canvasElement.height = video.videoHeight;
            }

            let startTimeMs = performance.now();
            if (lastVideoTime !== video.currentTime) {
                lastVideoTime = video.currentTime;
                results = faceLandmarker.detectForVideo(video, startTimeMs);
            }

            canvasCtx.clearRect(0, 0, canvasElement.width, canvasElement.height);

            // Draw Mirrored Video
            canvasCtx.save();
            canvasCtx.scale(-1, 1);
            canvasCtx.translate(-canvasElement.width, 0);
            canvasCtx.drawImage(video, 0, 0, canvasElement.width, canvasElement.height);

            // Draw Dots (Visual Feedback)
            if (results && results.faceLandmarks && results.faceLandmarks.length > 0) {
                const landmarks = results.faceLandmarks[0];
                canvasCtx.fillStyle = "#00FF00";
                // Draw nose tip
                const pt = landmarks[1];
                canvasCtx.beginPath();
                canvasCtx.arc(pt.x * canvasElement.width, pt.y * canvasElement.height, 3, 0, 2 * Math.PI);
                canvasCtx.fill();
            }
            canvasCtx.restore();

            // CHEATING LOGIC
            if (results && results.faceLandmarks && results.faceLandmarks.length > 0) {
                const landmarks = results.faceLandmarks[0];
                const nose = landmarks[1].x;
                const leftCheek = landmarks[454].x;
                const rightCheek = landmarks[234].x;

                const distToRight = Math.abs(nose - rightCheek);
                const distToLeft = Math.abs(nose - leftCheek);

                if (distToRight < distToLeft * 0.1) triggerAlert("LOOKING RIGHT");
                else if (distToLeft < distToRight * 0.1) triggerAlert("LOOKING LEFT");
            } else if (results && (!results.faceLandmarks || results.faceLandmarks.length === 0)) {
                triggerAlert("NO FACE DETECTED", "missing_face");
            }

            window.requestAnimationFrame(predictWebcam);
        }

        // Alert System
        let alertTimer = null;
        // Alert Cooldown
        let lastAlertTime = 0;
        const ALERT_COOLDOWN_MS = 500; // Only log one alert every half second

        function triggerAlert(msg, alertType = 'head_pose') {
            alertText.innerText = msg;
            overlay.style.display = "block";

            if (alertTimer) clearTimeout(alertTimer);
            alertTimer = setTimeout(() => {
                overlay.style.display = "none";
            }, 1000);

            // Send to Server (with cooldown)
            const now = Date.now();
            if (now - lastAlertTime > ALERT_COOLDOWN_MS) {
                lastAlertTime = now;
                logAlertToServer(msg, alertType);
            }
        }

        function logAlertToServer(message, alertType = 'head_pose') {
            if (!window.examSessionId) {
                console.error('No session ID available');
                return;
            }
            // Capture Screenshot
            let screenshot = '';
            if (alertType !== 'tab_switch') {
                screenshot = canvasElement.toDataURL('image/jpeg', 0.7);
            }

            const formData = new FormData();
            formData.append('action', 'log_alert');
            formData.append('sesskey', window.sesskey);
            formData.append('sessionid', window.examSessionId);
            formData.append('userid', window.userId);
            formData.append('alerttype', alertType);
            formData.append('description', message);
            formData.append('screenshot', screenshot);
            formData.append('severity', 1);

            fetch(window.wwwroot + '/local/myplugin/ajax/api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error('Invalid JSON response: ' + text);
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        console.log('Alert logged:', data);
                    } else {
                        console.error('Server returned error:', data.message);
                    }
                })
                .catch(err => console.error('Error logging alert:', err));
        }

        // TAB SWITCH & AUTO SUBMIT
        let isSubmitting = false; // Flag to prevent infinite loops

        document.addEventListener("visibilitychange", () => {
            // Don't count if we are already in the process of auto-submitting
            if (isSubmitting) return;

            // Check if we are already in forced submission mode from previous page
            const STORAGE_KEY = 'local_myplugin_force_submit_' + window.attemptId;
            if (sessionStorage.getItem(STORAGE_KEY) === '1') return;

            if (document.hidden) {
                tabSwitchCount++;
                // Save the new count to storage immediately
                sessionStorage.setItem(TAB_COUNT_KEY, tabSwitchCount);

                const remaining = MAX_SWITCHES - tabSwitchCount;
                triggerAlert(`TAB SWITCH DETECTED (${tabSwitchCount}/${MAX_SWITCHES})`, "tab_switch");

                if (tabSwitchCount >= MAX_SWITCHES) {
                    isSubmitting = true; // Stop local counting
                    statusDiv.style.color = 'red';
                    statusDiv.innerText = "VIOLATION! SUBMITTING...";

                    // Tell the next page load that we are forcing a submit
                    sessionStorage.setItem(STORAGE_KEY, '1');

                    // Stop AI
                    if (video.srcObject) {
                        video.srcObject.getTracks().forEach(track => track.stop());
                    }

                    // === MOODLE SPECIFIC SUBMISSION LOGIC ===
                    const moodleForm = document.getElementById('responseform');

                    if (moodleForm) {
                        alert("Max tab violations reached. Your exam is being auto-submitted.");

                        // Inject 'finishattempt' to force Moodle to go to Summary page
                        let finishInput = document.createElement('input');
                        finishInput.type = 'hidden';
                        finishInput.name = 'finishattempt';
                        finishInput.value = '1';
                        moodleForm.appendChild(finishInput);

                        // Inject 'timeup' to simulate timer expiration (forces submit in some Moodle versions)
                        let timeupInput = document.createElement('input');
                        timeupInput.type = 'hidden';
                        timeupInput.name = 'timeup';
                        timeupInput.value = '1';
                        moodleForm.appendChild(timeupInput);

                        moodleForm.submit();
                    } else {
                        console.error("Could not find Moodle quiz form!");
                        // Fallback: Try to find the 'Finish attempt' button link
                        const finishBtn = document.querySelector('.mod_quiz-next-nav');
                        if (finishBtn) {
                            finishBtn.click();
                        } else {
                            window.location.reload();
                        }
                    }
                }
            }
        });
    </script>

<?php
    // Output the buffer
    $output = ob_get_clean();
    echo $output;
}
