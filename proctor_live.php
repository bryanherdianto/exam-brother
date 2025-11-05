<?php
require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
$context = context_system::instance();
require_capability('local/myplugin:monitor', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/myplugin/proctor_live.php'));
$PAGE->set_title(get_string('liveproctor', 'local_myplugin'));
$PAGE->set_heading(get_string('liveproctor', 'local_myplugin'));
$PAGE->set_pagelayout('standard');

// Add CSS
$PAGE->requires->css('/local/myplugin/styles.css');

echo $OUTPUT->header();

global $DB;

// Get all active exam sessions
$activesessions = $DB->get_records('local_myplugin_sessions', ['status' => 'active'], 'starttime DESC');

?>

<div class="proctor-dashboard">
    <div class="dashboard-header">
        <h2><?php echo get_string('liveproctor', 'local_myplugin'); ?></h2>
        <div class="refresh-controls">
            <span class="last-update">Last updated: <span id="last-update-time">--:--:--</span></span>
            <button class="btn btn-primary" id="refresh-btn">Refresh Now</button>
            <label class="auto-refresh-toggle">
                <input type="checkbox" id="auto-refresh" checked> Auto-refresh (5s)
            </label>
        </div>
    </div>

    <?php if (empty($activesessions)): ?>
        <div class="alert alert-info">
            <p><?php echo get_string('noactivesessions', 'local_myplugin'); ?></p>
        </div>
    <?php else: ?>
        <div class="active-sessions-grid" id="active-sessions">
            <?php foreach ($activesessions as $session): 
                $user = $DB->get_record('user', ['id' => $session->userid]);
                $alertcount = $DB->count_records('local_myplugin_alerts', ['sessionid' => $session->id]);
                $recentalerts = $DB->get_records('local_myplugin_alerts', 
                    ['sessionid' => $session->id], 
                    'timecreated DESC', 
                    '*', 
                    0, 
                    5
                );
            ?>
                <div class="session-card" data-sessionid="<?php echo $session->id; ?>">
                    <div class="session-header">
                        <div class="student-info">
                            <img src="<?php echo new moodle_url('/user/pix.php/'.$session->userid.'/f1.jpg'); ?>" 
                                 alt="<?php echo fullname($user); ?>" 
                                 class="student-avatar">
                            <div>
                                <h4><?php echo fullname($user); ?></h4>
                                <p class="exam-name"><?php echo $session->examname; ?></p>
                            </div>
                        </div>
                        <div class="session-status active">
                            <span class="status-dot"></span> Active
                        </div>
                    </div>

                    <div class="session-details">
                        <div class="detail-item">
                            <span class="label">Start Time:</span>
                            <span class="value"><?php echo userdate($session->starttime, '%H:%M:%S'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Duration:</span>
                            <span class="value duration" data-starttime="<?php echo $session->starttime; ?>">--:--</span>
                        </div>
                        <div class="detail-item alert-count">
                            <span class="label">Alerts:</span>
                            <span class="value badge <?php echo $alertcount > 5 ? 'badge-danger' : ($alertcount > 0 ? 'badge-warning' : 'badge-success'); ?>">
                                <?php echo $alertcount; ?>
                            </span>
                        </div>
                    </div>

                    <div class="recent-alerts">
                        <h5>Recent Alerts</h5>
                        <?php if (empty($recentalerts)): ?>
                            <p class="no-alerts-text">No alerts</p>
                        <?php else: ?>
                            <div class="alert-list">
                                <?php foreach ($recentalerts as $alert): ?>
                                    <div class="alert-item">
                                        <span class="alert-icon">⚠️</span>
                                        <div class="alert-content">
                                            <span class="alert-type"><?php echo ucwords(str_replace('_', ' ', $alert->alerttype)); ?></span>
                                            <span class="alert-time"><?php echo userdate($alert->timecreated, '%H:%M:%S'); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="session-actions">
                        <button class="btn btn-sm btn-info view-details-btn" 
                                data-sessionid="<?php echo $session->id; ?>">
                            View Details
                        </button>
                        <button class="btn btn-sm btn-danger end-session-btn" 
                                data-sessionid="<?php echo $session->id; ?>">
                            End Session
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Real-time Alerts Section -->
    <div class="realtime-alerts-section">
        <h3><?php echo get_string('realtimealerts', 'local_myplugin'); ?></h3>
        <div class="alert-feed" id="alert-feed">
            <p class="no-alerts-text">Waiting for alerts...</p>
        </div>
    </div>
</div>

<script>
let autoRefreshInterval;
let isAutoRefresh = true;

// Update durations every second
setInterval(updateDurations, 1000);

function updateDurations() {
    document.querySelectorAll('.duration').forEach(function(elem) {
        const startTime = parseInt(elem.dataset.starttime);
        const now = Math.floor(Date.now() / 1000);
        const duration = now - startTime;
        
        const hours = Math.floor(duration / 3600);
        const minutes = Math.floor((duration % 3600) / 60);
        const seconds = duration % 60;
        
        elem.textContent = 
            (hours > 0 ? hours + ':' : '') +
            String(minutes).padStart(2, '0') + ':' + 
            String(seconds).padStart(2, '0');
    });
}

// Auto-refresh functionality
function refreshDashboard() {
    fetch(window.location.href)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newSessions = doc.querySelector('#active-sessions');
            const oldSessions = document.querySelector('#active-sessions');
            
            if (newSessions && oldSessions) {
                oldSessions.innerHTML = newSessions.innerHTML;
                setupEventListeners();
            }
            
            document.getElementById('last-update-time').textContent = 
                new Date().toLocaleTimeString();
        })
        .catch(error => {
            console.error('Error refreshing dashboard:', error);
        });
    
    // Fetch new alerts
    fetchRecentAlerts();
}

function fetchRecentAlerts() {
    // In production, this would call an AJAX endpoint
    // For now, we'll skip the implementation
}

// Setup auto-refresh
function startAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
    autoRefreshInterval = setInterval(refreshDashboard, 5000); // Every 5 seconds
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
}

// Event listeners
function setupEventListeners() {
    // View details buttons
    document.querySelectorAll('.view-details-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const sessionId = this.dataset.sessionid;
            window.location.href = '<?php echo $CFG->wwwroot; ?>/local/myplugin/proctor_dashboard.php?sessionid=' + sessionId;
        });
    });

    // End session buttons
    document.querySelectorAll('.end-session-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const sessionId = this.dataset.sessionid;
            if (confirm('Are you sure you want to end this exam session?')) {
                endSession(sessionId);
            }
        });
    });
}

function endSession(sessionId) {
    // In production, this would call an AJAX endpoint
    fetch('<?php echo $CFG->wwwroot; ?>/local/myplugin/ajax/end_session.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ sessionid: sessionId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            refreshDashboard();
        }
    })
    .catch(error => {
        console.error('Error ending session:', error);
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    updateDurations();
    
    // Refresh button
    document.getElementById('refresh-btn').addEventListener('click', refreshDashboard);
    
    // Auto-refresh toggle
    document.getElementById('auto-refresh').addEventListener('change', function() {
        isAutoRefresh = this.checked;
        if (isAutoRefresh) {
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    });
    
    // Start auto-refresh
    if (isAutoRefresh) {
        startAutoRefresh();
    }
});
</script>

<?php
echo $OUTPUT->footer();
?>
