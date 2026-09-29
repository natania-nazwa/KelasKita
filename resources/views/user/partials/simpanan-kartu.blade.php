{{--
    Isi satu baris kartu untuk halaman "Simpan". Dipakai dua kali:

      1. halaman pertama, lewat <x-simpan.daftar> di
         user/simpanan.blade.php;
      2. halaman berikutnya, lewat SimpananController::muat() yang
         mengirim HTML-nya ke initMuatLebih() di app.js.

    Sengaja tanpa wrapper grid, supaya kartu bisa disisipkan ke dalam
    grid yang sudah ada, bukan menggantikannya.
--}}

@foreach ($daftar as $item)
    @if ($tab === 'quiz')
        <x-quiz.kartu :quiz="$item" :kata-kunci="$kataKunci" />
    @else
        <x-materi.kartu :materi="$item" :kata-kunci="$kataKunci" />
    @endif
@endforeach
