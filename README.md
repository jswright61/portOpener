# Port Opener

## Introduction

Port OS attempts to solve a specific problem: Allowing access to a server from just about anywhere while maximizing security. This program requires 
a familiarity with the Linux command line and knowledge of Linux security practices. While step by step instructions are provided, it is incumbent on 
the person installing this software to understand the implications and risks associated with its instillation.

## Dependencies
- iptables. Linux firewall.
- PHP 5.3 or greater.
- Apache.
- root access to your server (temporarily) The program will require a cron installed by root as well as an entry in /etc/sudoers to allow the execution 
of a script by the user running apache.
- Highly recommended: a mail transport agent (MTA), such as postfix, installed on your server.
- Highly Recommended: an SSL certificate which may be self signed.

## Overview
Port Opener presents a web page with the ability to add temporary ACCEPT rules to an iptables chain. It also gives you the ability to delete existing temporary 
rules via the same web page. The program and the web page are very simple. There are some setup steps that must be taken to make everything work, but step by step instructions are provided. If you are not using iptables on your server, and do not want to, then this program is not for you. There are no separate records to maintain, no database 
to keep track of rules. A simple comment on the iptables rule stores the expiration time. Sample rule:

        num  target     prot opt source               destination         
        1    ACCEPT     tcp  --  8.8.1.1              0.0.0.0/0            tcp dpt:22 /* tempRuleExpires:1381712389 Sun 20:59:49 requestIP:8.8.1.2 */

> This rule will allow connections from ip address 8.8.1.1 on port 22 (ssh) to the server. It expires on Sunday 10/13/2013 at 8:59:49 PM EDT. It
was requested via the web interface from ip address 8.8.1.2. Not the unix timestamp following expires is what controls the expiration, The textual 
date representation is there for convenience, as is the requesting ip address.


## Installation
Please see INSTALLATION.md for detailed installation instructions.