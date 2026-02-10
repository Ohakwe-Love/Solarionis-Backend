<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('investment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['investment', 'dividend', 'withdrawal', 'deposit', 'fee']);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed');
            $table->string('reference_number')->unique(); // Idempotency key
            $table->timestamp('occurred_at'); // Cleaner than created_at for business logic
            $table->json('metadata')->nullable(); // Store extra info (offering_id, etc.)
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'type', 'status']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};