<x-layouts.admin
    :title="$isEdit ? 'Ubah Token Sesi' : 'Buat Token Sesi'"
    :heading="$isEdit ? 'Ubah Token Sesi' : 'Buat Token Sesi'"
    subtitle="Atur token unik 6 digit yang berlaku untuk satu sesi tes."
>
    <x-slot:actions>
        <a href="{{ route('admin.tokens.index') }}" class="btn btn-ghost">Kembali ke Daftar</a>
    </x-slot:actions>

    <section class="admin-form-card">
        <form method="POST" action="{{ $isEdit ? route('admin.tokens.update', $tokenRecord) : route('admin.tokens.store') }}" class="form-grid" data-token-form>
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <label class="field">
                <span>Sesi Tes</span>
                <select class="input" name="session_id" required data-session-select>
                    <option value="">Pilih sesi</option>
                    @foreach ($sessions as $session)
                        <option value="{{ $session->id }}" @selected((string) old('session_id', $tokenRecord->session_id ?: $defaultSessionId) === (string) $session->id)>
                            {{ $session->title }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="field">
                <span>Token Tes</span>
                <div class="filter-actions">
                    <input
                        class="input"
                        type="text"
                        name="token"
                        required
                        maxlength="6"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        title="Token harus 6 digit angka"
                        value="{{ old('token', $tokenRecord->token ?: $generatedToken) }}"
                        data-token-input
                    >
                    <button type="button" class="btn btn-ghost" data-generate-token data-generate-url="{{ route('admin.tokens.generate') }}">Buat Otomatis</button>
                </div>
                <small class="field-help">Format token: 6 digit angka (contoh: 084275).</small>
            </label>

            <label class="field">
                <span>Status</span>
                <select class="input" name="is_active" required>
                    <option value="1" @selected((string) old('is_active', (int) ($tokenRecord->is_active ?? true)) === '1')>Aktif</option>
                    <option value="0" @selected((string) old('is_active', (int) ($tokenRecord->is_active ?? true)) === '0')>Nonaktif</option>
                </select>
            </label>

            <label class="field">
                <span>Kedaluwarsa (opsional)</span>
                <input
                    class="input"
                    type="datetime-local"
                    name="expires_at"
                    value="{{ old('expires_at', $tokenRecord->expires_at?->format('Y-m-d\\TH:i')) }}"
                >
            </label>

            <button class="btn btn-primary" type="submit">{{ $isEdit ? 'Simpan Perubahan' : 'Buat Token Sesi' }}</button>
        </form>
    </section>

    <script>
        (() => {
            const form = document.querySelector('[data-token-form]');
            if (!form) return;

            const tokenInput = form.querySelector('[data-token-input]');
            const generateBtn = form.querySelector('[data-generate-token]');

            generateBtn?.addEventListener('click', async () => {
                const url = generateBtn.dataset.generateUrl;
                if (!url || !tokenInput) return;

                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
                generateBtn.disabled = true;

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });
                    const data = await response.json();
                    if (!response.ok || !data.token) {
                        throw new Error('Pembuatan token otomatis gagal.');
                    }

                    tokenInput.value = String(data.token);
                } catch (error) {
                    await window.AppDialog.alert({
                        title: 'Pembuatan Token Gagal',
                        text: 'Gagal membuat token otomatis. Coba lagi.',
                        icon: 'error',
                    });
                } finally {
                    generateBtn.disabled = false;
                }
            });
        })();
    </script>
</x-layouts.admin>
