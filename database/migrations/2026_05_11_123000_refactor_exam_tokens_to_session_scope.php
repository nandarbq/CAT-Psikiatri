<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('exam_tokens', 'participant_id')) {
            return;
        }

        DB::transaction(function (): void {
            $latestTokenIds = DB::table('exam_tokens')
                ->selectRaw('MAX(id) as id')
                ->groupBy('session_id')
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            if ($latestTokenIds !== []) {
                DB::table('exam_tokens')->whereNotIn('id', $latestTokenIds)->delete();
            }
        });

        if ($this->isSqlite()) {
            $this->rebuildExamTokensTableForSqliteUp();

            return;
        }

        Schema::table('exam_tokens', function (Blueprint $table): void {
            if (! $this->indexExists('exam_tokens', 'exam_tokens_session_id_tmp_index')) {
                // Temporary index to ensure FK on session_id still has backing index
                // when composite unique(session_id, participant_id) is dropped.
                $table->index('session_id', 'exam_tokens_session_id_tmp_index');
            }

            if ($this->foreignKeyExists('exam_tokens', 'exam_tokens_participant_id_foreign')) {
                $table->dropForeign('exam_tokens_participant_id_foreign');
            }

            if ($this->indexExists('exam_tokens', 'exam_tokens_session_id_participant_id_unique')) {
                $table->dropUnique('exam_tokens_session_id_participant_id_unique');
            }

            if ($this->indexExists('exam_tokens', 'exam_tokens_participant_id_is_active_index')) {
                $table->dropIndex('exam_tokens_participant_id_is_active_index');
            }

            if (Schema::hasColumn('exam_tokens', 'participant_id')) {
                $table->dropColumn('participant_id');
            }

            if (! $this->indexExists('exam_tokens', 'exam_tokens_session_id_unique')) {
                $table->unique('session_id', 'exam_tokens_session_id_unique');
            }

            if (! $this->indexExists('exam_tokens', 'exam_tokens_session_id_is_active_index')) {
                $table->index(['session_id', 'is_active'], 'exam_tokens_session_id_is_active_index');
            }

            if ($this->indexExists('exam_tokens', 'exam_tokens_session_id_tmp_index')) {
                $table->dropIndex('exam_tokens_session_id_tmp_index');
            }
        });
    }

    public function down(): void
    {
        if ($this->isSqlite()) {
            $this->rebuildExamTokensTableForSqliteDown();

            return;
        }

        Schema::table('exam_tokens', function (Blueprint $table): void {
            if ($this->indexExists('exam_tokens', 'exam_tokens_session_id_unique')) {
                $table->dropUnique('exam_tokens_session_id_unique');
            }

            if ($this->indexExists('exam_tokens', 'exam_tokens_session_id_is_active_index')) {
                $table->dropIndex('exam_tokens_session_id_is_active_index');
            }

            if (! Schema::hasColumn('exam_tokens', 'participant_id')) {
                $table->foreignId('participant_id')->nullable()->after('session_id');
            }

            if (! $this->foreignKeyExists('exam_tokens', 'exam_tokens_participant_id_foreign')) {
                $table->foreign('participant_id')->references('id')->on('participants')->cascadeOnDelete();
            }

            if (! $this->indexExists('exam_tokens', 'exam_tokens_session_id_participant_id_unique')) {
                $table->unique(['session_id', 'participant_id'], 'exam_tokens_session_id_participant_id_unique');
            }

            if (! $this->indexExists('exam_tokens', 'exam_tokens_participant_id_is_active_index')) {
                $table->index(['participant_id', 'is_active'], 'exam_tokens_participant_id_is_active_index');
            }
        });
    }

    private function rebuildExamTokensTableForSqliteUp(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::create('exam_tokens_new', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->string('token', 80)->unique();
            $table->boolean('is_active')->default(true);
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('last_used_at')->nullable();
            $table->timestamps();

            $table->unique('session_id', 'exam_tokens_session_id_unique');
            $table->index(['session_id', 'is_active'], 'exam_tokens_session_id_is_active_index');
        });

        DB::statement('
            INSERT INTO exam_tokens_new (id, session_id, token, is_active, expires_at, last_used_at, created_at, updated_at)
            SELECT id, session_id, token, is_active, expires_at, last_used_at, created_at, updated_at
            FROM exam_tokens
        ');

        Schema::drop('exam_tokens');
        Schema::rename('exam_tokens_new', 'exam_tokens');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    private function rebuildExamTokensTableForSqliteDown(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::create('exam_tokens_new', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->foreignId('participant_id')->nullable()->constrained('participants')->cascadeOnDelete();
            $table->string('token', 80)->unique();
            $table->boolean('is_active')->default(true);
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'participant_id'], 'exam_tokens_session_id_participant_id_unique');
            $table->index(['participant_id', 'is_active'], 'exam_tokens_participant_id_is_active_index');
        });

        DB::statement('
            INSERT INTO exam_tokens_new (id, session_id, token, is_active, expires_at, last_used_at, created_at, updated_at)
            SELECT id, session_id, token, is_active, expires_at, last_used_at, created_at, updated_at
            FROM exam_tokens
        ');

        Schema::drop('exam_tokens');
        Schema::rename('exam_tokens_new', 'exam_tokens');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    private function isSqlite(): bool
    {
        return DB::getDriverName() === 'sqlite';
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
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

    private function foreignKeyExists(string $table, string $foreignKeyName): bool
    {
        try {
            $database = DB::getDatabaseName();
            $result = DB::selectOne(
                'SELECT 1 FROM information_schema.table_constraints WHERE constraint_schema = ? AND table_name = ? AND constraint_name = ? AND constraint_type = ? LIMIT 1',
                [$database, $table, $foreignKeyName, 'FOREIGN KEY'],
            );

            return $result !== null;
        } catch (\Throwable) {
            return false;
        }
    }
};
