<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->decimal('share_price', 10, 2);
            $table->decimal('min_investment', 10, 2)->default(100);
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->enum('status', ['open', 'closed', 'paused'])->default('open');
            $table->integer('total_shares')->nullable();
            $table->decimal('shares_sold')->default(0);
            $table->timestamps();

            // Index for performance
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offerings');
    }
};