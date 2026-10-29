<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Migration: ffe
//  Location: database/migrations/2026_10_28_100022_create_ffe_table.php
//
//  Furniture, fixtures and equipment: how they are paid for.
//  The columns are exactly those of the previous CFO Hotels database, so the saved
//  results stay the same (many numbers are kept as text there, on purpose).
//  Links: company_id -> companies (cannot be deleted while used); hospitality_sector_id -> hospitality_sectors (removed together with it).
// ══════════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ffe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospitality_sector_id')->nullable()->constrained('hospitality_sectors')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->restrictOnDelete();
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->string('duration')->nullable();
            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();
            $table->string('execution_method')->nullable();
            $table->string('down_payment')->nullable();
            $table->string('balance_rate_one')->nullable();
            $table->string('balance_rate_two')->nullable();
            $table->string('due_one')->nullable();
            $table->string('due_two')->nullable();
            $table->string('ffe_equity_funding')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ffe');
    }
};
