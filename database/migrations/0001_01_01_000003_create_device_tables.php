<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // OLTs
        Schema::create('olts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_id');
            $table->uuid('device_model_id');
            $table->uuid('site_id');
            $table->uuid('firmware_profile_id')->nullable();
            $table->string('name');
            $table->string('hostname')->nullable();
            $table->ipAddress('ip_address');
            $table->integer('snmp_port')->default(161);
            $table->string('snmp_version')->default('v2c'); // v1, v2c, v3
            $table->text('snmp_community_read')->nullable();
            $table->text('snmp_community_write')->nullable();
            $table->string('snmp_v3_username')->nullable();
            $table->text('snmp_v3_auth_pass')->nullable();
            $table->string('snmp_v3_auth_proto')->nullable(); // MD5, SHA
            $table->text('snmp_v3_priv_pass')->nullable();
            $table->string('snmp_v3_priv_proto')->nullable(); // DES, AES
            $table->string('ssh_username')->nullable();
            $table->text('ssh_password')->nullable();
            $table->integer('ssh_port')->default(22);
            $table->string('telnet_username')->nullable();
            $table->text('telnet_password')->nullable();
            $table->integer('telnet_port')->default(23);
            $table->string('status')->default('unknown'); // online, offline, maintenance, unknown
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->jsonb('system_info')->nullable();
            $table->integer('polling_interval')->default(60);
            $table->boolean('is_polling_enabled')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('vendor_id')->references('id')->on('vendors');
            $table->foreign('device_model_id')->references('id')->on('device_models');
            $table->foreign('site_id')->references('id')->on('sites');
            $table->foreign('firmware_profile_id')->references('id')->on('firmware_profiles')->nullOnDelete();
            $table->index('ip_address');
            $table->index('status');
        });

        // PON Ports
        Schema::create('pon_ports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('olt_id');
            $table->integer('port_index');
            $table->string('port_name');
            $table->string('port_type')->default('epon'); // epon, gpon
            $table->string('admin_status')->default('up'); // up, down
            $table->string('oper_status')->default('unknown'); // up, down, unknown
            $table->bigInteger('if_index')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->foreign('olt_id')->references('id')->on('olts')->cascadeOnDelete();
            $table->unique(['olt_id', 'port_index']);
        });

        // Service Profiles
        Schema::create('service_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->integer('upstream_bw_kbps')->default(0);
            $table->integer('downstream_bw_kbps')->default(0);
            $table->string('service_type')->default('internet'); // internet, voip, iptv, management
            $table->jsonb('vlan_config')->nullable();
            $table->jsonb('pppoe_config')->nullable();
            $table->jsonb('qos_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ONUs
        Schema::create('onus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('olt_id');
            $table->uuid('pon_port_id');
            $table->uuid('service_profile_id')->nullable();
            $table->string('onu_index');
            $table->string('serial_number')->nullable();
            $table->string('mac_address')->nullable();
            $table->string('vendor_id')->nullable();
            $table->string('model')->nullable();
            $table->string('firmware_version')->nullable();
            $table->string('status')->default('unknown'); // online, offline, los, disabled, unknown
            $table->string('auth_status')->default('pending'); // authorized, pending, denied
            $table->string('description')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_id')->nullable();
            $table->integer('distance_meters')->nullable();
            $table->decimal('rx_power_dbm', 8, 2)->nullable();
            $table->decimal('tx_power_dbm', 8, 2)->nullable();
            $table->decimal('olt_rx_power_dbm', 8, 2)->nullable();
            $table->decimal('optical_loss_db', 8, 2)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('olt_id')->references('id')->on('olts')->cascadeOnDelete();
            $table->foreign('pon_port_id')->references('id')->on('pon_ports')->cascadeOnDelete();
            $table->foreign('service_profile_id')->references('id')->on('service_profiles')->nullOnDelete();
            $table->index(['olt_id', 'pon_port_id']);
            $table->index('serial_number');
            $table->index('mac_address');
            $table->index('status');
            $table->index('customer_id');
        });

        // VLAN Configs
        Schema::create('vlan_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('onu_id');
            $table->integer('vlan_id');
            $table->string('vlan_mode')->default('access'); // access, trunk, hybrid
            $table->string('service_type')->default('internet');
            $table->integer('cos_priority')->default(0);
            $table->timestamps();
            $table->foreign('onu_id')->references('id')->on('onus')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vlan_configs');
        Schema::dropIfExists('onus');
        Schema::dropIfExists('service_profiles');
        Schema::dropIfExists('pon_ports');
        Schema::dropIfExists('olts');
    }
};
