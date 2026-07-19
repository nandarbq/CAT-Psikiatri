<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->foreignId('category_id')
                ->nullable()
                ->after('category')
                ->constrained('master_categories')
                ->nullOnDelete();

            $table->index(['category_id', 'is_active']);
        });

        $categoryNames = DB::table('questions')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        foreach ($categoryNames as $name) {
            $cleanName = trim((string) $name);
            if ($cleanName === '') {
                continue;
            }

            $baseSlug = Str::slug($cleanName) ?: 'category';
            $slug = $baseSlug;
            $suffix = 1;
            while (DB::table('master_categories')->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix;
                $suffix++;
            }

            $existing = DB::table('master_categories')->where('name', $cleanName)->first();
            if ($existing === null) {
                DB::table('master_categories')->insert([
                    'name' => $cleanName,
                    'slug' => $slug,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $categories = DB::table('master_categories')->get(['id', 'name']);
        foreach ($categories as $category) {
            DB::table('questions')
                ->where('category', $category->name)
                ->update(['category_id' => $category->id]);
        }
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->dropIndex(['category_id', 'is_active']);
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
