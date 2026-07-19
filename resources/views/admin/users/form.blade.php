<x-layouts.admin
    :title="$isEdit ? 'Ubah Akun Admin' : 'Buat Akun Admin'"
    :heading="$isEdit ? 'Ubah Akun Admin' : 'Buat Akun Admin'"
    subtitle="Modul ini hanya untuk user internal dengan role admin."
>
    <x-slot:actions>
        <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Kembali ke Daftar</a>
    </x-slot:actions>

    <section class="admin-form-card">
        <form method="POST" action="{{ $isEdit ? route('admin.users.update', $user) : route('admin.users.store') }}" class="form-grid">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <label class="field">
                <span>Nama</span>
                <input class="input" type="text" name="name" required value="{{ old('name', $user->name) }}">
            </label>
            <label class="field">
                <span>Username</span>
                <input class="input" type="text" name="username" required value="{{ old('username', $user->username) }}">
            </label>
            <label class="field">
                <span>Email</span>
                <input class="input" type="email" name="email" required value="{{ old('email', $user->email) }}">
            </label>
            <label class="field">
                <span>Password {{ $isEdit ? '(Kosongkan jika tidak ganti)' : '' }}</span>
                <input class="input" type="password" name="password" {{ $isEdit ? '' : 'required' }}>
            </label>

            <button class="btn btn-primary" type="submit">{{ $isEdit ? 'Simpan Perubahan' : 'Buat Akun' }}</button>
        </form>
    </section>
</x-layouts.admin>
