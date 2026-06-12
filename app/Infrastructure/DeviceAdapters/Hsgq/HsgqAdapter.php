<?php

namespace App\Infrastructure\DeviceAdapters\Hsgq;

use App\Infrastructure\DeviceAdapters\AbstractDeviceAdapter;

class HsgqAdapter extends AbstractDeviceAdapter
{
    public function getSystemInfo(): array
    {
        try {
            $sysDescr = $this->snmpGet('1.3.6.1.2.1.1.1.0');
            $sysName = $this->snmpGet('1.3.6.1.2.1.1.5.0');
            $sysUptime = $this->snmpGet('1.3.6.1.2.1.1.3.0');

            return $this->result(true, 'System info retrieved', [
                'description' => (string) $sysDescr,
                'hostname'    => (string) $sysName,
                'uptime'      => $this->oidParser->parseTimeTicks($sysUptime),
            ]);
        } catch (\Exception $e) {
            return $this->result(false, $e->getMessage());
        }
    }

    public function getOltHealth(): array
    {
        return $this->snmpService->pollOltHealth($this->olt);
    }

    public function getOltTraffic(): array
    {
        try {
            $rxOid = $this->resolveOid('if_in_octets');
            $txOid = $this->resolveOid('if_out_octets');

            // Get traffic from uplink interface (typically ifIndex 1)
            $rx = $rxOid ? $this->snmpGet($rxOid . '.1') : null;
            $tx = $txOid ? $this->snmpGet($txOid . '.1') : null;

            return $this->result(true, '', [
                'rx_bytes' => (int) $rx,
                'tx_bytes' => (int) $tx,
            ]);
        } catch (\Exception $e) {
            return $this->result(false, $e->getMessage());
        }
    }

    public function getPonPorts(): array
    {
        // HSGQ-E04I has 4 EPON ports
        return $this->snmpService->pollOnuTable($this->olt);
    }

    public function getOnus(): array
    {
        return $this->snmpService->pollOnuTable($this->olt);
    }

    public function getOnuOptical(string $onuIndex): array
    {
        try {
            $rxOid = $this->resolveOid('onu_rx_power');
            $txOid = $this->resolveOid('onu_tx_power');

            $rx = $rxOid ? $this->snmpGet("{$rxOid}.{$onuIndex}") : null;
            $tx = $txOid ? $this->snmpGet("{$txOid}.{$onuIndex}") : null;

            return $this->result(true, '', [
                'rx_power_dbm' => $this->oidParser->parseOpticalPower($rx),
                'tx_power_dbm' => $this->oidParser->parseOpticalPower($tx),
            ]);
        } catch (\Exception $e) {
            return $this->result(false, $e->getMessage());
        }
    }

    public function getOnuTraffic(string $onuIndex): array
    {
        // Traffic metrics per ONU via ifTable using ifIndex
        return $this->result(true, '', []);
    }

    public function registerOnu(array $params): array
    {
        try {
            $firmware = $this->olt->firmwareProfile;
            $template = $firmware?->getCliCommand('register_onu');

            if (!$template) {
                return $this->result(false, 'No CLI command template for register_onu');
            }

            $command = $this->buildCliCommand($template, $params);
            $output = $this->cliExecute($command);

            return $this->result(true, 'ONU registered', ['output' => $output]);
        } catch (\Exception $e) {
            return $this->result(false, $e->getMessage());
        }
    }

    public function deleteOnu(string $onuIndex): array
    {
        try {
            $firmware = $this->olt->firmwareProfile;
            $template = $firmware?->getCliCommand('delete_onu');

            if (!$template) {
                return $this->result(false, 'No CLI command template for delete_onu');
            }

            $command = $this->buildCliCommand($template, ['seq' => $onuIndex]);
            $output = $this->cliExecute($command);

            return $this->result(true, 'ONU deleted', ['output' => $output]);
        } catch (\Exception $e) {
            return $this->result(false, $e->getMessage());
        }
    }

    public function rebootOnu(string $onuIndex): array
    {
        try {
            $firmware = $this->olt->firmwareProfile;
            $template = $firmware?->getCliCommand('reboot_onu');

            if (!$template) {
                return $this->result(false, 'No CLI command template for reboot_onu');
            }

            $command = $this->buildCliCommand($template, ['onu_index' => $onuIndex]);
            $output = $this->cliExecute($command);

            return $this->result(true, 'ONU rebooted', ['output' => $output]);
        } catch (\Exception $e) {
            return $this->result(false, $e->getMessage());
        }
    }

    public function rebootOlt(): array
    {
        try {
            $output = $this->cliExecute('reload');
            return $this->result(true, 'OLT reboot initiated', ['output' => $output]);
        } catch (\Exception $e) {
            return $this->result(false, $e->getMessage());
        }
    }

    public function configureVlan(string $onuIndex, array $config): array
    {
        try {
            $firmware = $this->olt->firmwareProfile;
            $template = $firmware?->getCliCommand('set_vlan');

            if (!$template) {
                return $this->result(false, 'No CLI command template for set_vlan');
            }

            $command = $this->buildCliCommand($template, array_merge(
                ['onu_index' => $onuIndex],
                $config
            ));
            $output = $this->cliExecute($command);

            return $this->result(true, 'VLAN configured', ['output' => $output]);
        } catch (\Exception $e) {
            return $this->result(false, $e->getMessage());
        }
    }

    public function applyProfile(string $onuIndex, array $profile): array
    {
        try {
            $firmware = $this->olt->firmwareProfile;
            $template = $firmware?->getCliCommand('set_speed');

            if (!$template) {
                return $this->result(false, 'No CLI command template for set_speed');
            }

            $command = $this->buildCliCommand($template, array_merge(
                ['onu_index' => $onuIndex],
                $profile
            ));
            $output = $this->cliExecute($command);

            return $this->result(true, 'Profile applied', ['output' => $output]);
        } catch (\Exception $e) {
            return $this->result(false, $e->getMessage());
        }
    }

    public function getRunningConfig(): string
    {
        return $this->cliExecute('show running-config');
    }

    public function backupConfig(): string
    {
        return $this->getRunningConfig();
    }
}
