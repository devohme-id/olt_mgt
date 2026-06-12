<?php
$host = "103.122.64.161";
$port = 23;
$timeout = 10;
$socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
if (!$socket) {
    die("Connection failed\n");
}
stream_set_timeout($socket, 5);

function telnetWaitFor($socket, string $pattern, int $timeout = 5) {
    $buffer = '';
    $start = time();
    while (time() - $start < $timeout) {
        $char = fgetc($socket);
        if ($char === false) {
            $info = stream_get_meta_data($socket);
            if ($info['timed_out']) {
                usleep(10000);
                continue;
            }
            break;
        }
        $buffer .= $char;
        if (stripos($buffer, $pattern) !== false) {
            return $buffer;
        }
    }
    return $buffer;
}

echo "WAITING FOR username:\n";
$out = telnetWaitFor($socket, 'username:');
var_dump($out);

usleep(100000); // 100ms
echo "SENDING root\n";
fwrite($socket, "root\r\n");

echo "WAITING FOR password:\n";
$out = telnetWaitFor($socket, 'password:');
var_dump($out);

usleep(100000);
echo "SENDING admin\n";
fwrite($socket, "admin\r\n");

echo "WAITING FOR PROMPT (> or #):\n";
$out = telnetWaitFor($socket, '>');
if (strpos($out, '>') === false) {
    $out .= telnetWaitFor($socket, '#');
}
var_dump($out);

fclose($socket);
