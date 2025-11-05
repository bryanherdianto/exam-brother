/**
 * Face Detection and Head Pose Monitoring using MediaPipe
 * Detects when student looks left or right
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, ajax, notification) {
    'use strict';

    let video, canvas, ctx;
    let faceLandmarker = null;
    let isMonitoring = false;
    let alertCount = 0;
    let lastAlertTime = 0;
    const ALERT_COOLDOWN = 3000; // 3 seconds between alerts

    // Head pose thresholds
    const LOOKING_LEFT_THRESHOLD = -25;
    const LOOKING_RIGHT_THRESHOLD = 25;
    
    return {
        init: function() {
            console.log('Face detection initializing...');
            video = document.getElementById('video-feed');
            canvas = document.getElementById('canvas-output');
            ctx = canvas ? canvas.getContext('2d') : null;

            if (!video || !canvas) {
                console.error('Video or canvas element not found');
                return;
            }

            this.setupEventListeners();
            this.loadMediaPipe();
        },

        loadMediaPipe: function() {
            const self = this;
            
            console.log('Loading MediaPipe from CDN...');
            
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.8/vision_bundle.js';
            script.crossOrigin = 'anonymous';
            
            script.onload = function() {
                console.log('✅ MediaPipe script loaded');
                setTimeout(function() {
                    if (typeof window.FaceLandmarker !== 'undefined') {
                        console.log('✅ MediaPipe ready');
                        self.initializeFaceLandmarker();
                    } else {
                        console.warn('⚠️ MediaPipe not available, using basic mode');
                        faceLandmarker = 'basic';
                    }
                }, 1000);
            };
            
            script.onerror = function() {
                console.warn('⚠️ MediaPipe failed to load, continuing in basic mode');
                faceLandmarker = 'basic';
            };
            
            document.head.appendChild(script);
        },

        initializeFaceLandmarker: async function() {
            try {
                console.log('Initializing Face Landmarker...');
                
                const { FaceLandmarker, FilesetResolver } = window;
                
                const filesetResolver = await FilesetResolver.forVisionTasks(
                    "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.8/wasm"
                );
                
                faceLandmarker = await FaceLandmarker.createFromOptions(filesetResolver, {
                    baseOptions: {
                        modelAssetPath: 'https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task',
                        delegate: "GPU"
                    },
                    outputFaceBlendshapes: false,
                    outputFacialTransformationMatrixes: true,
                    runningMode: "VIDEO",
                    numFaces: 1
                });
                
                console.log('✅ Face Landmarker initialized!');
                
            } catch (error) {
                console.error('❌ Face Landmarker error:', error);
                faceLandmarker = 'basic';
            }
        },

        setupEventListeners: function() {
            const self = this;
            
            console.log('Setting up event listeners...');
            
            const startBtn = document.getElementById('start-camera-btn');
            if (startBtn) {
                startBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('🎥 Start camera clicked');
                    self.startCamera();
                });
                console.log('✅ Start camera button listener attached');
            } else {
                console.error('❌ start-camera-btn not found');
            }

            const endBtn = document.getElementById('end-exam-btn');
            if (endBtn) {
                endBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    self.endExam();
                });
            }

            const testAlertBtn = document.getElementById('test-alert-btn');
            if (testAlertBtn) {
                testAlertBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    self.triggerAlert('test_alert', 'Manual test');
                });
            }

            const testScreenshotBtn = document.getElementById('test-screenshot-btn');
            if (testScreenshotBtn) {
                testScreenshotBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (video.srcObject) {
                        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                        self.triggerAlert('manual_screenshot', 'Manual screenshot test');
                    } else {
                        console.log('Camera not started yet');
                    }
                });
            }
        },

        startCamera: async function() {
            const self = this;
            
            console.log('🎥 Requesting camera access...');
            
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { width: 640, height: 480 },
                    audio: false
                });

                console.log('✅ Camera access granted');
                video.srcObject = stream;
                
                video.onloadedmetadata = function() {
                    video.play();
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    console.log('📹 Video dimensions:', video.videoWidth, 'x', video.videoHeight);
                    
                    // Update UI
                    const status = document.getElementById('camera-status');
                    if (status) {
                        status.textContent = '✅ Camera Active';
                        status.classList.remove('alert-danger');
                        status.classList.add('alert-success');
                    }
                    
                    const btn = document.getElementById('start-camera-btn');
                    if (btn) {
                        btn.disabled = true;
                        btn.textContent = '✅ Camera Active';
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-secondary');
                    }
                    
                    const examContent = document.getElementById('exam-content-area');
                    if (examContent) {
                        examContent.style.display = 'block';
                    }
                    
                    const monitoringStatus = document.getElementById('monitoring-status');
                    if (monitoringStatus) {
                        monitoringStatus.innerHTML = '<span class="status-dot active"></span> Active';
                    }
                    
                    self.startFaceDetection();
                };

            } catch (error) {
                console.error('❌ Camera error:', error.name, error.message);
                
                // Show friendly error message
                const alertBox = document.getElementById('alert-box');
                if (alertBox) {
                    alertBox.className = 'alert alert-danger';
                    alertBox.innerHTML = '<strong>Camera Error:</strong> ' + error.message + 
                        '<br><small>Please allow camera access and refresh the page.</small>';
                    alertBox.classList.remove('d-none');
                }
            }
        },

        startFaceDetection: function() {
            const self = this;
            isMonitoring = true;
            
            console.log('👁️ Face detection started');
            
            let lastVideoTime = -1;
            let frameCount = 0;
            
            const detectLoop = function() {
                if (!isMonitoring) {
                    console.log('Monitoring stopped');
                    return;
                }
                
                frameCount++;
                
                if (video.currentTime !== lastVideoTime) {
                    lastVideoTime = video.currentTime;
                    
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    
                    if (faceLandmarker && faceLandmarker !== 'basic') {
                        try {
                            const results = faceLandmarker.detectForVideo(video, performance.now());
                            
                            if (results.faceLandmarks && results.faceLandmarks.length > 0) {
                                self.drawFaceLandmarks(results.faceLandmarks[0]);
                                
                                if (results.facialTransformationMatrixes && results.facialTransformationMatrixes.length > 0) {
                                    const headPose = self.calculateHeadPose(results.facialTransformationMatrixes[0]);
                                    self.displayHeadPose(headPose);
                                    self.checkForCheating(headPose);
                                }
                            } else {
                                ctx.fillStyle = 'red';
                                ctx.font = '20px Arial';
                                ctx.fillText('⚠️ No face detected', 10, 30);
                            }
                        } catch (error) {
                            if (frameCount % 100 === 0) {
                                console.error('Detection error:', error);
                            }
                        }
                    } else {
                        ctx.fillStyle = '#ffc107';
                        ctx.font = '16px Arial';
                        ctx.fillText('📹 Camera Active (Basic Mode)', 10, 30);
                        ctx.fillText('Face detection loading...', 10, 55);
                    }
                }
                
                requestAnimationFrame(detectLoop);
            };
            
            detectLoop();
        },

        drawFaceLandmarks: function(landmarks) {
            ctx.fillStyle = 'rgba(0, 255, 255, 0.5)';
            for (let i = 0; i < landmarks.length; i += 5) {
                const point = landmarks[i];
                ctx.beginPath();
                ctx.arc(point.x * canvas.width, point.y * canvas.height, 2, 0, 2 * Math.PI);
                ctx.fill();
            }
        },

        calculateHeadPose: function(matrix) {
            const data = matrix.data;
            const yaw = Math.atan2(data[6], data[10]) * (180 / Math.PI);
            const pitch = Math.asin(-data[2]) * (180 / Math.PI);
            const roll = Math.atan2(data[1], data[0]) * (180 / Math.PI);
            return { yaw, pitch, roll };
        },

        displayHeadPose: function(headPose) {
            ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
            ctx.fillRect(5, 5, 200, 110);
            
            ctx.fillStyle = '#ffc107';
            ctx.font = '14px Arial';
            ctx.fillText('Yaw: ' + headPose.yaw.toFixed(1) + '°', 10, 25);
            ctx.fillText('Pitch: ' + headPose.pitch.toFixed(1) + '°', 10, 45);
            ctx.fillText('Roll: ' + headPose.roll.toFixed(1) + '°', 10, 65);
            
            let status = 'Looking: ';
            if (headPose.yaw < LOOKING_LEFT_THRESHOLD) {
                status += 'LEFT ⚠️';
                ctx.fillStyle = '#dc3545';
            } else if (headPose.yaw > LOOKING_RIGHT_THRESHOLD) {
                status += 'RIGHT ⚠️';
                ctx.fillStyle = '#dc3545';
            } else {
                status += 'FORWARD ✓';
                ctx.fillStyle = '#28a745';
            }
            
            ctx.font = 'bold 18px Arial';
            ctx.fillText(status, 10, 95);
        },

        checkForCheating: function(headPose) {
            const currentTime = Date.now();
            
            if (currentTime - lastAlertTime < ALERT_COOLDOWN) return;
            
            if (headPose.yaw < LOOKING_LEFT_THRESHOLD) {
                console.log('⚠️ ALERT: Looking left!', headPose.yaw);
                this.triggerAlert('looking_left', 'Student looked left (' + headPose.yaw.toFixed(1) + '°)');
                lastAlertTime = currentTime;
            } else if (headPose.yaw > LOOKING_RIGHT_THRESHOLD) {
                console.log('⚠️ ALERT: Looking right!', headPose.yaw);
                this.triggerAlert('looking_right', 'Student looked right (' + headPose.yaw.toFixed(1) + '°)');
                lastAlertTime = currentTime;
            }
        },

        triggerAlert: function(alertType, description) {
            alertCount++;
            console.log('🚨 Alert #' + alertCount + ':', alertType);

            const screenshot = canvas.toDataURL('image/jpeg', 0.8);
            console.log('📸 Screenshot captured:', screenshot.length, 'bytes');

            this.showAlertToStudent(description);
            this.logAlert(alertType, description, screenshot);
            this.addToAlertLog(alertType, description);
        },

        showAlertToStudent: function(message) {
            const alertBox = $('#cheating-alert');
            if (alertBox.length) {
                $('#alert-description').text(message);
                alertBox.fadeIn();
                setTimeout(function() { alertBox.fadeOut(); }, 3000);
            }
        },

        logAlert: function(alertType, description, screenshot) {
            const sessionId = $('#session-id').val();
            const userId = $('#user-id').val();
            
            console.log('📤 Logging alert to server...', {
                sessionId: sessionId,
                userId: userId,
                alertType: alertType
            });
            
            ajax.call([{
                methodname: 'local_myplugin_log_alert',
                args: {
                    sessionid: parseInt(sessionId),
                    userid: parseInt(userId),
                    alerttype: alertType,
                    description: description,
                    screenshot: screenshot,
                    severity: 2
                },
                done: function(response) {
                    console.log('✅ Alert logged:', response);
                },
                fail: function(error) {
                    console.error('❌ Failed to log alert:', error);
                }
            }]);
        },

        addToAlertLog: function(alertType, description) {
            const alertLog = $('#alert-log');
            if (alertLog.length) {
                const timestamp = new Date().toLocaleTimeString();
                const entry = '<div class="alert alert-warning mb-2">[' + timestamp + '] ' + 
                              alertType + ': ' + description + '</div>';
                alertLog.prepend(entry);
                
                $('#alert-count').text(alertCount);
            }
        },

        endExam: function() {
            if (confirm('Are you sure you want to end the exam and submit?')) {
                isMonitoring = false;
                
                if (video.srcObject) {
                    video.srcObject.getTracks().forEach(function(track) {
                        track.stop();
                    });
                }
                
                const sessionId = $('#session-id').val();
                
                ajax.call([{
                    methodname: 'local_myplugin_end_session',
                    args: { sessionid: parseInt(sessionId) },
                    done: function() {
                        window.location.href = M.cfg.wwwroot + '/local/myplugin/';
                    },
                    fail: notification.exception
                }]);
            }
        }
    };
});

require(['local_myplugin/face_detection'], function(faceDetection) {
    if (document.getElementById('video-feed')) {
        console.log('🚀 Initializing face detection module');
        faceDetection.init();
    }
});
