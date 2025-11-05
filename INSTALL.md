# Exam Monitor Plugin - Installation & Setup Guide

## Quick Start

### Step 1: Install the Plugin

1. The plugin is already in place at `c:\xampp\htdocs\moodle\local\myplugin`
2. Open your browser and navigate to your Moodle site
3. Log in as an administrator
4. Go to: **Site Administration > Notifications**
5. You should see the plugin ready to install
6. Click **"Upgrade Moodle database now"**
7. The plugin will create the necessary database tables

### Step 2: Verify Installation

After installation, check:

- ✅ No error messages appear
- ✅ Plugin appears in **Site Administration > Plugins > Local plugins**
- ✅ Database tables created:
  - `mdl_local_myplugin_sessions`
  - `mdl_local_myplugin_alerts`
  - `mdl_local_myplugin_screenshots`

### Step 3: Test Camera Access

1. Navigate to: `http://localhost/moodle/local/myplugin/`
2. Click **"Start Exam"**
3. Browser will request camera permission - click **"Allow"**
4. If camera doesn't work on localhost, you may need to:
   - Use `https://` instead of `http://`
   - OR configure your browser to allow camera on localhost

## Browser Camera Permissions

### Chrome (Recommended)

1. Go to `chrome://settings/content/camera`
2. Add your Moodle site to "Allow" list
3. For localhost: `http://localhost` or `https://localhost`

### Firefox

1. Click the camera icon in address bar
2. Select "Allow" when prompted
3. Check "Remember this decision"

### Edge

1. Go to `edge://settings/content/camera`
2. Add site to allowed list
3. Restart browser if needed

## User Roles & Permissions

### Students

Can access:

- Student exam page (`student_exam.php`)
- Take monitored exams
- See their own alerts

### Teachers/Managers

Can access:

- Live monitoring dashboard (`proctor_live.php`)
- Exam reports (`proctor_dashboard.php`)
- View all student sessions
- Access screenshots and alerts

### Assign Capabilities Manually (if needed)

1. Go to **Site Administration > Users > Permissions > Define roles**
2. Edit "Student" role:
   - Enable `local/myplugin:takeexam`
3. Edit "Teacher" role:
   - Enable `local/myplugin:monitor`
   - Enable `local/myplugin:viewreports`

## Testing the Plugin

### Test 1: Student Exam

1. Create a test student account (or use existing)
2. Log in as student
3. Navigate to: `/local/myplugin/student_exam.php`
4. Click "Enable Camera"
5. Look left and right to trigger alerts
6. Check that alerts appear on screen
7. Click "End Exam"

### Test 2: Live Monitoring

1. Keep student exam running (Step 1 above)
2. In another browser/incognito, log in as teacher
3. Navigate to: `/local/myplugin/proctor_live.php`
4. Verify you see the active session
5. Check that alerts appear in real-time
6. Note: Auto-refresh is enabled by default (5 seconds)

### Test 3: Reports Dashboard

1. After ending exam session
2. Log in as teacher
3. Navigate to: `/local/myplugin/proctor_dashboard.php`
4. Click on the exam session
5. Verify:
   - Alert timeline appears
   - Screenshots are visible
   - Statistics are correct

## Troubleshooting

### Problem: Plugin doesn't appear in notifications

**Solution**:

- Clear Moodle cache: Site Administration > Development > Purge all caches
- Check version.php has correct version number
- Ensure folder is in correct location: `moodle/local/myplugin`

### Problem: Camera not working

**Solution**:

- Check browser console for errors (F12)
- Verify HTTPS (required for most browsers)
- For localhost testing:

  ```txt
  Chrome: Go to chrome://flags/#unsafely-treat-insecure-origin-as-secure
  Add: http://localhost
  Restart Chrome
  ```

- Check camera is not in use by another app

### Problem: MediaPipe not loading

**Solution**:

- Verify internet connection (CDN required)
- Check browser console for 404 errors
- Test CDN link manually: <https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.8/vision_bundle.js>
- Check firewall/proxy settings

### Problem: Alerts not saving

**Solution**:

- Check Moodle error logs
- Verify web services are enabled:
  - Site Administration > Advanced features
  - Enable "Enable web services"
- Check database tables exist
- Verify user has capability `local/myplugin:takeexam`

### Problem: Database errors on install

**Solution**:

- Check `db/install.xml` syntax
- Verify Moodle version compatibility (requires 2022041900+)
- Check database permissions
- Try manual installation:

  ```sql
  -- Run in your database (not recommended, use Moodle upgrade instead)
  -- Check error logs for specific table creation issues
  ```

### Problem: JavaScript not loading

**Solution**:

- Clear browser cache (Ctrl+Shift+Delete)
- Purge Moodle caches
- Check file exists: `amd/build/face_detection.min.js`
- Verify file permissions (readable)
- Check browser console for errors

## Development Mode Setup

For development and testing:

1. **Enable Debugging**:

   ```txt
   Site Administration > Development > Debugging
   - Debug messages: DEVELOPER
   - Display debug messages: Yes
   ```

2. **Disable Caching**:

   ```txt
   Site Administration > Development > Purge all caches (after each change)
   ```

3. **JavaScript Debugging**:
   - Use source file: `amd/src/face_detection.js`
   - Browser console: F12
   - Add `console.log()` statements

4. **Database Inspection**:
   - Use phpMyAdmin
   - Check tables: `mdl_local_myplugin_*`
   - Monitor record creation

## Production Deployment

Before deploying to production:

1. **HTTPS Required**: Ensure site uses HTTPS (camera requirement)
2. **Test Thoroughly**: Run all test scenarios
3. **Backup Database**: Before installation
4. **Check Server Resources**: Camera processing is CPU-intensive
5. **Privacy Policy**: Update to include monitoring disclosure
6. **User Training**: Train teachers on dashboard usage
7. **Student Information**: Inform students of monitoring

## Performance Optimization

For better performance:

1. **Adjust Detection Frequency**:

   ```javascript
   // In face_detection.js, change interval
   setInterval(function() {
       if (isMonitoring) {
           self.detectFacePosition();
       }
   }, 200); // Change from 100ms to 200ms (5 FPS instead of 10 FPS)
   ```

2. **Screenshot Quality**:

   ```javascript
   // In face_detection.js
   const screenshot = canvas.toDataURL('image/jpeg', 0.6); // Lower quality = smaller size
   ```

3. **Auto-refresh Rate**:

   ```javascript
   // In proctor_live.php
   autoRefreshInterval = setInterval(refreshDashboard, 10000); // 10 seconds instead of 5
   ```

## Uninstallation

To remove the plugin:

1. **Backup Data** (if needed):
   - Export session data
   - Save screenshots

2. **Uninstall via Moodle**:
   - Site Administration > Plugins > Plugins overview
   - Find "Exam Monitor" (local_myplugin)
   - Click "Uninstall"
   - Confirm deletion
   - Tables will be dropped automatically

3. **Manual Cleanup** (if needed):
   - Delete folder: `moodle/local/myplugin`
   - Clear caches

## Integration with Moodle Quiz

To integrate with Moodle Quiz module (future enhancement):

1. Current setup works standalone
2. For quiz integration, modify:
   - `student_exam.php` to load quiz questions
   - Add quiz module dependency
   - Hook into quiz events

## Getting Help

If you encounter issues:

1. Check README.md for feature documentation
2. Review this installation guide
3. Check Moodle error logs
4. Check browser console (F12)
5. Enable debugging mode
6. Test in different browser

## Next Steps

After successful installation:

1. ✅ Test with real exam scenario
2. ✅ Train proctors on dashboard usage  
3. ✅ Customize alert thresholds if needed
4. ✅ Review privacy/legal requirements
5. ✅ Plan integration with existing exams
6. ✅ Set up backup schedule for exam data

## Configuration Checklist

- [ ] Plugin installed successfully
- [ ] Database tables created
- [ ] Camera permissions working
- [ ] MediaPipe loading correctly
- [ ] Student can start exam
- [ ] Alerts are detected and logged
- [ ] Screenshots are captured
- [ ] Live dashboard shows sessions
- [ ] Reports display correctly
- [ ] All user roles have correct capabilities
- [ ] HTTPS configured (for production)
- [ ] Users informed about monitoring

---

**Installation Support**: Check the README.md for detailed troubleshooting
**Plugin Version**: 1.0
**Moodle Requirement**: 4.0+ (2022041900)
**Last Updated**: November 5, 2025
