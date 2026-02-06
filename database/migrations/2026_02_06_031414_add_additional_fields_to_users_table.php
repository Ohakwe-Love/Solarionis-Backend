<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'investment_type')) {
                $table->string('investment_type')->nullable()->after('id');
            }
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->string('address')->nullable()->after('date_of_birth');
            }
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            if (!Schema::hasColumn('users', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (!Schema::hasColumn('users', 'zip_code')) {
                $table->string('zip_code')->nullable()->after('state');
            }
            if (!Schema::hasColumn('users', 'is_email_verified')) {
                $table->boolean('is_email_verified')->default(false)->after('email');
            }
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('is_email_verified');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'investment_type', 'first_name', 'last_name', 'phone', 
                'date_of_birth', 'address', 'city', 'state', 'zip_code',
                'is_email_verified', 'email_verified_at'
            ]);
        });
    }
};