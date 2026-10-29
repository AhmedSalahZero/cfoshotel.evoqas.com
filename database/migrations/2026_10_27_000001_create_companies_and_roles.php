<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════════
//  CFO Hotels — Migration: companies and roles
//  Location: database/migrations/2026_10_27_000001_create_companies_and_roles.php
//
//  companies       the customers of the platform. Created only by the super admin.
//                    status               trial | active | suspended (suspended = nobody can sign in)
//                    users_limit          active accounts allowed, the company admin included
//                    subscription_ends_at after this day the company can look but not change (read-only)
//  users           + company_id, role (super_admin | company_admin | user), job_title, created_by
//                    Every account that exists before this migration becomes a super admin
//                    (the only account so far is the administrator made by the seeder).
//
//  The hotel studies (and who each study is shared with) come with the Financial Planning module.
// ══════════════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name_en', 150);
            $table->string('name_ar', 150);
            $table->string('status', 20)->default('trial')->index();
            $table->unsignedSmallInteger('users_limit')->default(5);
            $table->date('subscription_starts_at')->nullable();
            $table->date('subscription_ends_at')->nullable()->index();
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->restrictOnDelete();
            $table->string('role', 20)->default('user')->after('password')->index();
            $table->string('job_title', 100)->nullable()->after('role');
            $table->unsignedBigInteger('created_by')->nullable()->after('is_active');
        });

        DB::table('users')->whereNull('company_id')->update(['role' => 'super_admin']);
    }

    public function down(): void
    {
        $sqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('users', function (Blueprint $table) use ($sqlite) {
            if (! $sqlite) {
                $table->dropConstrainedForeignId('company_id');
            } else {
                $table->dropColumn('company_id');
            }
            $table->dropColumn(['role', 'job_title', 'created_by']);
        });

        Schema::dropIfExists('companies');
    }
};
