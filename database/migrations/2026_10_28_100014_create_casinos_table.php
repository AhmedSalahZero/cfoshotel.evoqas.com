<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Migration: casinos
//  Location: database/migrations/2026_10_28_100014_create_casinos_table.php
//
//  Gaming revenue of the study.
//  The columns are exactly those of the previous CFO Hotels database, so the saved
//  results stay the same (many numbers are kept as text there, on purpose).
//  Links: casino_type_id -> casino_names (cannot be deleted while used); hospitality_sector_id -> hospitality_sectors (removed together with it).
// ══════════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casinos', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('casino_count')->nullable();
            $table->string('f&b_facilities')->nullable();
            $table->bigInteger('casino_cover')->nullable();
            $table->foreignId('hospitality_sector_id')->constrained('hospitality_sectors')->cascadeOnDelete();
            $table->longText('percentage_from_rooms_revenues')->nullable();
            $table->string('collection_policy_type')->nullable();
            $table->string('collection_policy_value');
            $table->string('collection_policy_interval')->nullable();
            $table->longText('cover_per_day')->nullable();
            $table->longText('guest_capture_cover_percentage')->nullable();
            $table->string('charges_value_at_operation_date')->nullable();
            $table->string('charges_value_escalation_rate')->nullable();
            $table->string('charges_value_annual_escalation_rate')->nullable();
            $table->string('chosen_casino_currency')->nullable();
            $table->string('cover_value')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreignId('casino_type_id')->nullable()->constrained('casino_names');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casinos');
    }
};
