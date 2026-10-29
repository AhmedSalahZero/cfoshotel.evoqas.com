<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Migration: meeting_names
//  Location: database/migrations/2026_10_28_100007_create_meeting_names_table.php
//
//  The meeting space types of a company. One list per company.
//  The columns are exactly those of the previous CFO Hotels database, so the saved
//  results stay the same (many numbers are kept as text there, on purpose).
//  Links: company_id -> companies (cannot be deleted while used).
// ══════════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_names', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->tinyInteger('is_active')->default(1);
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_names');
    }
};
