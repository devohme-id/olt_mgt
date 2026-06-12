<?php

namespace App\Infrastructure\DeviceAdapters;

interface DeviceAdapterInterface
{
    // ── Monitoring ──
    public function getSystemInfo(): array;
    public function getOltHealth(): array;
    public function getOltTraffic(): array;
    public function getPonPorts(): array;
    public function getOnus(): array;
    public function getOnuOptical(string $onuIndex): array;
    public function getOnuTraffic(string $onuIndex): array;

    // ── Provisioning ──
    public function registerOnu(array $params): array;
    public function deleteOnu(string $onuIndex): array;
    public function rebootOnu(string $onuIndex): array;
    public function rebootOlt(): array;
    public function configureVlan(string $onuIndex, array $config): array;
    public function applyProfile(string $onuIndex, array $profile): array;

    // ── Configuration ──
    public function getRunningConfig(): string;
    public function backupConfig(): string;
}
