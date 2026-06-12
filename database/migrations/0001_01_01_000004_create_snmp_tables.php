<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MIB Files
        Schema::create('mib_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_id');
            $table->string('filename');
            $table->string('mib_name');
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->timestamps();
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
        });

        // OID Definitions
        Schema::create('oid_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('mib_file_id')->nullable();
            $table->string('oid');
            $table->string('name');
            $table->string('data_type')->nullable(); // INTEGER, OCTET STRING, Counter32, etc.
            $table->string('access_mode')->default('read-only'); // read-only, read-write
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // system, interface, optical, traffic
            $table->timestamps();
            $table->foreign('mib_file_id')->references('id')->on('mib_files')->nullOnDelete();
            $table->index('oid');
        });

        // Vendor OID Mappings — the core dynamic mapping table
        Schema::create('vendor_oid_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_id');
            $table->uuid('device_model_id')->nullable();
            $table->uuid('firmware_profile_id')->nullable();
            $table->string('metric_key'); // cpu_usage, memory_usage, onu_status, etc.
            $table->string('oid');
            $table->string('data_type')->default('integer'); // integer, string, counter, gauge, mac, optical_power
            $table->string('parser_class')->nullable(); // Custom parser class
            $table->string('unit')->nullable(); // %, dBm, °C, bps, bytes
            $table->decimal('multiplier', 10, 4)->default(1);
            $table->jsonb('transform_rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->foreign('device_model_id')->references('id')->on('device_models')->nullOnDelete();
            $table->foreign('firmware_profile_id')->references('id')->on('firmware_profiles')->nullOnDelete();
            $table->index(['vendor_id', 'device_model_id', 'metric_key'], 'idx_vendor_oid_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_oid_mappings');
        Schema::dropIfExists('oid_definitions');
        Schema::dropIfExists('mib_files');
    }
};
