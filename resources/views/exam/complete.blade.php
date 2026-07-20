<x-layouts.app title="Tes Selesai">
    <section class="panel card-tight card-centered auth-floating">
        <h1 class="title-xl">Tes Selesai</h1>
        <p class="subtitle">Jawaban Anda sudah dikunci. Terima kasih telah mengikuti sesi tes.</p>
        <form method="POST" action="{{ route('participant.logout') }}">
            @csrf
            <button type="submit" class="btn btn-primary">Kembali ke Login</button>
        </form>
    </section>
</x-layouts.app>
