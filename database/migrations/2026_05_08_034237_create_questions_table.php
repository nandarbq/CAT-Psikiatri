<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table): void {
            $table->id();
            $table->string('category')->nullable();
            $table->text('question');
            $table->unsignedInteger('order_no');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'order_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
