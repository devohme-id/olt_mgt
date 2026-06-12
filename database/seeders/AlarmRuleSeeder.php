<?php

namespace Database\Seeders;

use App\Domain\Alarm\Models\AlarmRule;
use Illuminate\Database\Seeder;

class AlarmRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'name'               => 'OLT High CPU',
                'metric_key'         => 'cpu_usage',
                'severity'           => 'major',
                'condition_operator'  => 'gt',
                'threshold_value'     => 90,
                'duration_seconds'    => 300,
                'cooldown_seconds'    => 900,
                'device_type'        => 'olt',
                'notification_channels' => ['telegram'],
            ],
            [
                'name'               => 'OLT Critical CPU',
                'metric_key'         => 'cpu_usage',
                'severity'           => 'critical',
                'condition_operator'  => 'gt',
                'threshold_value'     => 95,
                'duration_seconds'    => 60,
                'cooldown_seconds'    => 300,
                'device_type'        => 'olt',
                'notification_channels' => ['telegram'],
            ],
            [
                'name'               => 'OLT High Temperature',
                'metric_key'         => 'temperature',
                'severity'           => 'major',
                'condition_operator'  => 'gt',
                'threshold_value'     => 65,
                'duration_seconds'    => 0,
                'cooldown_seconds'    => 900,
                'device_type'        => 'olt',
                'notification_channels' => ['telegram'],
            ],
            [
                'name'               => 'OLT Critical Temperature',
                'metric_key'         => 'temperature',
                'severity'           => 'critical',
                'condition_operator'  => 'gt',
                'threshold_value'     => 75,
                'duration_seconds'    => 0,
                'cooldown_seconds'    => 300,
                'device_type'        => 'olt',
                'notification_channels' => ['telegram'],
            ],
            [
                'name'               => 'OLT High Memory',
                'metric_key'         => 'memory_usage',
                'severity'           => 'warning',
                'condition_operator'  => 'gt',
                'threshold_value'     => 85,
                'duration_seconds'    => 300,
                'cooldown_seconds'    => 900,
                'device_type'        => 'olt',
                'notification_channels' => ['telegram'],
            ],
            [
                'name'               => 'ONU Low RX Power',
                'metric_key'         => 'rx_power_dbm',
                'severity'           => 'warning',
                'condition_operator'  => 'lt',
                'threshold_value'     => -27,
                'duration_seconds'    => 0,
                'cooldown_seconds'    => 900,
                'device_type'        => 'onu',
                'notification_channels' => ['telegram'],
            ],
            [
                'name'               => 'ONU Critical RX Power',
                'metric_key'         => 'rx_power_dbm',
                'severity'           => 'critical',
                'condition_operator'  => 'lt',
                'threshold_value'     => -30,
                'duration_seconds'    => 0,
                'cooldown_seconds'    => 300,
                'device_type'        => 'onu',
                'notification_channels' => ['telegram'],
            ],
            [
                'name'               => 'ONU High Optical Loss',
                'metric_key'         => 'optical_loss_db',
                'severity'           => 'warning',
                'condition_operator'  => 'gt',
                'threshold_value'     => 25,
                'duration_seconds'    => 0,
                'cooldown_seconds'    => 900,
                'device_type'        => 'onu',
                'notification_channels' => ['telegram'],
            ],
        ];

        foreach ($rules as $rule) {
            AlarmRule::create(array_merge($rule, ['is_active' => true]));
        }
    }
}
