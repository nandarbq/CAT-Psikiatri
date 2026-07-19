<?php

declare(strict_types=1);

namespace App\Support\Monitoring;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class VersionInfoPayloadFactory
{
    /**
     * @return array{
     *     app:string,
     *     environment:string,
     *     env:string,
     *     version:string|null,
     *     commit:string|null,
     *     live_commit_sha:string|null,
     *     sha:string|null,
     *     commit_short:string|null,
     *     deployed_at:string|null,
     *     generated_at:string
     * }
     */
    public function make(): array
    {
        $commit = $this->resolveCommitSha();
        $version = $this->resolveVersion($commit);
        $deployedAt = $this->resolveDeployedAt();
        $environment = app()->environment();

        return [
            'app' => (string) config('monitoring.app_name', config('app.name')),
            'environment' => $environment,
            'env' => $environment,
            'version' => $version,
            'commit' => $commit,
            'live_commit_sha' => $commit,
            'sha' => $commit,
            'commit_short' => $commit !== null ? Str::substr($commit, 0, 7) : null,
            'deployed_at' => $deployedAt,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function resolveCommitSha(): ?string
    {
        $envCommit = $this->normalizeSha((string) config('monitoring.commit_sha', ''));
        if ($envCommit !== null) {
            return $envCommit;
        }

        $fileCommit = $this->firstNonEmptyLineFromFiles(['REVISION', '.commit_sha']);
        $normalizedFileCommit = $this->normalizeSha((string) $fileCommit);
        if ($normalizedFileCommit !== null) {
            return $normalizedFileCommit;
        }

        return $this->readGitHeadSha();
    }

    private function resolveVersion(?string $commit): ?string
    {
        $envVersion = trim((string) config('monitoring.version', ''));
        if ($envVersion !== '') {
            return $envVersion;
        }

        $fileVersion = $this->firstNonEmptyLineFromFiles(['VERSION', '.version']);
        if ($fileVersion !== null) {
            return $fileVersion;
        }

        if ($commit !== null) {
            return 'git-'.Str::substr($commit, 0, 7);
        }

        return null;
    }

    private function resolveDeployedAt(): ?string
    {
        $envDeployedAt = trim((string) config('monitoring.deployed_at', ''));
        if ($envDeployedAt !== '') {
            return $envDeployedAt;
        }

        $fileDeployedAt = $this->firstNonEmptyLineFromFiles(['DEPLOYED_AT', '.deployed_at']);
        if ($fileDeployedAt !== null) {
            return $fileDeployedAt;
        }

        $markerFile = $this->firstExistingFile([
            'DEPLOYED_AT',
            '.deployed_at',
            'REVISION',
            '.commit_sha',
        ]);

        if ($markerFile === null) {
            return null;
        }

        $timestamp = File::lastModified($markerFile);

        return now()->setTimestamp($timestamp)->toIso8601String();
    }

    private function firstNonEmptyLineFromFiles(array $relativePaths): ?string
    {
        foreach ($relativePaths as $relativePath) {
            $absolutePath = base_path($relativePath);
            if (! File::exists($absolutePath)) {
                continue;
            }

            $firstLine = trim(Str::before((string) File::get($absolutePath), "\n"));
            if ($firstLine !== '') {
                return $firstLine;
            }
        }

        return null;
    }

    private function firstExistingFile(array $relativePaths): ?string
    {
        foreach ($relativePaths as $relativePath) {
            $absolutePath = base_path($relativePath);
            if (File::exists($absolutePath)) {
                return $absolutePath;
            }
        }

        return null;
    }

    private function readGitHeadSha(): ?string
    {
        $gitDir = base_path('.git');
        $headPath = $gitDir.'/HEAD';

        if (! File::exists($headPath)) {
            return null;
        }

        $headValue = trim((string) File::get($headPath));
        if ($headValue === '') {
            return null;
        }

        if (str_starts_with($headValue, 'ref:')) {
            $refPath = trim(Str::after($headValue, 'ref:'));
            if ($refPath === '') {
                return null;
            }

            $resolvedRefPath = $gitDir.'/'.$refPath;
            if (File::exists($resolvedRefPath)) {
                return $this->normalizeSha(trim((string) File::get($resolvedRefPath)));
            }

            return $this->readShaFromPackedRefs($refPath);
        }

        return $this->normalizeSha($headValue);
    }

    private function readShaFromPackedRefs(string $refPath): ?string
    {
        $packedRefsPath = base_path('.git/packed-refs');
        if (! File::exists($packedRefsPath)) {
            return null;
        }

        $lines = preg_split('/\r\n|\r|\n/', (string) File::get($packedRefsPath)) ?: [];
        foreach ($lines as $line) {
            $candidate = trim($line);
            if ($candidate === '' || str_starts_with($candidate, '#') || str_starts_with($candidate, '^')) {
                continue;
            }

            [$sha, $name] = preg_split('/\s+/', $candidate, 2) + [null, null];
            if ($name === $refPath) {
                return $this->normalizeSha((string) $sha);
            }
        }

        return null;
    }

    private function normalizeSha(string $value): ?string
    {
        $trimmed = strtolower(trim($value));
        if ($trimmed === '') {
            return null;
        }

        return preg_match('/^[0-9a-f]{40}$/', $trimmed) === 1
            ? $trimmed
            : null;
    }
}
