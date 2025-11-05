# Exam Monitor Plugin - Quick Reference

## 🎯 Project Overview

A complete Moodle plugin for online exam monitoring with real-time cheating detection using MediaPipe face detection technology.

## 📁 Complete File Structure

```txt
local/myplugin/
│
├── 📄 index.php                        # Main landing page with navigation
├── 📄 student_exam.php                 # Student exam interface with camera
├── 📄 proctor_live.php                 # Live monitoring dashboard
├── 📄 proctor_dashboard.php            # Post-exam reports & screenshots
├── 📄 version.php                      # Plugin metadata
├── 📄 styles.css                       # All CSS styles
├── 📄 README.md                        # Complete documentation
├── 📄 INSTALL.md                       # Installation guide
│
├── 📁 amd/                             # JavaScript modules
│   ├── src/
│   │   └── face_detection.js          # Source: MediaPipe integration
│   └── build/
│       └── face_detection.min.js      # Production: Minified version
│
├── 📁 classes/                         # PHP classes
│   └── external/                       # Web service APIs
│       ├── log_alert.php              # API: Log cheating alerts
│       ├── end_session.php            # API: End exam sessions
│       └── get_active_sessions.php    # API: Get active sessions
│
├── 📁 db/                              # Database definitions
│   ├── install.xml                    # Tables: sessions, alerts, screenshots
│   ├── access.php                     # Capabilities: takeexam, monitor, viewreports
│   └── services.php                   # Web services registration
│
└── 📁 lang/                            # Translations
    └── en/
        └── local_myplugin.php         # English language strings
```

## 🔑 Key Features Implemented

✅ **Student Exam Page** (`student_exam.php`)

- Camera monitoring with live video feed
- Real-time cheating detection
- On-screen alert notifications
- Alert history log
- Screenshot capture on detection

✅ **Live Proctor Dashboard** (`proctor_live.php`)

- View all active exam sessions
- Real-time alert notifications
- Auto-refresh every 5 seconds
- Student information and statistics
- Quick actions (view details, end session)

✅ **Post-Exam Dashboard** (`proctor_dashboard.php`)

- Detailed session reports
- Alert timeline view
- Screenshot gallery
- Session statistics
- Historical data browsing

✅ **Cheating Detection** (JavaScript)

- MediaPipe face detection
- Head pose tracking (yaw rotation)
- Looking left detection (<-15°)
- Looking right detection (>15°)
- Screenshot on alert
- 2-second cooldown between alerts

✅ **Backend APIs** (External Services)

- Log alert with screenshot
- End exam session
- Get active sessions
- AJAX-ready for real-time updates

## 🗄️ Database Schema

### Table: local_myplugin_sessions

```txt
id, userid, examname, starttime, endtime, status, timecreated, timemodified
```

### Table: local_myplugin_alerts

```txt
id, sessionid, userid, alerttype, description, severity, timecreated
```

### Table: local_myplugin_screenshots

```txt
id, alertid, sessionid, userid, imagedata, timecreated
```

## 👥 User Capabilities

| Role | Capability | Access |
|------|-----------|--------|
| Student | `local/myplugin:takeexam` | Take monitored exams |
| Teacher | `local/myplugin:monitor` | Live monitoring dashboard |
| Teacher | `local/myplugin:viewreports` | View reports & screenshots |

## 🚀 Quick Start

### Installation

1. Navigate to: Site Administration > Notifications
2. Click "Upgrade Moodle database now"
3. Plugin installed! ✓

### For Students

1. Go to `/local/myplugin/`
2. Click "Start Exam"
3. Enable camera
4. Take exam (monitored)

### For Teachers

1. **Live**: `/local/myplugin/proctor_live.php`
2. **Reports**: `/local/myplugin/proctor_dashboard.php`

## 🔧 Technology Stack

| Component | Technology |
|-----------|-----------|
| Backend | PHP 7.4+ (Moodle) |
| Frontend | JavaScript (AMD modules) |
| Face Detection | MediaPipe Tasks Vision v0.10.8 |
| Database | MySQL/PostgreSQL (Moodle DB) |
| UI Framework | Moodle + Custom CSS |
| AJAX | Moodle Web Services |

## 📊 Detection Parameters

```javascript
LOOKING_LEFT_THRESHOLD = -15°   // Trigger alert
LOOKING_RIGHT_THRESHOLD = 15°   // Trigger alert
ALERT_COOLDOWN = 2000ms        // Time between alerts
DETECTION_RATE = 100ms         // Check every 100ms (10 FPS)
```

## 🎨 Main Pages Overview

### 1. index.php - Dashboard

- Feature cards for students/teachers
- Active session count
- System information
- Recent activity table

### 2. student_exam.php - Exam Interface

- Two-column layout
- Left: Exam questions
- Right: Camera feed + alerts
- Status indicators
- End exam button

### 3. proctor_live.php - Live Monitoring

- Grid of session cards
- Auto-refresh toggle
- Duration counters
- Recent alerts per student
- Real-time updates

### 4. proctor_dashboard.php - Reports

- Session overview table
- Detailed timeline view
- Screenshot gallery
- Statistics cards
- Modal image viewer

## 🔐 Security Features

- ✅ Capability checks on all pages
- ✅ Context validation
- ✅ Input sanitization (PARAM_INT, PARAM_TEXT)
- ✅ CSRF protection (Moodle sesskey)
- ✅ Database API (SQL injection prevention)
- ✅ Base64 encoded screenshots

## 📱 Browser Requirements

| Browser | Min Version | Status |
|---------|-------------|--------|
| Chrome | 80+ | ✅ Recommended |
| Firefox | 75+ | ✅ Supported |
| Edge | 80+ | ✅ Supported |
| Safari | 13+ | ⚠️ Limited |

**Note**: HTTPS required for camera access

## 🎯 Cheating Detection Logic

```txt
1. Capture video frame (10 FPS)
2. Analyze head pose (MediaPipe)
3. Calculate yaw rotation
4. If yaw < -15° → Looking Left Alert
5. If yaw > 15° → Looking Right Alert
6. Capture screenshot
7. Save to database via AJAX
8. Show alert to student
9. Notify proctor dashboard
10. Wait 2 seconds (cooldown)
```

## 🔄 Real-time Flow

```txt
Student Exam Page
    ↓ (Camera active)
Face Detection (JS)
    ↓ (Cheating detected)
AJAX Call → Backend API
    ↓ (Save to DB)
Database (alerts + screenshots)
    ↑ (Auto-refresh / Poll)
Proctor Dashboard
    ↓ (Display)
Live Monitoring View
```

## 📝 API Endpoints

```php
// Log Alert
local_myplugin_log_alert(sessionid, userid, alerttype, description, screenshot, severity)

// End Session
local_myplugin_end_session(sessionid)

// Get Active Sessions
local_myplugin_get_active_sessions()
```

## 🎨 CSS Classes Reference

### Layout

- `.exam-monitor-container`
- `.exam-content` (grid layout)
- `.main-section` / `.sidebar-section`

### Components

- `.session-card`
- `.alert-item`
- `.timeline-item`
- `.gallery-item`

### Status

- `.status-dot.active` / `.inactive`
- `.badge-success` / `-warning` / `-danger`

## 🔮 Future Enhancements

- [ ] Moodle Quiz module integration
- [ ] Multiple camera views
- [ ] Screen sharing detection
- [ ] Audio monitoring
- [ ] AI behavior analysis
- [ ] PDF report export
- [ ] Email notifications
- [ ] Mobile app support
- [ ] Advanced analytics

## 📞 Support Checklist

When troubleshooting:

- [ ] Check Moodle error logs
- [ ] Check browser console (F12)
- [ ] Verify camera permissions
- [ ] Test MediaPipe CDN
- [ ] Check database tables exist
- [ ] Verify web services enabled
- [ ] Clear all caches
- [ ] Test in different browser

## 📈 Performance Tips

1. **Reduce Detection Rate**: Change from 100ms to 200ms
2. **Lower Screenshot Quality**: JPEG quality 0.6 instead of 0.8
3. **Increase Auto-refresh**: 10 seconds instead of 5
4. **Limit Alert History**: Show last 10 instead of all

## ✅ Testing Checklist

- [ ] Plugin installs without errors
- [ ] Database tables created
- [ ] Camera permission works
- [ ] MediaPipe loads successfully
- [ ] Student can start exam
- [ ] Looking left triggers alert
- [ ] Looking right triggers alert
- [ ] Screenshots are captured
- [ ] Alerts save to database
- [ ] Live dashboard shows session
- [ ] Auto-refresh works
- [ ] Report dashboard displays data
- [ ] Screenshot modal works
- [ ] End session works
- [ ] All capabilities correct

## 🎓 Page Access Matrix

| Page | Student | Teacher | URL |
|------|---------|---------|-----|
| Dashboard | ✅ | ✅ | `/local/myplugin/` |
| Take Exam | ✅ | ❌ | `/student_exam.php` |
| Live Monitor | ❌ | ✅ | `/proctor_live.php` |
| Reports | ❌ | ✅ | `/proctor_dashboard.php` |

## 📦 Dependencies

### Required

- Moodle 4.0+ (2022041900)
- PHP 7.4+
- Modern browser with camera
- Internet connection (MediaPipe CDN)

### Optional

- HTTPS (required for production)
- High-performance server (for many concurrent users)

## 🏁 Installation Time

- Download/Copy: 1 minute
- Database Install: 30 seconds
- Configuration: 0 minutes (works out of the box)
- Testing: 5-10 minutes

**Total**: ~12 minutes to fully operational

---

## 📚 Documentation Files

- **README.md**: Complete feature documentation
- **INSTALL.md**: Step-by-step installation guide
- **QUICKREF.md**: This file - quick reference

---

**Version**: 1.0  
**Date**: November 5, 2025  
**Status**: ✅ Production Ready  
**License**: GNU GPL v3+
