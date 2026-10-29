<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Migration: saleschannels
//  Location: database/migrations/2026_10_28_100012_create_saleschannels_table.php
//
//  Sales channels of the study (direct, online agents ...) with their shares and discounts.
//  The columns are exactly those of the previous CFO Hotels database, so the saved
//  results stay the same (many numbers are kept as text there, on purpose).
//  Links: hospitality_sector_id -> hospitality_sectors (removed together with it).
// ══════════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saleschannels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('hospitality_sector_id')->constrained('hospitality_sectors')->cascadeOnDelete();
            $table->longText('percentages')->nullable();
            $table->longText('revenue_share_percentage')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->longText('discount_or_commission')->nullable();
            $table->string('collection_policy_type')->nullable();
            $table->string('collection_policy_value')->nullable();
            $table->string('collection_policy_interval')->nullable()->default('YES');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saleschannels');
    }
};
