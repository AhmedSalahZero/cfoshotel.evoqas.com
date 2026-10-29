<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Migration: acquisitions
//  Location: database/migrations/2026_10_28_100019_create_acquisitions_table.php
//
//  Land purchase and construction costs of the study.
//  The columns are exactly those of the previous CFO Hotels database, so the saved
//  results stay the same (many numbers are kept as text there, on purpose).
//  Links: company_id -> companies (cannot be deleted while used); hospitality_sector_id -> hospitality_sectors (removed together with it).
// ══════════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acquisitions', function (Blueprint $table) {
            $table->id();
            $table->string('land_installment_count')->nullable();
            $table->string('land_after_month')->nullable();
            $table->string('second_land_down_payment_percentage')->nullable();
            $table->string('first_land_down_payment_percentage')->nullable();
            $table->string('land_payment_method')->nullable();
            $table->string('land_contingency_rate')->nullable();
            $table->string('land_purchase_cost')->nullable();
            $table->string('purchase_date')->nullable();
            $table->string('land_equity_funding_rate')->nullable()->default('100');
            $table->string('installment_interval')->nullable();
            $table->string('collection_policy_interval')->nullable();
            $table->string('collection_policy_type')->nullable();
            $table->string('collection_policy_value')->nullable();
            $table->foreignId('hospitality_sector_id')->nullable()->constrained('hospitality_sectors')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->restrictOnDelete();
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->string('has_land_section')->nullable();
            $table->string('hard_construction_contingency_rate')->nullable();
            $table->string('hard_construction_cost')->nullable();
            $table->string('has_hard_construction_cost_section')->nullable();
            $table->string('hard_construction_duration')->nullable();
            $table->string('hard_construction_start_date')->nullable();
            $table->string('hard_construction_end_date')->nullable();
            $table->string('hard_execution_method')->nullable();
            $table->string('hard_down_payment')->nullable();
            $table->string('hard_balance_rate_one')->nullable();
            $table->string('hard_balance_rate_two')->nullable();
            $table->string('hard_due_one')->nullable();
            $table->string('hard_due_two')->nullable();
            $table->string('hard_equity_funding')->nullable();
            $table->string('soft_due_two')->nullable();
            $table->string('soft_balance_rate_two')->nullable();
            $table->string('soft_due_one')->nullable();
            $table->string('soft_balance_rate_one')->nullable();
            $table->string('soft_construction_start_date')->nullable();
            $table->string('soft_down_payment')->nullable();
            $table->string('soft_construction_end_date')->nullable();
            $table->string('soft_execution_method')->nullable();
            $table->string('soft_construction_contingency_rate')->nullable();
            $table->string('soft_construction_cost')->nullable();
            $table->string('has_soft_construction_cost_section')->nullable();
            $table->string('soft_construction_duration')->nullable()->default('0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acquisitions');
    }
};
