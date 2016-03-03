## Oro shell tools

OroCRM has number of shell scripts created to leverage user dealing with routines like code and database backup and restore, graceful stop and start of application (maintenance mode), etc.
These tools are plced in app/bin/ directory of the appliction. Below is breif description of these tools.

- Requirements and limitations

These tools are environment agnostic and require very little from environment to be working (just some Nginx server configuration preparation, Apache will use customized .htaccess)
Some configuration maybe done in configuration file app/config/oro_env.conf (default values will work fine in most simple cases).

Below is snipped from Nginx config that adds maintenance mode ability:

```bash
        if (try_files $document_root/maintenance.lock) {
                return 503;
        }
        
        error_page 503 @maintenance;

        location @maintenance {
                rewrite ^(.*)$ /maintenance.html break;
        }
```

However, it is REQUIRED that these scripts MUST run from the same user as webserver/PHP processes owner, it MUST be run from OroCRM application root directory and user it runs from must have write access to application directory, plus directory one level up (../$oro_web_root_dir) and system temporary directory (/tmp). It does not require root privileges.

However, it is possible to run it by root or other user via sudo like this:

```bash
        su - nginx -c "cd /var/www/oro/crm-application/; ./app/bin/oro_backup.sh"
```

- General description

These tools are set of scripts that can be used ad hoc. There is two major scripts (oro_backup.sh and oro_restore.sh) which incorporate other scripts and run them in required order. Script names are pretty self-explanatory. Scripts does not reqiure any user input. There is one script that accept argument: oro_db_restore.sh can take as argument name of the file with datanase backup that you want to restore from. If no arggument supplied it restores from latest avaliable backup in db backup files location (default behavior).

Also, be aware, that maintenance script detects if any jobs are running and ask user to wait or stop job queue daemon and waits for few seconds and checks if there are non finished jobs back and forth unless all jobs are finished or user interrupts script (in latter case INT is trapped and all preparations for maintenance are restored to previous state and maintenance is off). 

These scripts we designed to be modular and extensible, available for use from other applications/scripts (from PHP or JS, etc).
