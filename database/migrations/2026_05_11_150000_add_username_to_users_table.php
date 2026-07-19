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
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->after('name');
            }
        });

        $users = DB::table('users')
            ->select(['id', 'email', 'username'])
            ->orderBy('id')
            ->get();

        foreach ($users as $user) {
            $currentUsername = trim((string) ($user->username ?? ''));
            if ($currentUsername !== '') {
                continue;
            }

            $email = trim((string) ($user->email ?? ''));
            $base = Str::lower(Str::before($email, '@'));
            $base = preg_replace('/[^a-z0-9._-]/', '', $base ?? '');
            if ($base === null || $base === '') {
                $base = 'admin'.$user->id;
            }

            $candidate = $base;
            $suffix = 1;
            while (
                DB::table('users')
                    ->where('username', $candidate)
                    ->where('id', '!=', $user->id)
                    ->exists()
            ) {
                $candidate = $base.$suffix;
                $suffix++;
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update(['username' => $candidate]);
        }

        if (! $this->indexExists('users', 'users_username_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique('username');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if ($this->indexExists('users', 'users_username_unique')) {
                $table->dropUnique('users_username_unique');
            }

            if (Schema::hasColumn('users', 'username')) {
                $table->dropColumn('username');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            if (DB::getDriverName() === 'sqlite') {
                $indexes = DB::select("PRAGMA index_list('{$table}')");
                foreach ($indexes as $index) {
                    if ((string) ($index->name ?? '') === $indexName) {
                        return true;
                    }
                }

                return false;
            }

            $database = DB::getDatabaseName();
            $result = DB::selectOne(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
                [$database, $table, $indexName],
            );

            return $result !== null;
        } catch (\Throwable) {
            return false;
        }
    }
};
