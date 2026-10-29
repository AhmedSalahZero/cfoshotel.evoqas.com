<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Migration: meetings
//  Location: database/migrations/2026_10_28_100015_create_meetings_table.php
//
//  Meeting and events revenue of the study.
//  The columns are exactly those of the previous CFO Hotels database, so the saved
//  results stay the same (many numbers are kept as text there, on purpose).
//  Links: meeting_type_id -> meeting_names (cannot be deleted while used); hospitality_sector_id -> hospitality_sectors (removed together with it).
// ══════════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('collection_policy_type')->nullable();
            $table->string('collection_policy_value');
            $table->string('collection_policy_interval')->nullable();
            $table->bigInteger('meeting_count')->nullable();
            $table->bigInteger('meeting_cover')->nullable();
            $table->foreignId('hospitality_sector_id')->constrained('hospitality_sectors')->cascadeOnDelete();
            $table->string('f&b_facilities')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->longText('guest_capture_cover_percentage')->nullable();
            $table->longText('guest_seasonality')->nullable();
            $table->longText('rent_seasonality')->nullable();
            $table->string('charges_value_escalation_rate')->nullable();
            $table->string('chosen_meeting_currency')->nullable();
            $table->string('cover_value')->nullable();
            $table->string('charges_value_per_guest')->nullable();
            $table->string('charges_value_at_operation_date')->nullable();
            $table->string('charges_value_annual_escalation_rate')->nullable();
            $table->longText('percentage_from_f_and_b_revenues')->nullable();
            $table->foreignId('meeting_type_id')->nullable()->constrained('meeting_names');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
