FreshPC Cloud - Authentication Logs

This folder contains login attempt logs for security monitoring.

Files:
- auth.log: Contains all login attempts (success and failures)
- .htaccess: Protects log files from direct web access

Access logs via:
- Admin dashboard (when logged in)
- view-logs.php (standalone viewer, no login required)

Log format:
[YYYY-MM-DD HH:MM:SS] STATUS - Username: email@domain.com - IP: xxx.xxx.xxx.xxx

Log files are automatically created when first login attempt is made.