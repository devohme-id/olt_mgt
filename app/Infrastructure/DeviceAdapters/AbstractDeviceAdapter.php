<?php

namespace App\Infrastructure\DeviceAdapters;

use App\Domain\Device\Models\Olt;
use App\Infrastructure\Snmp\SnmpClientFactory;
use App\Infrastructure\Snmp\OidRegistry;
use App\Infrastructure\Snmp\OidParser;
use App\Infrastructure\Snmp\SnmpPollingService;
use App\Infrastructure\Cli\CliExecutor;

abstract class AbstractDeviceAdapter implements DeviceAdapterInterface
{
    protected SnmpPollingService $snmpService;
    protected OidRegistry $oidRegistry;
    protected OidParser $oidParser;
    protected CliExecutor $cliExecutor;

    public function __construct(
        protected Olt $olt,
    ) {
        $this->snmpService = app(SnmpPollingService::class);
        $this->oidRegistry = app(OidRegistry::class);
        $this->oidParser = app(OidParser::class);
        $this->cliExecutor = app(CliExecutor::class);
    }

    protected function snmpGet(string $oid): mixed
    {
        return $this->snmpService->snmpGet($this->olt, $oid);
    }

    protected function cliExecute(string $command): string
    {
        return $this->cliExecutor->execute($this->olt, $command);
    }

    protected function resolveOid(string $metricKey): ?string
    {
        $mapping = $this->oidRegistry->resolve($this->olt, $metricKey);
        return $mapping?->oid;
    }

    protected function buildCliCommand(string $template, array $params): string
    {
        foreach ($params as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }
        return $template;
    }

    protected function result(bool $success, string $message = '', array $data = []): array
    {
        return [
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ];
    }
}
