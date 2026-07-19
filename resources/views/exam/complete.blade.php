<x-layouts.app title="Ujian Selesai">
    <section class="panel card-tight card-centered auth-floating">
        <h1 class="title-xl">Ujian Selesai</h1>
        <p class="subtitle">Jawaban Anda sudah dikunci. Terima kasih telah mengikuti sesi ujian.</p>
        <form method="POST" action="{{ route('participant.logout') }}">
            @csrf
            <button type="submit" class="btn btn-primary">Kembali ke Login</button>
        </form>
    </section>
</x-layouts.app>
