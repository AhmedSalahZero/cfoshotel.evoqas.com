<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Migration: hospitality_sector_shares
//  Location: database/migrations/2026_10_28_100010_create_hospitality_sector_shares_table.php
//
//  NEW (not in the old database): a study shared by its owner with a colleague of the same company (view or edit).
//  Same idea as the sharing of studies in the Zavero application.
// ══════════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospitality_sector_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospitality_sector_id')->constrained('hospitality_sectors')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('can_edit')->default(false);
            $table->timestamps();
            $table->unique(['hospitality_sector_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospitality_sector_shares');
    }
};
