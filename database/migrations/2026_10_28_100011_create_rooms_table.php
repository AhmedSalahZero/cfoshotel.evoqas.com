<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Migration: rooms
//  Location: database/migrations/2026_10_28_100011_create_rooms_table.php
//
//  Rooms revenue: the room types of the study with counts, rates and occupancy.
//  The columns are exactly those of the previous CFO Hotels database, so the saved
//  results stay the same (many numbers are kept as text there, on purpose).
//  Links: room_type_id -> room_names (cannot be deleted while used); hospitality_sector_id -> hospitality_sectors (removed together with it).
// ══════════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('room_count')->nullable();
            $table->string('guest_per_room')->nullable();
            $table->foreignId('hospitality_sector_id')->constrained('hospitality_sectors')->cascadeOnDelete();
            $table->string('average_daily_rate')->nullable()->default('0');
            $table->string('chosen_room_currency')->nullable();
            $table->string('average_daily_rate_at_operation_date')->nullable()->default('');
            $table->string('average_daily_rate_annual_escalation_rate')->nullable()->default('');
            $table->string('occupancy_rate_type')->nullable();
            $table->string('average_daily_rate_escalation_rate')->nullable();
            $table->longText('occupancy_rate_per_room')->nullable();
            $table->longText('seasonality')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->foreignId('room_type_id')->nullable()->constrained('room_names');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
