<?php
define('DEBUG', FALSE);
define('MAILTO', 'user@example.com');

define('IPTABLES', '/sbin/iptables');
// you may need to adjust this for your system
// which iptables from the command line should give you the complete path to the iptables program

// the sudo command is required because iptables commands must be run as root 
define('COMMANDS', 'sudo ' . __DIR__ . '/portOpenerCommands.php');
// in order for the web page to successfully execute the sudo command
//     an entry must be made in the sudoers file which is typically located at:
//     /etc/sudoers. The preferred method for editing this file is to issue the following command as root:
//     root@host$visudo
//     A line must be added to allow the user executing apache (typically apache or www-data) to run the
//     portOpenerCommands.php file as root without entering a password.
//     Assuming the apache user is apache, and the file is located at /var/www/_secure/portOpenerCommands.php
//     the sudoers line would be the following:
//     apache ALL=(root) NOPASSWD: /var/www/stiffpool/_secure/portOpenerCommands.php
//     if you want to be more restrictive, the ALL before the = may be replaced bty the hostname of the server