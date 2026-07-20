<x-layouts.admin
    title="Hasil Tes"
    heading="Monitoring Hasil Peserta"
    subtitle="Pantau progres pengerjaan, status pengumpulan jawaban, dan ekspor data."
>
    <x-slot:actions>
        <a href="{{ route('admin.results.export.csv') }}" class="btn btn-ghost">Ekspor CSV</a>
        <a href="{{ route('admin.results.export.excel') }}" class="btn btn-primary">Ekspor Excel</a>
    </x-slot:actions>

    <div class="table-wrap" role="region" aria-label="Daftar hasil tes">
        <table class="data-table">
            <thead>
            <tr>
                <th>Peserta</th>
                <th>Sesi</th>
                <th>Mulai</th>
                <th>Selesai</th>
                <th>Jawaban</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($attempts as $attempt)
                <tr>
                    <td>{{ $attempt->participant->name }}</td>
                    <td>{{ $attempt->session->title }}</td>
                    <td>{{ $attempt->started_at }}</td>
                    <td>{{ $attempt->finished_at ?? '-' }}</td>
                    <td>{{ $attempt->answers_count }}</td>
                    <td>
                        <span class="status-chip {{ $attempt->is_submitted ? 'ok' : 'pending' }}">
                            {{ $attempt->is_submitted ? 'Sudah Dikirim' : 'Sedang Berjalan' }}
                        </span>
                    </td>
                    <td><a class="table-link" href="{{ route('admin.results.show', $attempt) }}">Detail</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Belum ada data pengerjaan tes.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $attempts->links() }}
</x-layouts.admin>
