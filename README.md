spawnd
=========

Process manager written in PHP. It manages persistent processes and restarts them if they stop.

It supports the following features currently:

*Running multiple concurrent processes
*Restarting processes if they stop
*Modifying configuration live without restarting
*Enable/disable processes without restarting

##Configuring
Spanwd is configured with ini files in /etc/spawnd

You can create 1 single ini file or multiple smaller ones.

Each process is contained with in its own ini section, e.g.

    [commandName]
    cmd=/usr/bin/mycommand
    enabled=1
