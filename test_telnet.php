<?php
$host = "103.122.64.161";
$port = 23;
$timeout = 10;
$socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
stream_set_timeout($socket, 2);

function readAll($socket) {
    $buffer = '';
    while (!feof($socket)) {
        $char = fgetc($socket);
        if ($char === false) break;
        $buffer .= $char;
    }
    return $buffer;
}

echo "Connect:\n";
echo readAll($socket);
fwrite($socket, "root\n");
echo "After username:\n";
echo readAll($socket);
fwrite($socket, "admin\n");
echo "After password:\n";
echo readAll($socket);
fwrite($socket, "epon reset-onu 16778255\n");
echo "After command 1:\n";
echo readAll($socket);
fwrite($socket, "show epon onu-info 1\n");
echo "After command 2:\n";
echo readAll($socket);
fclose($socket);
