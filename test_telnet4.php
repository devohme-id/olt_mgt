<?php
$host = "103.122.64.161";
$port = 23;
$timeout = 10;
$socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
stream_set_timeout($socket, 5);

function readAll($socket, $stopChar = null) {
    $buffer = '';
    while (!feof($socket)) {
        $char = fgetc($socket);
        if ($char === false) break;
        $buffer .= $char;
        if ($stopChar && str_ends_with($buffer, $stopChar)) {
            break;
        }
    }
    return $buffer;
}

readAll($socket, ':');
fwrite($socket, "root\r\n");
readAll($socket, ':');
fwrite($socket, "admin\r\n");
readAll($socket, '>');
fwrite($socket, "enable\r\n");
readAll($socket, '#');

fwrite($socket, "config\r\n");
readAll($socket, '#');

fwrite($socket, "interface epon 0/1\r\n");
readAll($socket, '#');

fwrite($socket, "?\r\n");
echo "HELP INTERFACE EPON:\n";
echo readAll($socket, '#');

fclose($socket);
