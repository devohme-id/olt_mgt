<?php

namespace Database\Seeders;

use App\Domain\Device\Models\DeviceModel;
use App\Domain\Device\Models\FirmwareProfile;
use App\Domain\Device\Models\Region;
use App\Domain\Device\Models\ServiceProfile;
use App\Domain\Device\Models\Site;
use App\Domain\Device\Models\Vendor;
use App\Domain\Device\Models\VendorOidMapping;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        // ── HSGQ Vendor ──
        $hsgq = Vendor::create([
            'name'           => 'HSGQ',
            'code'           => 'hsgq',
            'enterprise_oid' => '1.3.6.1.4.1.3320',
            'is_active'      => true,
        ]);

        $hsgqE04i = DeviceModel::create([
            'vendor_id'         => $hsgq->id,
            'name'              => 'HSGQ E04I',
            'model_number'      => 'HSGQ-E04I',
            'device_type'       => 'epon',
            'max_pon_ports'     => 4,
            'max_onus_per_port' => 64,
            'capabilities'      => [
                'snmp_monitoring'    => true,
                'snmp_provisioning'  => false,
                'cli_provisioning'   => true,
                'onu_auto_discovery' => true,
                'vlan_management'    => true,
                'speed_limit'        => true,
                'config_backup'      => true,
            ],
        ]);

        FirmwareProfile::create([
            'device_model_id'    => $hsgqE04i->id,
            'version'            => 'HSGQ-E04I_I_V3.2.9C_Rel',
            'hardware_version'   => 'HSGQ-E04I-hw-version-v4.0',
            'supported_features' => [
                'snmp_monitoring'    => true,
                'snmp_provisioning'  => false,
                'cli_provisioning'   => true,
                'onu_auto_discovery' => true,
                'vlan_management'    => true,
                'speed_limit'        => true,
            ],
            'cli_command_set' => [
                'register_onu'   => "config\r\ninterface epon {pon_port}\r\nbind-onu mac {mac} sequence {seq}",
                'delete_onu'     => "config\r\ninterface epon {pon_port}\r\nno bind-onu sequence {seq}",
                'reboot_onu'     => "config\r\ninterface onu {pon_port}/{onu_id}\r\nreboot",
                'set_vlan'       => "config\r\ninterface onu {pon_port}/{onu_id}\r\nport-vlan 1 mode tag {vlan_id} pri 0",
                'set_speed'      => "config\r\ninterface onu {pon_port}/{onu_id}\r\nport-rate-limit 1 egress cir {down} pir {down}",
                'show_onu_info'  => 'show epon onu-info {pon_port}',
                'show_optical'   => 'show epon optical-info {pon_port}',
                'show_running'   => 'show running-config',
                'save_config'    => 'write memory',
            ],
            'oid_overrides' => [],
            'is_active'     => true,
        ]);

        // ── HSGQ OID Mappings ──
        $oidMappings = [
            // System Health
            ['metric_key' => 'cpu_usage',     'oid' => '1.3.6.1.4.1.50224.3.1.1.8.0',       'data_type' => 'integer', 'unit' => '%'],
            ['metric_key' => 'memory_usage',  'oid' => '1.3.6.1.4.1.50224.3.1.1.17.0',      'data_type' => 'integer', 'unit' => '%'],
            ['metric_key' => 'temperature',   'oid' => '1.3.6.1.4.1.50224.3.1.1.18.0',      'data_type' => 'integer', 'unit' => '°C'],
            ['metric_key' => 'uptime',        'oid' => '1.3.6.1.2.1.1.3.0',                   'data_type' => 'timeticks', 'unit' => 'seconds'],

            // ONU Management
            ['metric_key' => 'onu_table',     'oid' => '1.3.6.1.4.1.3320.101.11.1.1',         'data_type' => 'string',  'unit' => null],
            ['metric_key' => 'onu_status',    'oid' => '1.3.6.1.4.1.3320.101.11.1.1.6',       'data_type' => 'integer', 'unit' => null],
            ['metric_key' => 'onu_mac',       'oid' => '1.3.6.1.4.1.3320.101.11.1.1.3',       'data_type' => 'mac',     'unit' => null],
            ['metric_key' => 'onu_distance',  'oid' => '1.3.6.1.4.1.3320.101.11.1.1.18',      'data_type' => 'integer', 'unit' => 'meters'],

            // Optical Power
            ['metric_key' => 'pon_rx_power',  'oid' => '1.3.6.1.4.1.3320.10.3.4.1.2',         'data_type' => 'optical_power', 'unit' => 'dBm', 'multiplier' => 0.01],
            ['metric_key' => 'pon_tx_power',  'oid' => '1.3.6.1.4.1.3320.10.3.4.1.3',         'data_type' => 'optical_power', 'unit' => 'dBm', 'multiplier' => 0.01],
            ['metric_key' => 'onu_rx_power',  'oid' => '1.3.6.1.4.1.3320.101.108.1.1.3',      'data_type' => 'optical_power', 'unit' => 'dBm', 'multiplier' => 0.01],
            ['metric_key' => 'onu_tx_power',  'oid' => '1.3.6.1.4.1.3320.101.108.1.1.2',      'data_type' => 'optical_power', 'unit' => 'dBm', 'multiplier' => 0.01],

            // Interface Traffic (standard IF-MIB)
            ['metric_key' => 'if_in_octets',  'oid' => '1.3.6.1.2.1.31.1.1.1.6',              'data_type' => 'counter', 'unit' => 'bytes'],
            ['metric_key' => 'if_out_octets', 'oid' => '1.3.6.1.2.1.31.1.1.1.10',             'data_type' => 'counter', 'unit' => 'bytes'],
        ];

        foreach ($oidMappings as $mapping) {
            VendorOidMapping::create(array_merge($mapping, [
                'vendor_id'       => $hsgq->id,
                'device_model_id' => $hsgqE04i->id,
                'multiplier'      => $mapping['multiplier'] ?? 1,
                'is_active'       => true,
            ]));
        }

        // ── Placeholder Vendors (for future extension) ──
        foreach (['Huawei' => 'huawei', 'ZTE' => 'zte', 'VSOL' => 'vsol', 'CData' => 'cdata', 'FiberHome' => 'fiberhome'] as $name => $code) {
            Vendor::create(['name' => $name, 'code' => $code, 'is_active' => false]);
        }

        // ── Default Region & Site ──
        $region = Region::create([
            'name' => 'Default Region',
            'code' => 'DEFAULT',
        ]);

        Site::create([
            'region_id' => $region->id,
            'name'      => 'Main Site',
            'code'      => 'MAIN',
        ]);

        // ── Default Service Profiles ──
        $profiles = [
            ['name' => '10 Mbps',  'upstream_bw_kbps' => 5000,   'downstream_bw_kbps' => 10000],
            ['name' => '20 Mbps',  'upstream_bw_kbps' => 10000,  'downstream_bw_kbps' => 20000],
            ['name' => '50 Mbps',  'upstream_bw_kbps' => 25000,  'downstream_bw_kbps' => 50000],
            ['name' => '100 Mbps', 'upstream_bw_kbps' => 50000,  'downstream_bw_kbps' => 100000],
        ];

        foreach ($profiles as $profile) {
            ServiceProfile::create(array_merge($profile, [
                'service_type' => 'internet',
                'is_active'    => true,
            ]));
        }
    }
}
