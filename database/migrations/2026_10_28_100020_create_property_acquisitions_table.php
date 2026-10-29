<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Migration: property_acquisitions
//  Location: database/migrations/2026_10_28_100020_create_property_acquisitions_table.php
//
//  Property purchase of the study.
//  The columns are exactly those of the previous CFO Hotels database, so the saved
//  results stay the same (many numbers are kept as text there, on purpose).
//  Links: company_id -> companies (cannot be deleted while used); hospitality_sector_id -> hospitality_sectors (removed together with it).
// ══════════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_acquisitions', function (Blueprint $table) {
            $table->id();
            $table->string('property_installment_count')->nullable();
            $table->string('property_after_month')->nullable();
            $table->string('second_property_down_payment_percentage')->nullable();
            $table->string('first_property_down_payment_percentage')->nullable();
            $table->string('property_payment_method')->nullable();
            $table->string('property_contingency_rate')->nullable();
            $table->string('property_purchase_cost')->nullable();
            $table->string('purchase_date')->nullable();
            $table->string('property_equity_funding_rate')->nullable()->default('100');
            $table->string('installment_interval')->nullable();
            $table->string('collection_policy_interval')->nullable();
            $table->string('collection_policy_type')->nullable();
            $table->string('collection_policy_value')->nullable();
            $table->foreignId('hospitality_sector_id')->nullable()->constrained('hospitality_sectors')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->restrictOnDelete();
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->string('has_land_section')->nullable();
            $table->string('replacement_cost_name')->nullable();
            $table->string('replacement_cost_rate')->nullable();
            $table->string('replacement_interval')->nullable();
            $table->string('ffe_replacement_interval')->nullable();
            $table->string('ffe_replacement_cost_rate')->nullable();
            $table->string('ffe_replacement_cost_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_acquisitions');
    }
};
