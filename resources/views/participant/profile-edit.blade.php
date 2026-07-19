<x-layouts.app title="Ubah Data Diri Peserta">
    <section class="panel participant-panel">
        @include('participant.partials.nav')

        @if ($errors->any())
            <div class="alert error">{{ $errors->first() }}</div>
        @endif

        <div class="participant-hero">
            <h1 class="title-xl">Ubah Data Diri</h1>
            <p class="subtitle">
                Pastikan data berikut sesuai dokumen Anda: NIK, Nama, TTL, Alamat, dan Keperluan.
            </p>
        </div>

        <section class="participant-card">
            <form method="POST" action="{{ route('participant.profile.update') }}" class="form-grid">
                @csrf
                @method('PUT')

                <label class="field">
                    <span>NIK</span>
                    <input class="input" type="text" name="nik" required value="{{ old('nik', $participant->nik) }}">
                </label>
                <label class="field">
                    <span>Nama</span>
                    <input class="input" type="text" name="name" required value="{{ old('name', $participant->name) }}">
                </label>
                <label class="field">
                    <span>Tempat Lahir</span>
                    <input class="input" type="text" name="birth_place" required value="{{ old('birth_place', $participant->birth_place) }}">
                </label>
                <label class="field">
                    <span>Tanggal Lahir</span>
                    <input class="input" type="date" name="birth_date" required value="{{ old('birth_date', optional($participant->birth_date)->format('Y-m-d')) }}">
                </label>
                <label class="field">
                    <span>Alamat</span>
                    <textarea class="input" name="address" rows="3" required>{{ old('address', $participant->address) }}</textarea>
                </label>
                <label class="field">
                    <span>Keperluan</span>
                    <input class="input" type="text" name="purpose" required value="{{ old('purpose', $participant->purpose) }}" placeholder="Contoh: MCU, Rekrutmen, atau Keperluan lainnya">
                </label>

                <button type="submit" class="btn btn-primary">Simpan Data Diri</button>
            </form>
        </section>
    </section>
</x-layouts.app>

