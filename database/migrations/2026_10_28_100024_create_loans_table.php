<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Migration: loans
//  Location: database/migrations/2026_10_28_100024_create_loans_table.php
//
//  Loans of the study (land, property, FF&E ...).
//  The columns are exactly those of the previous CFO Hotels database, so the saved
//  results stay the same (many numbers are kept as text there, on purpose).
//  Links: company_id -> companies (cannot be deleted while used); hospitality_sector_id -> hospitality_sectors (removed together with it).
// ══════════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospitality_sector_id')->nullable()->constrained('hospitality_sectors')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('section_name')->nullable();
            $table->integer('acquisition_id')->nullable();
            $table->integer('ffe_id')->nullable();
            $table->string('loan_type')->nullable();
            $table->string('grace_period')->nullable();
            $table->string('loan_amount')->nullable();
            $table->string('installment_interval')->nullable();
            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();
            $table->string('period')->nullable();
            $table->string('fixedType')->nullable();
            $table->string('base_rate')->nullable();
            $table->string('margin_rate')->nullable();
            $table->string('pricing')->nullable();
            $table->string('duration')->nullable()->comment('tenor');
            $table->string('step_down_rate')->nullable();
            $table->string('step_up_rate')->nullable();
            $table->string('step_up_interval')->nullable();
            $table->string('step_down_interval')->nullable();
            $table->string('borrowing_rate')->nullable();
            $table->string('capitalization_type')->nullable();
            $table->string('margin_interest')->nullable();
            $table->string('loan_interest')->nullable();
            $table->string('min_interest')->nullable();
            $table->string('repayment_duration')->nullable();
            $table->string('installment_amount')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
