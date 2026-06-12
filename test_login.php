<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Domain\Device\Models\Olt;
use App\Infrastructure\Cli\CliExecutor;

$olt = Olt::first();
$cli = new CliExecutor();

try {
    echo "Executing enable command...\n";
    $output = $cli->execute($olt, "enable");
    echo "Output:\n" . $output . "\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
