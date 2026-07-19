<x-layouts.admin
    :title="$isEdit ? 'Ubah Peserta' : 'Buat Akun Peserta'"
    :heading="$isEdit ? 'Ubah Peserta' : 'Buat Akun Peserta'"
    subtitle="Data peserta dipakai untuk autentikasi dan mapping sesi ujian."
>
    <x-slot:actions>
        <a href="{{ route('admin.participants.index') }}" class="btn btn-ghost">Kembali ke Daftar</a>
    </x-slot:actions>

    @if ($hasAttempts)
        <div class="alert warning">Peserta ini punya riwayat pengerjaan ujian. Pindah sesi hanya diizinkan bila tidak ada ujian yang masih berjalan.</div>
    @endif
    @if ($isEdit)
        <div class="alert warning">Jika peserta dipindah ke sesi lain, status selesai akan di-reset untuk sesi baru.</div>
    @endif

    <section class="admin-form-card">
        <form method="POST" action="{{ $isEdit ? route('admin.participants.update', $participant) : route('admin.participants.store') }}" class="form-grid">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <label class="field">
                <span>Sesi</span>
                <select class="input" name="session_id" required>
                    @foreach ($sessions as $session)
                        <option value="{{ $session->id }}" @selected((string) old('session_id', $participant->session_id) === (string) $session->id)>{{ $session->title }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field"><span>NIK</span><input class="input" type="text" name="nik" required value="{{ old('nik', $participant->nik) }}"></label>
            <label class="field"><span>Username</span><input class="input" type="text" name="username" required value="{{ old('username', $participant->username) }}"></label>
            <label class="field"><span>Password {{ $isEdit ? '(Kosongkan jika tidak ganti)' : '' }}</span><input class="input" type="password" name="password" {{ $isEdit ? '' : 'required' }}></label>
            <div class="field">
                <span>Kode Peserta (Legacy)</span>
                <div class="input" style="display: flex; align-items: center; min-height: 44px;">Dikelola otomatis oleh sistem</div>
                <small class="field-help">Token ujian dibuat terpisah lewat menu Token Ujian (berlaku per sesi).</small>
            </div>
            <label class="field"><span>Nama</span><input class="input" type="text" name="name" required value="{{ old('name', $participant->name) }}"></label>
            <label class="field"><span>Tempat Lahir</span><input class="input" type="text" name="birth_place" value="{{ old('birth_place', $participant->birth_place) }}"></label>
            <label class="field"><span>Tanggal Lahir</span><input class="input" type="date" name="birth_date" required value="{{ old('birth_date', optional($participant->birth_date)->format('Y-m-d')) }}"></label>
            <label class="field">
                <span>Gender</span>
                <select class="input" name="gender" required>
                    <option value="male" @selected(old('gender', $participant->gender) === 'male')>Laki-laki</option>
                    <option value="female" @selected(old('gender', $participant->gender) === 'female')>Perempuan</option>
                </select>
            </label>
            <label class="field"><span>No. HP</span><input class="input" type="text" name="phone" value="{{ old('phone', $participant->phone) }}"></label>
            <label class="field"><span>Alamat</span><textarea class="input" name="address" rows="3">{{ old('address', $participant->address) }}</textarea></label>
            <label class="field"><span>Keperluan</span><input class="input" type="text" name="purpose" value="{{ old('purpose', $participant->purpose) }}" placeholder="Contoh: MCU / Rekrutmen"></label>
            @if ($isEdit)
                <label class="field">
                    <span>Status Selesai</span>
                    <select class="input" name="is_finished" required>
                        <option value="0" @selected((string) old('is_finished', (int) ($participant->is_finished ?? false)) === '0')>Belum</option>
                        <option value="1" @selected((string) old('is_finished', (int) ($participant->is_finished ?? false)) === '1')>Selesai</option>
                    </select>
                </label>
            @endif

            <button class="btn btn-primary" type="submit">{{ $isEdit ? 'Simpan Perubahan' : 'Buat Peserta' }}</button>
        </form>
    </section>
</x-layouts.admin>
