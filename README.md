## rainloop-iredmail ##
A plugin for [RainLoop](http://www.rainloop.net/) to allow your [iRedMail](https://www.iredmail.org/) users to change their passwords. To use, just head to Settings in RainLoop and click Password at the left. This is an unofficial plugin with no affiliation to the iRedMail or RainLoop teams.

### Requirements ###
- iRedMail 0.8 or greater, MySQL or PostgreSQL edition (Sorry, no OpenLDAP)
- RainLoop 1.6 or greater

### Installation ###
1. Upload the "iredmail" directory to `data/_data_/_default_/plugins` in your RainLoop installation. (NOTE: Older versions of RainLoop have a slightly different data directory structure with a random hash, but you will still find a plugins directory)
2. Activate in the Plugins section of the admin area.
3. Configure with your iRedMail edition (MySQL/MariaDB or PgSQL), and the `vmail` database password iRedMail generated for you. You can find it in settings.py in iRedAdmin's root directory (`/opt/www/iredadmin` on Debian).
4. You may need to remove `escapeshellcmd` and `shell_exec` from php.ini's disable_functions directive (`/etc/php5-fpm/php.ini` on Debian). This plugin uses the doveadm utility.

The database hostname and other more complicated parameters can be edited in index.php.

### Troubleshooting ###
If password changes are not successful, you can enable RainLoop logging to find out why. Open *data/data/default/configs/application.ini* and find `[logs]`, then change `enable` to `On` below it. Try to change the password again, then check the logfile. Disable logs when you're finished to prevent disk space from being slowly eaten.
