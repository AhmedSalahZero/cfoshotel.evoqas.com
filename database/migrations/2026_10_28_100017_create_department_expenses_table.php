<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Migration: department_expenses
//  Location: database/migrations/2026_10_28_100017_create_department_expenses_table.php
//
//  Direct and general expenses of the study, kept per section (rooms, food, marketing ...).
//  The columns are exactly those of the previous CFO Hotels database, so the saved
//  results stay the same (many numbers are kept as text there, on purpose).
//  Links: company_id -> companies (cannot be deleted while used); hospitality_sector_id -> hospitality_sectors (removed together with it).
// ══════════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('model_name')->nullable();
            $table->foreignId('hospitality_sector_id')->nullable()->constrained('hospitality_sectors')->cascadeOnDelete();
            $table->string('section_name')->nullable();
            $table->string('name')->nullable();
            $table->longText('payload')->nullable();
            $table->longText('manpower_payload')->nullable();
            $table->string('expense_per_night_sold')->nullable();
            $table->string('expense_per_guest')->nullable();
            $table->string('inventory_coverage_days')->nullable();
            $table->string('beginning_inventory_balance_value')->nullable();
            $table->string('cash_payment_percentage')->nullable();
            $table->string('deferred_payment_percentage')->nullable();
            $table->string('due_days')->nullable();
            $table->string('current_net_salary')->nullable();
            $table->string('chosen_currency')->nullable();
            $table->string('escalation_rate')->nullable();
            $table->string('net_salary_at_operation_date')->nullable();
            $table->string('annual_escalation_rate')->nullable();
            $table->string('salary_taxes')->nullable();
            $table->string('social_insurance')->nullable();
            $table->string('night_expense_escalation_rate')->nullable();
            $table->string('chosen_night_expense_currency')->nullable();
            $table->foreignId('company_id')->nullable()->constrained('companies')->restrictOnDelete();
            $table->string('night_annual_escalation_rate')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->string('night_expense_at_operation_date')->nullable();
            $table->string('guest_annual_escalation_rate')->nullable();
            $table->string('guest_expense_at_operation_date')->nullable();
            $table->string('guest_expense_escalation_rate')->nullable();
            $table->string('expense_per_guest_sold')->nullable();
            $table->string('chosen_guest_expense_currency')->nullable();
            $table->string('opex_payment_terms')->nullable();
            $table->string('payment_month')->nullable();
            $table->string('percentage_from_fixed_assets')->nullable();
            $table->string('start_up_cost')->nullable()->default('0');
            $table->string('date')->nullable()->default('0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_expenses');
    }
};
