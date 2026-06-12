<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Regions
        Schema::create('regions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Sites
        Schema::create('sites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('region_id');
            $table->string('name');
            $table->string('code')->unique();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
            $table->foreign('region_id')->references('id')->on('regions')->cascadeOnDelete();
        });

        // Vendors
        Schema::create('vendors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('enterprise_oid')->nullable();
            $table->jsonb('default_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Device Models
        Schema::create('device_models', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_id');
            $table->string('name');
            $table->string('model_number');
            $table->string('device_type')->default('epon'); // epon, gpon
            $table->integer('max_pon_ports')->default(4);
            $table->integer('max_onus_per_port')->default(64);
            $table->jsonb('capabilities')->nullable();
            $table->timestamps();
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->unique(['vendor_id', 'model_number']);
        });

        // Firmware Profiles
        Schema::create('firmware_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('device_model_id');
            $table->string('version');
            $table->string('hardware_version')->nullable();
            $table->jsonb('supported_features')->nullable();
            $table->jsonb('cli_command_set')->nullable();
            $table->jsonb('oid_overrides')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('device_model_id')->references('id')->on('device_models')->cascadeOnDelete();
            $table->unique(['device_model_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firmware_profiles');
        Schema::dropIfExists('device_models');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('regions');
    }
};
