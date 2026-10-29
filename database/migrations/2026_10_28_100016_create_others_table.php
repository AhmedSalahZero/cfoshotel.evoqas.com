<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Migration: others
//  Location: database/migrations/2026_10_28_100016_create_others_table.php
//
//  Other revenue of the study.
//  The columns are exactly those of the previous CFO Hotels database, so the saved
//  results stay the same (many numbers are kept as text there, on purpose).
//  Links: other_type_id -> other_names (cannot be deleted while used); hospitality_sector_id -> hospitality_sectors (removed together with it).
// ══════════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('others', function (Blueprint $table) {
            $table->id();
            $table->string('collection_policy_type')->nullable();
            $table->string('collection_policy_value');
            $table->string('collection_policy_interval')->nullable();
            $table->string('f&b_facilities')->nullable();
            $table->bigInteger('other_count')->nullable();
            $table->string('chosen_other_currency')->nullable();
            $table->foreignId('hospitality_sector_id')->constrained('hospitality_sectors')->cascadeOnDelete();
            $table->string('charges_per_guest_escalation_rate')->nullable();
            $table->string('charges_per_guest')->nullable();
            $table->string('charges_per_guest_annual_escalation_rate')->nullable();
            $table->string('charges_per_guest_at_operation_date')->nullable();
            $table->longText('guest_capture_cover_percentage')->nullable();
            $table->longText('percentage_from_rooms_revenues')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreignId('other_type_id')->nullable()->constrained('other_names');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('others');
    }
};
