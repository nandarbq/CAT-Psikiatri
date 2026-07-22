<x-layouts.admin
    title="Pustaka Soal (Modul)"
    heading="Pustaka Soal (Modul)"
    subtitle="Kelola modul soal. Buat modul baru lengkap dengan soalnya, lalu atur status aktif/nonaktif per modul."
>
    <x-slot:actions>
        <a href="{{ route('admin.modules.create') }}" class="btn btn-primary">Tambah Modul</a>
    </x-slot:actions>

    <form method="GET" action="{{ route('admin.modules.index') }}" class="filter-panel">
        <label class="field">
            <span>Cari Nama Modul</span>
            <input class="input" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari modul...">
        </label>
        <label class="field">
            <span>Status</span>
            <select class="input" name="status">
                <option value="">Semua</option>
                <option value="1" @selected((string) ($filters['status'] ?? '') === '1')>Aktif</option>
                <option value="0" @selected((string) ($filters['status'] ?? '') === '0')>Nonaktif</option>
            </select>
        </label>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Terapkan</button>
            <a href="{{ route('admin.modules.index') }}" class="btn btn-ghost">Atur Ulang</a>
        </div>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Nama Modul</th>
                <th>Deskripsi</th>
                <th>Jumlah Soal</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($modules as $module)
                <tr>
                    <td>{{ $module->name }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($module->description ?? '-', 80) }}</td>
                    <td>{{ $module->active_questions_count }} aktif / {{ $module->questions_count }} total</td>
                    <td>
                        <span class="status-chip {{ $module->status ? 'ok' : 'pending' }}">{{ $module->status ? 'Aktif' : 'Nonaktif' }}</span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="{{ route('admin.modules.edit', $module) }}" class="table-link">Kelola Soal</a>
                            <form method="POST" action="{{ route('admin.modules.status', $module) }}" class="inline-form" data-confirm-message="{{ $module->status ? 'Nonaktifkan modul ini?' : 'Aktifkan kembali modul ini?' }}" data-confirm-title="Konfirmasi Status">
                                @csrf
                                <input type="hidden" name="status" value="{{ $module->status ? '0' : '1' }}">
                                <button type="submit" class="link-button">{{ $module->status ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.modules.destroy', $module) }}" class="inline-form" data-confirm-message="Hapus modul ini beserta seluruh soalnya?" data-confirm-title="Konfirmasi Hapus">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="link-button">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada modul. Klik <strong>Tambah Modul</strong> untuk membuat modul soal baru.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $modules->links() }}
</x-layouts.admin>
