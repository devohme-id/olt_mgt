<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Alarm Rules
        Schema::create('alarm_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('metric_key'); // cpu_usage, temperature, rx_power_dbm, etc.
            $table->string('severity')->default('warning'); // critical, major, minor, warning
            $table->string('condition_operator'); // gt, gte, lt, lte, eq, neq
            $table->decimal('threshold_value', 12, 4);
            $table->integer('duration_seconds')->default(0);
            $table->integer('cooldown_seconds')->default(300);
            $table->string('device_type')->default('any'); // olt, onu, any
            $table->boolean('is_active')->default(true);
            $table->jsonb('notification_channels')->nullable(); // ["telegram"]
            $table->timestamps();
        });

        // Alarms
        Schema::create('alarms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('alarm_rule_id')->nullable();
            $table->uuid('device_id'); // OLT or ONU UUID
            $table->string('device_type'); // olt, onu
            $table->string('severity'); // critical, major, minor, warning
            $table->string('status')->default('active'); // active, acknowledged, resolved
            $table->text('message');
            $table->jsonb('context')->nullable(); // Additional alarm data
            $table->uuid('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->uuid('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->foreign('alarm_rule_id')->references('id')->on('alarm_rules')->nullOnDelete();
            $table->foreign('acknowledged_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['severity', 'status']);
            $table->index(['device_id', 'device_type']);
        });

        // Alarm Histories
        Schema::create('alarm_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('alarm_id');
            $table->string('action'); // created, acknowledged, assigned, resolved, escalated
            $table->uuid('user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('alarm_id')->references('id')->on('alarms')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // ONU Events
        Schema::create('onu_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('onu_id');
            $table->string('event_type'); // status_change, registration, deregistration, optical_change
            $table->string('severity')->default('info'); // info, warning, critical
            $table->jsonb('data')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('onu_id')->references('id')->on('onus')->cascadeOnDelete();
            $table->index(['onu_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onu_events');
        Schema::dropIfExists('alarm_histories');
        Schema::dropIfExists('alarms');
        Schema::dropIfExists('alarm_rules');
    }
};
