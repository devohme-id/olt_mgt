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

        stream_set_timeout($socket, 2);

        // Wait for login prompt and authenticate (case-insensitive)
        $this->telnetWaitFor($socket, 'username:');
        usleep(250000); // Wait 250ms for OLT to be ready
        fwrite($socket, $olt->telnet_username . "\r\n");

        $this->telnetWaitFor($socket, 'password:');
        usleep(250000); // Wait 250ms for OLT to be ready
        fwrite($socket, $olt->telnet_password . "\r\n");

        // Wait for command prompt (> or #)
        $prompt = $this->telnetWaitForAny($socket, ['>', '#']);

        if (!str_contains($prompt, '>') && !str_contains($prompt, '#')) {
            throw new \RuntimeException("Telnet login failed or prompt not found. Output: " . trim($prompt));
        }

        if (str_ends_with(trim($prompt), '>')) {
            // Negotiate enable mode to get to #
            fwrite($socket, "enable\r\n");
            $enablePrompt = $this->telnetWaitForAny($socket, ['#', 'password:']);
            
            if (stripos($enablePrompt, 'password:') !== false) {
                usleep(250000); // Wait 250ms for OLT to be ready
                fwrite($socket, $olt->telnet_password . "\r\n");
                $enablePrompt = $this->telnetWaitFor($socket, '#');
            }

            if (!str_contains($enablePrompt, '#')) {
                throw new \RuntimeException("Failed to enter privilege enable mode. Output: " . trim($enablePrompt));
            }
        }

        // Split multiline commands and run sequentially
        $lines = preg_split('/\r\n|\r|\n/', $command);
        $output = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            usleep(100000); // Wait 100ms before sending command
            fwrite($socket, $line . "\r\n");
            // Wait for prompt (> or #) after each command
            $output .= $this->telnetWaitForAny($socket, ['>', '#']);
        }

        fclose($socket);

        Log::info("CLI execute on {$olt->name} via telnet: {$command}");

        return $output;
    }

    /**
     * Read from telnet socket until a case-insensitive pattern is found.
     */
    private function telnetWaitFor($socket, string $pattern, int $timeout = 10): string
    {
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
                Log::debug("telnetWaitFor matched '$pattern', buffer: " . $buffer);
                return $buffer;
            }
        }
        Log::debug("telnetWaitFor timeout for '$pattern', buffer: " . $buffer);

        return $buffer;
    }

    /**
     * Read from telnet socket until any of the patterns are found at the end of the buffer.
     */
    private function telnetWaitForAny($socket, array $patterns, int $timeout = 10): string
    {
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

            foreach ($patterns as $pattern) {
                if (str_ends_with(trim($buffer), $pattern)) {
                    Log::debug("telnetWaitForAny matched '$pattern', buffer: " . $buffer);
                    return $buffer;
                }
            }
        }
        Log::debug("telnetWaitForAny timeout, buffer: " . $buffer);

        return $buffer;
    }
}

