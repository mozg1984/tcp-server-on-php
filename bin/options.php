<?php

$params = array(
    ''      => 'help',
    'p:'    => 'port:',
);

// Default values
$address    = '127.0.0.1';
$port       = 0;
$errors     = [];

$options = getopt(implode('', array_keys($params)), $params);

// File path option
if (isset($options['port']) || isset($options['p'])) {
    $port = isset($options['port']) 
            ? $options['port'] 
            : $options['p'];
    $port = (int) $port;
}
else {
    $errors[]   = 'port required';
}

if ($port <= 0) {
    $errors[] = 'port number is not valid';
}

if (isset($options['help']) || count($errors)) {
    $help = "
usage: bin/console [--help] [-p|--port]

Options:
            --help      Show this message
        -p  --port      Port to bind to server
Example:
        bin/console --port=1234
";
    
    if ($errors) {
        $help .= 'Errors:' . PHP_EOL . implode("\n", $errors) . PHP_EOL;
    }

    die($help);
}