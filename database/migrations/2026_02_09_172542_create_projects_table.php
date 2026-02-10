<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['solar', 'wind', 'hydro', 'battery']);
            $table->text('description');
            $table->string('location');
            $table->string('location_state');
            $table->decimal('capacity', 10, 2); // in MW
            $table->decimal('total_cost', 15, 2);
            $table->decimal('funding_goal', 15, 2);
            $table->decimal('current_funding', 15, 2)->default(0);
            $table->decimal('expected_annual_return', 5, 2); // percentage
            $table->integer('duration_months')->default(120); // 10 years default
            $table->enum('status', ['draft', 'funding', 'active', 'completed', 'paused'])->default('draft');
            $table->integer('completion_percentage')->default(0);
            $table->date('project_start_date')->nullable();
            $table->date('expected_completion_date')->nullable();
            $table->string('image_url')->nullable();
            $table->json('highlights')->nullable();
            $table->json('documents')->nullable();
            $table->timestamps();

            $table->index(['status', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};