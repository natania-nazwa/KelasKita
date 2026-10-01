@extends('layouts.admin')

@section('title', 'Sesi Login | KelasKita')

@section('content')

    {{--
        "Sesi Login" di dalam area Pengaturan admin.

        Daftar perangkatnya dibaca dari tabel sessions, bukan dari data
        tiruan: tabel itu memang dipakai driver sesi aktif aplikasi ini
        (SESSION_DRIVER=database), dan setiap permintaan yang masuk
        memperbarui barisnya.

        Browser dan sistem operasi dibaca dari user_agent yang tersimpan. Kalau
        user_agent kosong atau tidak dikenali, halamannya mengatakannya tidak
        diketahui, bukan menebak. Kalau driver sesinya bukan database, daftar
        perangkat tidak bisa dibaca sama sekali, dan halamannya mengatakannya
        terus terang, bukan menampilkan baris contoh.
    --}}

    <div class="ad-seksi">
        <x-admin.kepala judul="Sesi Login" ikon="ponsel"
            subjudul="Lihat perangkat yang sedang login."
            :aksi-teks="'Kembali ke Pengaturan'"
            :aksi-href="route('admin.pengaturan')" />
    </div>

    <div class="ad-atur-lebar">
        <div class="ad-kartu">
            <header class="ad-kartu__kepala">
                <div class="ad-kartu__kepala-titik">
                    <span class="ad-cepat__ikon" aria-hidden="true">
                        <x-admin.ikon nama="ponsel" ukuran="w-5 h-5" />
                    </span>

                    <div class="min-w-0">
                        <h2 class="ad-kartu__kepala-judul">Perangkat Aktif</h2>
                        <p class="ad-kartu__subjudul">
                            {{ $perangkatLain }} perangkat lain selain perangkat ini
                        </p>
                    </div>
                </div>

                @if ($tersedia)
                    <button type="button" class="ad-tombol ad-tombol--bahaya"
                        data-atur-dialog-buka="atur-logout-semua">
                        <x-admin.ikon nama="pintu-keluar" />
                        Logout dari Semua Perangkat
                    </button>
                @endif
            </header>

            <div class="ad-kartu__badan">
                @if (! $tersedia)
                    {{--
                        Driver sesi bukan database, jadi tidak ada tabel yang
                        bisa dibaca. Halaman ini tidak pernah mengisi daftar
                        dengan data contoh untuk menutupi itu.
                    --}}
                    <x-admin.kosong ikon="ponsel" judul="Daftar perangkat tidak tersedia"
                        teks="Aplikasi ini memakai driver sesi {{ config('session.driver') }}, yang tidak menyimpan daftar sesi di database sehingga perangkat yang sedang login tidak bisa dibaca di sini." />
                @else
                    @forelse ($perangkat as $baris)
                        <div class="ad-atur-perangkat">
                            <span class="ad-atur-perangkat__ikon" aria-hidden="true">
                                <x-admin.ikon :nama="$baris['sistem'] === 'Android' ? 'ponsel' : 'monitor'"
                                    ukuran="w-4 h-4" />
                            </span>

                            <div class="ad-atur-perangkat__teks">
                                <p class="ad-atur-perangkat__judul">
                                    {{ $baris['peramban'] }}
                                    <span class="ad-teks-2 !text-xs font-normal">{{ $baris['sistem'] }}</span>

                                    @if ($baris['ini'])
                                        <span class="ad-lencana ad-lencana--sukses">
                                            <span class="ad-lencana__titik" aria-hidden="true"></span>
                                            Perangkat ini
                                        </span>
                                    @endif
                                </p>

                                <p class="ad-atur-perangkat__sub">
                                    {{ $baris['ini'] ? 'Aktif sekarang' : 'Terakhir aktif '.$baris['terakhir_label'] }}
                                    @if (filled($baris['ip']))
                                        <span class="mx-1">&middot;</span>
                                        <span class="ad-atur-kode">{{ $baris['ip'] }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    @empty
                        <x-admin.kosong ikon="ponsel" judul="Belum ada sesi tercatat"
                            teks="Tidak ada perangkat yang tercatat masuk dengan akun ini. Coba muat ulang halaman ini." />
                    @endforelse
                @endif

                <div class="ad-alert mt-5">
                    <span class="ad-alert__ikon" aria-hidden="true">
                        <x-admin.ikon nama="lampu" ukuran="w-3.5 h-3.5" />
                    </span>

                    <p class="min-w-0">
                        Sesi yang tercatat sudah lewat masa berlaku
                        ({{ config('session.lifetime') }} menit) tidak ikut ditampilkan, karena
                        Laravel sudah membersihkannya sendiri. Mengubah kata sandi
                        juga mengeluarkan perangkat lain.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== DIALOG KONFIRMASI ==================== --}}
    <div class="ad-dialog" data-atur-dialog="atur-logout-semua" role="dialog" aria-modal="true" aria-hidden="true"
        aria-labelledby="atur-sesi-logout-semua-judul">
        <div class="ad-dialog__kartu">
            <header class="ad-dialog__kepala">
                <div class="min-w-0 flex-1">
                    <h2 class="ad-dialog__judul" id="atur-sesi-logout-semua-judul">Logout dari semua perangkat?</h2>
                </div>

                <button type="button" class="ad-dialog__tutup" data-atur-dialog-batal aria-label="Tutup">
                    <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
                </button>
            </header>

            <div class="ad-dialog__badan">
                <p class="ad-dialog__pesan">
                    Semua sesi login akan diakhiri, jadi
                    {{ $perangkatLain }} perangkat lain harus login ulang.
                    Perangkat ini tetap aktif.
                </p>
            </div>

            <footer class="ad-dialog__kaki">
                <button type="button" class="ad-tombol ad-tombol--garis" data-atur-dialog-batal>Batal</button>

                <form method="POST" action="{{ route('admin.pengaturan.sesi.destroy') }}"
                    id="form-atur-sesi-logout-semua" hidden>
                    @csrf
                    @method('DELETE')
                </form>

                <button type="submit" class="ad-tombol ad-tombol--bahaya" form="form-atur-sesi-logout-semua">
                    <x-admin.ikon nama="pintu-keluar" />
                    Logout Semua
                </button>
            </footer>
        </div>
    </div>

    <x-admin.toast :judul="session('sukses')" :pesan="session('suksesDetail')" />

@endsection