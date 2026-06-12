<?php

namespace App\Infrastructure\Cli;

use App\Domain\Device\Models\Olt;
use Illuminate\Support\Facades\Log;
use phpseclib3\Net\SSH2;

class CliExecutor
{
    /**
     * Execute a CLI command on an OLT via SSH or Telnet.
     */
    public function execute(Olt $olt, string $command): string
    {
        $protocol = config('devices.cli.protocol', 'telnet');

        if ($olt->ssh_username && $olt->ssh_password) {
            return $this->executeViaSsh($olt, $command);
        }

        if ($olt->telnet_username && $olt->telnet_password) {
            return $this->executeViaTelnet($olt, $command);
        }

        throw new \RuntimeException("No CLI credentials configured for OLT {$olt->name}");
    }

    /**
     * Execute command via SSH using phpseclib.
     */
    private function executeViaSsh(Olt $olt, string $command): string
    {
        $ssh = new SSH2($olt->ip_address, $olt->ssh_port ?: 22);
        $timeout = config('devices.cli.default_timeout', 15);
        $ssh->setTimeout($timeout);

        if (!$ssh->login($olt->ssh_username, $olt->ssh_password)) {
            throw new \RuntimeException("SSH login failed for OLT {$olt->name}");
        }

        Log::info("CLI execute on {$olt->name}: {$command}");

        $output = $ssh->exec($command);
        $ssh->disconnect();

        return $output;
    }

    /**
     * Execute command via Telnet (basic socket implementation).
     */
    private function executeViaTelnet(Olt $olt, string $command): string
    {
        $host = $olt->ip_address;
        $port = $olt->telnet_port ?: 23;
        $timeout = config('devices.cli.default_timeout', 15);

        $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);

        if (!$socket) {
            throw new \RuntimeException("Telnet connection failed to {$host}:{$port} - {$errstr}");
        }

        stream_set_timeout($socket, $timeout);

        // Wait for login prompt and authenticate
        $this->telnetWaitFor($socket, 'Username:');
        fwrite($socket, $olt->telnet_username . "\n");

        $this->telnetWaitFor($socket, 'Password:');
        fwrite($socket, $olt->telnet_password . "\n");

        // Wait for command prompt
        $this->telnetWaitFor($socket, '#');

        // Send command
        fwrite($socket, $command . "\n");

        // Read response
        $output = $this->telnetWaitFor($socket, '#');

        fclose($socket);

        Log::info("CLI execute on {$olt->name} via telnet: {$command}");

        return $output;
    }

    /**
     * Read from telnet socket until a pattern is found.
     */
    private function telnetWaitFor($socket, string $pattern, int $timeout = 10): string
    {
        $buffer = '';
        $start = time();

        while (time() - $start < $timeout) {
            $char = fgetc($socket);
            if ($char === false) break;
            $buffer .= $char;

            if (str_contains($buffer, $pattern)) {
                return $buffer;
            }
        }

        return $buffer;
    }
}
