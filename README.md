# Moodle Exam Monitor Plugin

A comprehensive Moodle plugin for online exam monitoring with real-time cheating detection using MediaPipe face detection.

## Features

### 1. Student Exam Monitoring

- **Camera Integration**: Real-time camera feed during exams
- **Cheating Detection**: Automatic detection when students look left or right
- **Visual Alerts**: On-screen warnings when cheating is detected
- **Alert History**: View all alerts during the exam session
- **Screenshot Capture**: Automatic screenshot when cheating is detected

### 2. Live Proctor Dashboard

- **Real-time Monitoring**: View all active exam sessions
- **Auto-refresh**: Automatic updates every 5 seconds
- **Student Overview**: See each student's exam status and alert count
- **Duration Tracking**: Live timer for each exam session
- **Quick Actions**: End sessions or view detailed reports

### 3. Post-Exam Reports

- **Detailed Timeline**: Complete history of all alerts with timestamps
- **Screenshot Gallery**: View all captured screenshots
- **Session Statistics**: Duration, alert counts, and student information
- **Historical Data**: Browse all past exam sessions
- **Export Capability**: Ready for future export features

## Installation

1. Copy the plugin folder to `moodle/local/myplugin`
2. Log in to Moodle as an administrator
3. Navigate to Site Administration > Notifications
4. Click "Upgrade Moodle database now"
5. The plugin will be installed and database tables will be created

## Database Structure

The plugin creates three main tables:

### local_myplugin_sessions

Stores exam session information:

- Session ID
- User ID
- Exam name
- Start/end times
- Status (active/completed)

### local_myplugin_alerts

Stores cheating detection alerts:

- Alert ID
- Session ID
- Alert type (looking_left, looking_right)
- Description
- Severity level
- Timestamp

### local_myplugin_screenshots

Stores screenshots of cheating incidents:

- Screenshot ID
- Alert ID
- Session ID
- Image data (base64 encoded)
- Timestamp

## Capabilities

The plugin defines three capabilities:

1. **local/myplugin:takeexam**
   - Assigned to: Students
   - Allows taking monitored exams

2. **local/myplugin:monitor**
   - Assigned to: Teachers, Editing Teachers, Managers
   - Allows real-time monitoring of exams

3. **local/myplugin:viewreports**
   - Assigned to: Teachers, Editing Teachers, Managers
   - Allows viewing exam reports and history

## Technical Implementation

### Frontend

- **JavaScript**: AMD module format for Moodle compatibility
- **MediaPipe**: Face detection using MediaPipe Tasks Vision library
- **AJAX**: Real-time communication with backend
- **Responsive Design**: Works on desktop and tablet devices

### Backend

- **External API**: Web services for AJAX calls
- **Database API**: Standard Moodle database operations
- **Capability Checks**: Proper permission validation
- **Security**: Input validation and sanitization

## File Structure

```txt
local/myplugin/
├── amd/
│   ├── src/
│   │   └── face_detection.js          # Source JavaScript
│   └── build/
│       └── face_detection.min.js      # Minified JavaScript
├── classes/
│   └── external/
│       ├── log_alert.php              # Log alert API
│       ├── end_session.php            # End session API
│       └── get_active_sessions.php    # Get sessions API
├── db/
│   ├── access.php                     # Capability definitions
│   ├── install.xml                    # Database schema
│   └── services.php                   # Web services definition
├── lang/
│   └── en/
│       └── local_myplugin.php         # English language strings
├── index.php                          # Main landing page
├── student_exam.php                   # Student exam interface
├── proctor_live.php                   # Live monitoring dashboard
├── proctor_dashboard.php              # Post-exam reports
├── styles.css                         # Plugin styles
└── version.php                        # Plugin version info
```

## Usage

### For Students

1. Navigate to the plugin homepage
2. Click "Start Exam"
3. Allow camera access when prompted
4. Click "Enable Camera" to begin monitoring
5. Complete the exam normally
6. Click "End Exam" when finished

### For Proctors (Teachers)

#### Live Monitoring

1. Navigate to "Live Monitoring"
2. View all active exam sessions
3. Monitor alert counts in real-time
4. Click "View Details" for specific student
5. Use "End Session" if needed

#### View Reports

1. Navigate to "Exam Reports"
2. Browse all exam sessions
3. Click "View Details" on any session
4. Review alert timeline
5. View captured screenshots

## Cheating Detection

The system detects the following behaviors:

- **Looking Left**: Head rotation beyond -15 degrees (yaw)
- **Looking Right**: Head rotation beyond +15 degrees (yaw)

Detection parameters can be adjusted in `face_detection.js`:

```javascript
const LOOKING_LEFT_THRESHOLD = -15;
const LOOKING_RIGHT_THRESHOLD = 15;
```

## MediaPipe Integration

The plugin uses MediaPipe Face Landmarker for head pose detection:

- **Library**: MediaPipe Tasks Vision v0.10.8
- **CDN**: <https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.8>
- **Detection Rate**: 10 FPS (every 100ms)
- **Alert Cooldown**: 2 seconds between alerts

## Browser Compatibility

- Chrome 80+ (Recommended)
- Firefox 75+
- Edge 80+
- Safari 13+ (with limitations)

**Note**: Camera access requires HTTPS in production environments.

## Future Enhancements

Potential features for future versions:

1. Integration with Moodle Quiz module
2. Multiple detection methods (face recognition, screen capture)
3. Audio monitoring
4. AI-based behavior analysis
5. Export reports to PDF
6. Email notifications for proctors
7. Mobile app support
8. Advanced analytics and trends

## Configuration

No additional configuration required. The plugin works out of the box after installation.

Optional: Adjust JavaScript detection parameters in `amd/src/face_detection.js`

## Support

For issues or questions:

- Check the Moodle logs: Site Administration > Reports > Logs
- Enable debugging: Site Administration > Development > Debugging
- Review browser console for JavaScript errors

## License

This plugin is licensed under the GNU GPL v3 or later.

## Credits

- MediaPipe by Google
- Moodle Community
- Face detection algorithms based on MediaPipe Face Mesh

## Version History

### v1.0 (2025-11-05)

- Initial release
- Basic camera monitoring
- Left/right head movement detection
- Live proctor dashboard
- Post-exam reports
- Screenshot capture

## Privacy

This plugin processes video data for exam monitoring:

- Video is processed locally in the browser
- Only screenshots are stored when cheating is detected
- Stored images are accessible only to authorized proctors
- Data is retained per institutional policy

Ensure compliance with local privacy laws and inform students of monitoring.

## Troubleshooting

### Camera Not Working

- Check browser permissions
- Ensure HTTPS connection
- Verify camera is not in use by another application

### MediaPipe Not Loading

- Check internet connection (CDN required)
- Verify firewall settings
- Check browser console for errors

### Alerts Not Saving

- Check Moodle error logs
- Verify database tables exist
- Ensure web services are enabled

### Dashboard Not Updating

- Enable auto-refresh toggle
- Check browser console for errors
- Verify AJAX endpoints are working

## Development

To modify the JavaScript:

1. Edit `amd/src/face_detection.js`
2. Minify for production: Copy to `amd/build/face_detection.min.js`
3. Clear Moodle cache: Site Administration > Development > Purge all caches

## Testing

Recommended testing procedure:

1. Create test student account
2. Create test teacher account
3. Student: Start exam and enable camera
4. Student: Look left and right to trigger alerts
5. Teacher: Monitor in live dashboard
6. Student: End exam
7. Teacher: Review reports and screenshots

---

**Author**: Bryan Herdianto  
**Repository**: exambrother  
**Last Updated**: November 5, 2025
