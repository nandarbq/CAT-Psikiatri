<x-layouts.admin
    title="Hasil Tes"
    heading="Monitoring Hasil Peserta"
    subtitle="Pantau progres pengerjaan, status pengumpulan jawaban, dan ekspor data."
>
    <x-slot:actions>
        <a href="{{ route('admin.results.export.csv') }}" class="btn btn-ghost">Ekspor CSV</a>
        <a href="{{ route('admin.results.export.excel') }}" class="btn btn-primary">Ekspor Excel</a>
    </x-slot:actions>

    <!-- Per Page Selector -->
    <div class="flex items-center justify-between mb-4">
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
            Total: <span class="font-medium">{{ $attempts->total() }}</span> hasil tes
        </div>
    </div>

    <!-- Table -->
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
                    <td>{{ $attempt->participant?->name ?? 'Peserta tidak tersedia' }}</td>
                    <td>{{ $attempt->session?->title ?? 'Sesi tidak tersedia' }}</td>
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
                    <td colspan="7" class="text-center py-8 text-gray-500">Belum ada data pengerjaan tes.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- ============================================ -->
    <!-- PAGINATION CUSTOM - WARNA HIJAU -->
    <!-- ============================================ -->
    @if ($attempts->hasPages())
        <div class="flex flex-col items-center justify-between gap-4 border-t border-gray-200 px-4 py-3 mt-6 sm:flex-row">
            <!-- Info -->
            <div class="text-sm text-gray-700">
                Menampilkan
                <span class="font-medium">{{ $attempts->firstItem() }}</span>
                sampai
                <span class="font-medium">{{ $attempts->lastItem() }}</span>
                dari
                <span class="font-medium">{{ $attempts->total() }}</span>
                hasil tes
            </div>

            <!-- Tombol Pagination Warna Hijau -->
            <div class="flex items-center gap-1 flex-wrap justify-center">
                {{-- Previous --}}
                @if ($attempts->onFirstPage())
                    <span class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-md cursor-not-allowed">
                        ← Sebelumnya
                    </span>
                @else
                    <a href="{{ $attempts->previousPageUrl() }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-green-50 hover:border-green-400 transition-all duration-150">
                        ← Sebelumnya
                    </a>
                @endif

                {{-- Nomor Halaman --}}
                @foreach ($attempts->links()->elements as $element)
                    @if (is_string($element))
                        <span class="px-3 py-2 text-sm text-gray-500">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $attempts->currentPage())
                                <span class="inline-flex items-center px-4 py-2 text-sm font-bold text-white bg-blue-600 border border-blue-600 rounded-md shadow-sm">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-green-50 hover:border-green-400 transition-all duration-150">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($attempts->hasMorePages())
                    <a href="{{ $attempts->nextPageUrl() }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-green-50 hover:border-green-400 transition-all duration-150">
                        Selanjutnya →
                    </a>
                @else
                    <span class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-md cursor-not-allowed">
                        Selanjutnya →
                    </span>
                @endif
            </div>
        </div>
    @else
        {{-- Jika hanya 1 halaman --}}
        <div class="flex justify-center border-t border-gray-200 px-4 py-3 mt-6">
            <div class="text-sm text-gray-700">
                Menampilkan 
                <span class="font-medium">{{ $attempts->firstItem() }}</span> 
                sampai 
                <span class="font-medium">{{ $attempts->lastItem() }}</span> 
                dari 
                <span class="font-medium">{{ $attempts->total() }}</span> 
                hasil tes
            </div>
        </div>
    @endif
</x-layouts.admin>
