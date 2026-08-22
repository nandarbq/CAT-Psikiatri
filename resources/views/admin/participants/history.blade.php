<x-layouts.admin
    title="Riwayat Peserta"
    heading="Riwayat Peserta"
    subtitle="Riwayat sesi dan progres tes untuk satu peserta."
>
    <x-slot:actions>
        <a href="{{ route('admin.participants.index') }}" class="btn btn-ghost">Kembali ke Manajemen Peserta</a>
        <a href="{{ route('admin.participants.edit', $participant) }}" class="btn btn-primary">Ubah Peserta</a>
    </x-slot:actions>

    <section class="admin-form-card">
        <div class="form-grid">
            <div class="field">
                <span>Nama</span>
                <div class="input" style="display: flex; align-items: center; min-height: 44px;">{{ $participant->name }}</div>
            </div>
            <div class="field">
                <span>Username</span>
                <div class="input" style="display: flex; align-items: center; min-height: 44px;">{{ $participant->username }}</div>
            </div>
            <div class="field">
                <span>NIK</span>
                <div class="input" style="display: flex; align-items: center; min-height: 44px;">{{ $participant->nik }}</div>
            </div>
            <div class="field">
                <span>Sesi Aktif Saat Ini</span>
                <div class="input" style="display: flex; align-items: center; min-height: 44px;">{{ $participant->session->title }}</div>
            </div>
            <div class="field">
                <span>Status Selesai Sesi Aktif</span>
                <div class="input" style="display: flex; align-items: center; min-height: 44px;">
                    <span class="status-chip {{ $participant->is_finished ? 'ok' : 'pending' }}">{{ $participant->is_finished ? 'Selesai' : 'Belum' }}</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Per Page Selector --}}
    <div class="flex items-center justify-between mb-4 mt-4">
        <div class="flex items-center space-x-2">
            <label for="per_page" class="text-sm text-gray-700">Tampilkan</label>
            <select id="per_page"
                    name="per_page"
                    class="input !w-auto min-w-[70px]"
                    onchange="window.location.href = this.value">
                @php
                    $currentUrl = request()->fullUrlWithQuery(['per_page' => 'PER_PAGE']);
                @endphp
                @foreach([10, 25, 50, 100] as $size)
                    <option value="{{ str_replace('PER_PAGE', $size, $currentUrl) }}"
                            {{ request('per_page', 10) == $size ? 'selected' : '' }}>
                        {{ $size }}
                    </option>
                @endforeach
            </select>
            <span class="text-sm text-gray-700">per halaman</span>
        </div>
        <div class="text-sm text-gray-500">
            Total: <span class="font-medium">{{ $attempts->total() }}</span> pengerjaan
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>ID Pengerjaan</th>
                <th>Sesi</th>
                <th>Mulai</th>
                <th>Selesai</th>
                <th>Jawaban</th>
                <th>Status Pengerjaan</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($attempts as $attempt)
                <tr>
                    <td>#{{ $attempt->id }}</td>
                    <td>{{ $attempt->session->title }}</td>
                    <td>{{ optional($attempt->started_at)->format('d M Y H:i') ?? '-' }}</td>
                    <td>{{ optional($attempt->finished_at)->format('d M Y H:i') ?? '-' }}</td>
                    <td>{{ $attempt->answers_count }}</td>
                    <td>
                        <span class="status-chip {{ $attempt->is_submitted ? 'ok' : 'pending' }}">
                            {{ $attempt->is_submitted ? 'Sudah Dikirim' : 'Sedang Berjalan' }}
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="{{ route('admin.results.show', $attempt) }}" class="table-link">Detail Hasil</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">Peserta ini belum memiliki riwayat pengerjaan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $attempts->links() }}
</x-layouts.admin>
