<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->string('nik')->unique();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('code');
            $table->string('name');
            $table->date('birth_date');
            $table->enum('gender', ['male', 'female']);
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('login_token')->nullable();
            $table->boolean('is_finished')->default(false);
            $table->timestamps();

            $table->unique(['session_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
