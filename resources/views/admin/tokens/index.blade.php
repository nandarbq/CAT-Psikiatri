<x-layouts.admin
    title="Manajemen Token Tes"
    heading="Master Token Tes"
    subtitle="Kelola token unik 6 digit per sesi ujian."
>
    <x-slot:actions>
        <a href="{{ route('admin.tokens.bulk.form') }}" class="btn btn-ghost">Buat Token Beberapa Sesi</a>
        <a href="{{ route('admin.tokens.create') }}" class="btn btn-primary">Buat Token Sesi</a>
    </x-slot:actions>

    <form method="GET" action="{{ route('admin.tokens.index') }}" class="filter-panel">
        <label class="field">
            <span>Cari Token</span>
            <input class="input" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari token...">
        </label>
        <label class="field">
            <span>Sesi</span>
            <select class="input" name="session_id">
                <option value="">Semua Sesi</option>
                @foreach ($sessionOptions as $session)
                    <option value="{{ $session->id }}" @selected((string) ($filters['session_id'] ?? '') === (string) $session->id)>{{ $session->title }}</option>
                @endforeach
            </select>
        </label>
        <label class="field">
            <span>Status</span>
            <select class="input" name="is_active">
                <option value="">Semua</option>
                <option value="1" @selected((string) ($filters['is_active'] ?? '') === '1')>Aktif</option>
                <option value="0" @selected((string) ($filters['is_active'] ?? '') === '0')>Nonaktif</option>
            </select>
        </label>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Terapkan</button>
            <a href="{{ route('admin.tokens.index') }}" class="btn btn-ghost">Atur Ulang</a>
        </div>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Sesi</th>
                <th>Token</th>
                <th>Status</th>
                <th>Kedaluwarsa</th>
                <th>Terakhir Dipakai</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($tokens as $token)
                <tr>
                    <td>{{ $token->session->title }}</td>
                    <td><code>{{ $token->token }}</code></td>
                    <td>
                        <span class="status-chip {{ $token->is_active ? 'ok' : 'pending' }}">{{ $token->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </td>
                    <td>{{ $token->expires_at?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td>{{ $token->last_used_at?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td>
                        <div class="table-actions">
                            <a href="{{ route('admin.tokens.edit', $token) }}" class="table-link">Ubah</a>
                            <form method="POST" action="{{ route('admin.tokens.destroy', $token) }}" class="inline-form" data-confirm-message="Hapus token ini?" data-confirm-title="Konfirmasi Hapus">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="link-button">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">Belum ada token.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $tokens->links() }}
</x-layouts.admin>
