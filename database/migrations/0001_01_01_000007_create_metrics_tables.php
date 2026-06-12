<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // OLT Metrics
        Schema::create('olt_metrics', function (Blueprint $table) {
            $table->timestampTz('time');
            $table->uuid('olt_id');
            $table->float('cpu_usage')->nullable();
            $table->float('memory_usage')->nullable();
            $table->float('temperature')->nullable();
            $table->integer('fan_speed')->nullable();
            $table->smallInteger('psu_status')->nullable();
            $table->bigInteger('uptime_seconds')->nullable();
            $table->bigInteger('total_rx_bps')->nullable();
            $table->bigInteger('total_tx_bps')->nullable();
            $table->bigInteger('total_rx_bytes')->nullable();
            $table->bigInteger('total_tx_bytes')->nullable();
            $table->integer('online_onus')->nullable();
            $table->integer('offline_onus')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->index(['olt_id', 'time']);
        });

        // ONU Metrics
        Schema::create('onu_metrics', function (Blueprint $table) {
            $table->timestampTz('time');
            $table->uuid('onu_id');
            $table->uuid('olt_id');
            $table->float('rx_power_dbm')->nullable();
            $table->float('tx_power_dbm')->nullable();
            $table->float('olt_rx_power_dbm')->nullable();
            $table->float('optical_loss_db')->nullable();
            $table->bigInteger('rx_bps')->nullable();
            $table->bigInteger('tx_bps')->nullable();
            $table->bigInteger('rx_bytes')->nullable();
            $table->bigInteger('tx_bytes')->nullable();
            $table->integer('distance_meters')->nullable();
            $table->smallInteger('status')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->index(['onu_id', 'time']);
            $table->index(['olt_id', 'time']);
        });

        // PON Port Metrics
        Schema::create('pon_port_metrics', function (Blueprint $table) {
            $table->timestampTz('time');
            $table->uuid('pon_port_id');
            $table->uuid('olt_id');
            $table->bigInteger('rx_bps')->nullable();
            $table->bigInteger('tx_bps')->nullable();
            $table->float('utilization_pct')->nullable();
            $table->integer('onu_count')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->index(['pon_port_id', 'time']);
        });

        // ONU Optical History
        Schema::create('onu_optical_history', function (Blueprint $table) {
            $table->timestampTz('time');
            $table->uuid('onu_id');
            $table->float('rx_power_dbm')->nullable();
            $table->float('tx_power_dbm')->nullable();
            $table->float('olt_rx_power_dbm')->nullable();
            $table->float('optical_loss_db')->nullable();
            $table->float('temperature')->nullable();
            $table->index(['onu_id', 'time']);
        });

        // Convert to TimescaleDB hypertables if using PostgreSQL with TimescaleDB
        if (config('database.default') === 'pgsql') {
            try {
                DB::statement("CREATE EXTENSION IF NOT EXISTS timescaledb CASCADE");

                DB::statement("SELECT create_hypertable('olt_metrics', 'time', if_not_exists => TRUE)");
                DB::statement("SELECT create_hypertable('onu_metrics', 'time', if_not_exists => TRUE)");
                DB::statement("SELECT create_hypertable('pon_port_metrics', 'time', if_not_exists => TRUE)");
                DB::statement("SELECT create_hypertable('onu_optical_history', 'time', if_not_exists => TRUE)");

                // Compression policies (compress after 2 days)
                DB::statement("ALTER TABLE olt_metrics SET (timescaledb.compress, timescaledb.compress_segmentby = 'olt_id')");
                DB::statement("ALTER TABLE onu_metrics SET (timescaledb.compress, timescaledb.compress_segmentby = 'onu_id')");
                DB::statement("ALTER TABLE pon_port_metrics SET (timescaledb.compress, timescaledb.compress_segmentby = 'pon_port_id')");
                DB::statement("ALTER TABLE onu_optical_history SET (timescaledb.compress, timescaledb.compress_segmentby = 'onu_id')");

                DB::statement("SELECT add_compression_policy('olt_metrics', INTERVAL '2 days')");
                DB::statement("SELECT add_compression_policy('onu_metrics', INTERVAL '2 days')");
                DB::statement("SELECT add_compression_policy('pon_port_metrics', INTERVAL '2 days')");
                DB::statement("SELECT add_compression_policy('onu_optical_history', INTERVAL '1 day')");

                // Retention policies
                DB::statement("SELECT add_retention_policy('olt_metrics', INTERVAL '90 days')");
                DB::statement("SELECT add_retention_policy('onu_metrics', INTERVAL '90 days')");
                DB::statement("SELECT add_retention_policy('pon_port_metrics', INTERVAL '90 days')");
                DB::statement("SELECT add_retention_policy('onu_optical_history', INTERVAL '30 days')");
            } catch (\Exception $e) {
                // TimescaleDB not available, tables will work as regular PostgreSQL tables
                logger()->warning('TimescaleDB not available, using regular tables: ' . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('onu_optical_history');
        Schema::dropIfExists('pon_port_metrics');
        Schema::dropIfExists('onu_metrics');
        Schema::dropIfExists('olt_metrics');
    }
};
