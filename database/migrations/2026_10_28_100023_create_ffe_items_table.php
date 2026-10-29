<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Migration: ffe_items
//  Location: database/migrations/2026_10_28_100023_create_ffe_items_table.php
//
//  The furniture, fixtures and equipment items of the study.
//  The columns are exactly those of the previous CFO Hotels database, so the saved
//  results stay the same (many numbers are kept as text there, on purpose).
//  Links: company_id -> companies (cannot be deleted while used); hospitality_sector_id -> hospitality_sectors (removed together with it).
// ══════════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ffe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospitality_sector_id')->nullable()->constrained('hospitality_sectors')->cascadeOnDelete();
            $table->bigInteger('ffe_id')->nullable();
            $table->string('section_name')->nullable();
            $table->string('model_name')->nullable();
            $table->string('name')->nullable();
            $table->string('depreciation_duration')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->string('item_cost')->nullable();
            $table->string('contingency_rate')->nullable();
            $table->foreignId('company_id')->nullable()->constrained('companies')->restrictOnDelete();
            $table->string('currency_name')->nullable();
            $table->string('replacement_cost_rate')->nullable();
            $table->string('replacement_interval')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ffe_items');
    }
};
