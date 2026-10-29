<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Migration: foods
//  Location: database/migrations/2026_10_28_100013_create_foods_table.php
//
//  Food and beverage revenue of the study.
//  The columns are exactly those of the previous CFO Hotels database, so the saved
//  results stay the same (many numbers are kept as text there, on purpose).
//  Links: food_type_id -> food_names (cannot be deleted while used); hospitality_sector_id -> hospitality_sectors (removed together with it).
// ══════════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('food_count')->nullable();
            $table->bigInteger('food_cover')->nullable();
            $table->string('total_daily_cover_count')->nullable()->default('0');
            $table->string('f&b_facilities')->nullable();
            $table->string('cover_value')->nullable()->default('0');
            $table->string('estimation_date')->nullable();
            $table->string('chosen_food_currency')->nullable();
            $table->string('cover_value_escalation_rate')->nullable();
            $table->string('cover_value_at_operation_date')->nullable();
            $table->string('cover_value_annual_escalation_rate')->nullable();
            $table->longText('guest_capture_cover_percentage')->nullable();
            $table->longText('cover_per_day')->nullable();
            $table->foreignId('hospitality_sector_id')->constrained('hospitality_sectors')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('updated_at')->nullable();
            $table->longText('meal_per_guest')->nullable();
            $table->longText('percentage_from_rooms_revenues')->nullable();
            $table->string('collection_policy_type')->nullable();
            $table->string('collection_policy_value');
            $table->string('collection_policy_interval')->nullable();
            $table->foreignId('food_type_id')->nullable()->constrained('food_names');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foods');
    }
};
