## Oro shell tools

OroCRM has number of shell scripts created to leverage user dealing with routines like code and database backup and restore, graceful stop and start of application (maintenance mode), etc.
These tools are plced in app/bin/ directory of the appliction. Below is breif description of these tools.

- Requirements and limitations

These tools are environment agnostic and require very little from environment to be working (just some Nginx server configuration preparation, Apache will use customized .htaccess)
Some configuration should be done in configuration file app/config/oro_env.conf (please adjust file pathes to Oro installation, etc, as per commments in app/config/oro_env.conf).

However, it is REQUIRED that these scripts MUST run from the same user as webserver/PHP processes owner, it MUST be run from OroCRM application root directory and user it runs from must have write access to application directory, plus directory one level up (../$oro_web_root_dir) and system temporary directory (/tmp). It does not require root privileges.

It is also possible to run it by root or any other sudo user via sudo on befalf of OroCRM files and Webserver/PHP-FPM owner like this:

```bash
	cd /var/www/oro/crm-application/
	sudo -u nginx ./app/bin/oro_backup.sh
	sudo -u nginx ./app/bin/oro_restore.sh
```

- Maintenance mode support

Below is an example snippet from Nginx config that adds maintenance mode ability for Web server running OroCRM:

```bash
        try_files $document_root/maintenance.lock @maintenance;
        error_page 503 @maintenance;

        location @maintenance {
                rewrite ^(.*)$ /maintenance.html break;
        }
```

- General description

These tools are set of scripts that can be used ad hoc. Some of scripts incorporate other scripts and run them in required order. Script names are pretty self-explanatory. Scripts do not reqiure any user input with 2 exceptions: app/bin/oro-clankd tahes arguments start|stop|status and oro_db_restore.sh can take as argument name of the file with database backup that you want to restore from). In latter case, if no argument supplied oro_db_restore.sh restores from latest avaliable backup in db backup files location (default behavior).

Also, be aware, that maintenance script detects if any jobs are running and ask user to wait or stop job queue daemon and waits for few seconds and checks if there are non finished jobs back and forth unless all jobs are finished or user interrupts script (in latter case INT is trapped and all preparations for maintenance are restored to previous state and maintenance is off). 

These scripts we designed to be modular and extensible, available for use from other applications/scripts.

- Websocket service

OroCRM provides application with Websocket support. WS server runs by PHP CLI script according to configuration in app/config/parameters.yml

However, it is not practical to run it from shell typing "php app/console clank:server --env prod". This is why Oro tools include app/bin/oro_clankd shell wrapper, that mmay be called from system init script and allows to stop|start|status Websocket service. OroCRM installation comes with init script template in app/oro-ws.dist. Update this file with PHP process owner and full path to OroCRM installation. Copy it as root to /etc/init.d directory:

```bash
	cp app/oro-ws.dist /etc/init.d/oro-ws
```

Apparently, /etc/init.d/oro-ws is simply calling app/bin/oro_clankd init script and translate arguments for it. Now you can start, stop or ask Websocket service for status like this:

```bash
	/etc/init.d/oro-ws start
```

Also, when Websocket service starts it creates PID file (ws_PID.pid) in /tmp directory of the server and redirects all output to log file (app/logs/clank.log by default). It is possible to mmonitor query service status by direct quering "/etc/init.d/oro-ws status", check if process "clank:server" is running, or check if "clank:server"if PID file exists in /tmp.  
