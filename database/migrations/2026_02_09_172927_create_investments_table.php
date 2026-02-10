<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->decimal('shares', 10, 2); // Number of shares purchased
            $table->decimal('share_price', 10, 2); // Price per share at time of investment
            $table->decimal('current_value', 15, 2)->default(0);
            $table->decimal('total_returns', 15, 2)->default(0);
            $table->decimal('return_percentage', 5, 2)->default(0);
            $table->enum('status', ['active', 'completed', 'withdrawn'])->default('active');
            $table->date('investment_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};