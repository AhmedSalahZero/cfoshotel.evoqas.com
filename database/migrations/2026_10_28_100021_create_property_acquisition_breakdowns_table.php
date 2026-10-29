<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Migration: property_acquisition_breakdowns
//  Location: database/migrations/2026_10_28_100021_create_property_acquisition_breakdowns_table.php
//
//  How the property price is split into depreciable items.
//  The columns are exactly those of the previous CFO Hotels database, so the saved
//  results stay the same (many numbers are kept as text there, on purpose).
//  Links: company_id -> companies (cannot be deleted while used); hospitality_sector_id -> hospitality_sectors (removed together with it).
// ══════════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_acquisition_breakdowns', function (Blueprint $table) {
            $table->id();
            $table->string('model_name')->nullable();
            $table->foreignId('hospitality_sector_id')->nullable()->constrained('hospitality_sectors')->cascadeOnDelete();
            $table->string('section_name')->nullable();
            $table->string('name')->nullable();
            $table->string('property_cost_percentage')->nullable()->default('0');
            $table->string('item_amount')->nullable()->default('0');
            $table->string('depreciation_duration')->nullable();
            $table->foreignId('company_id')->nullable()->constrained('companies')->restrictOnDelete();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_acquisition_breakdowns');
    }
};
