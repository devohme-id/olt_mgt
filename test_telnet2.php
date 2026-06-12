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
readAll($socket, '>'); // wait for prompt

// Send enable just in case
fwrite($socket, "enable\r\n");
readAll($socket, '#');

fwrite($socket, "epon reset-onu ?\r\n");
echo "HELP OUTPUT:\n";
echo readAll($socket, '#');

fclose($socket);
