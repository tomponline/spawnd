spawnd
=========

Process manager written in PHP. It manages persistent processes and restarts them if they stop.

#Configuring
Spanwd is configured with ini files in /etc/spawnd

You can create 1 single ini file or multiple smaller ones.

Each process is contained with in its own ini section, e.g.

    [commandName]
    cmd=/usr/bin/mycommand
    enabled=1
