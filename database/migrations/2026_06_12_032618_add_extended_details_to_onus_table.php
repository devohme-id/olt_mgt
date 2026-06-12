<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('onus', function (Blueprint $table) {
            $table->string('name')->nullable()->after('onu_index');
            $table->string('device_type')->nullable()->after('vendor_id');
            $table->string('onu_type')->nullable()->after('model');
            $table->timestamp('deregistered_at')->nullable()->after('registered_at');
            $table->string('deregister_reason')->nullable()->after('deregistered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('onus', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'device_type',
                'onu_type',
                'deregistered_at',
                'deregister_reason'
            ]);
        });
    }
};
