# tcp-server-on-php
TCP Server example on PHP

TCP server by default binds to localhost.
To test TCP server you can use telnet service (telnet 127.0.0.1 1234).
To build project use Composer.

usage: bin/console [--help] [-p|--port]

Options:
            --help      Show this message
        -p  --port      Port to bind to server

Example:
        bin/console --port=1234

Dependencies:
    php7.*
    PCNTL