<?php
$host = "103.122.64.161";
$port = 23;
$timeout = 10;
$socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
if (!$socket) {
    die("Connection failed\n");
}
stream_set_timeout($socket, 5);

$buffer = '';
$start = time();
while (time() - $start < 3) {
    $char = fgetc($socket);
    if ($char === false) {
        break;
    }
    $buffer .= $char;
}

echo "PROMPT:\n";
var_dump($buffer);
fclose($socket);
