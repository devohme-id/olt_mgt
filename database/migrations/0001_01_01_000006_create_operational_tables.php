<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->nullable();
            $table->string('action'); // create, update, delete, login, logout, etc.
            $table->string('module'); // olt, onu, user, alarm, system, api, snmp
            $table->string('entity_type')->nullable(); // Model class name
            $table->uuid('entity_id')->nullable();
            $table->jsonb('before_state')->nullable();
            $table->jsonb('after_state')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('module');
        });

        // Report Schedules
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('report_type'); // availability, traffic, alarm, capacity, onu_growth
            $table->string('frequency'); // daily, weekly, monthly
            $table->string('format')->default('pdf'); // pdf, excel, csv
            $table->jsonb('parameters')->nullable(); // date_range, filters, etc.
            $table->jsonb('recipients')->nullable(); // email, telegram
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Report Histories
        Schema::create('report_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('report_schedule_id')->nullable();
            $table->string('report_type');
            $table->string('status'); // pending, generating, completed, failed
            $table->string('file_path')->nullable();
            $table->integer('file_size')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->foreign('report_schedule_id')->references('id')->on('report_schedules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_histories');
        Schema::dropIfExists('report_schedules');
        Schema::dropIfExists('audit_logs');
    }
};
