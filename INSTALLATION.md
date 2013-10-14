#Installation instructions
Command line commands are displayed using $ as the command prompt.
Unless otherwise noted, command line commands are to be run from the directory in which 
portOpener is installed.

1. Copy the files to a subdirectory under your webroot
    - I created a virtual host that listens only over SSL
    - I use apache HTTP Auth to require a user name and password in order to access this directory.
    - If you don't use HTTP Auth, then you should modify portOpener.php to add security.
2. Rename portOpenerConfig.template.php to portOpenerConfig.php

        $mv portOpenerConfig.template.php portOpenerConfig.php
3. Edit portOpenerConfig.php:
    1. Change the MAILTO value to the email address where you wish to get notifications
    2. Make sure the paths for the iptables executable and portOpenerCommands.php are correct
4. Make sure portOpenerCommands.php is executable by the user running the apache daemon

        $chmod +x portOpenerCommands.php
5. Make sure portOpenerCron.php is executable by root by running:

        $chmod u+x portOpenerCron.php
6. Create the following entry in your /etc/sudoers file (must be done as root)

        apache {hostname} = (root) NOPASSWD: /path/to/portOpenerCommands.php
        substitue the hostname of your server for {hostname}

        Assumes that apache is the user running the apache daemon or service

    - Please note, this gives the apache application permission to run portOpenerCommands.php as root. Because 
apache cannot write to this file and alter it, the risk is minimal.
    - You can further minimize your risk by moving the two exectutable files, portOpenerCron.php and 
portOpenerCommands.php to a directory that is not below your webroot. You will have to adjust 
the paths for the executables and the require statements accordingly.

7. Add a cron job to run portOpenerCron.php periodically. This will look for temporary rules that are expired and delete them. 
You can run this as frequently or infrequently as you wish. I decided that every two minutes was good for my needs.

        $crontab -e
        Add this line:
        */2 * * * * /path/to/portOpenerCron.php

8. Add a chain to iptables named TEMP_OPENINGS

        $iptables --new TEMP_OPENINGS
9. Create a rule on your INPUT chain to jump to the TEMP_OPENINGS chain, insert this as your first rule.
    - I assume your default action on your INPUT chain is to ACCEPT, and that your last rule is to DROP ALL
    - This is a pretty standard and sensible setup.
    - I use a separate chain and jump to it first so that I don't need to worry about inserting temp
    rules, I can simply add them.


        iptables --insert INPUT --jump TEMP_OPENINGS

10. Save your iptables rules to the file where they were previously saved so that the new chain and jump rule are restored 
on reboots. The instructions to do this vary with Linux distributions

    - Steps 9 and 10 are optional, there are checks in the programs to create the necessary chain and jump to that chain, but by
creating and saving them, you have complete control over where the jump appears in your INPUT chain.
