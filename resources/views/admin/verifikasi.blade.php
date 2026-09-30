@extends('layouts.admin')

@section('title', 'Verifikasi Konten | KelasKita')

@section('content')

    {{--
        Halaman "Verifikasi Konten": materi dan quiz yang dibuat pengguna
        dalam satu daftar.

        Halaman ini tidak pernah memutuskan apa pun sendiri. Tombol
        Setujui dan Tolak mengirim POST ke route yang sudah ada
        (admin.materi.setujui / admin.materi.tolak, dan padanannya untuk
        quiz) dengan field "kembali" supaya admin kembali ke halaman ini
        setelah memutuskan, bukan ke halaman tinjauan masing-masing.

        Quiz mode kode tidak pernah muncul di sini: quiz seperti itu
        tidak tayang untuk semua pengguna, jadi tidak ada yang perlu
        disetujui admin.
    --}}

    @php
        /*
         * Tab dibangun di sini, bukan di controller, supaya controller
         * tidak perlu tahu bentuk URL. Yang dikirim ke view cukup nilai
         * tab yang aktif dan jumlahnya.
         */
        $tabJenis = collect($pilihanJenis)
            ->map(fn (string $label, string $nilai): array => [
                'label' => $label,
                'nilai' => $nilai,
                'jumlah' => $jumlahJenis[$nilai] ?? 0,
                'href' => route('admin.verifikasi', [
                    'jenis' => $nilai,
                    'status' => $statusAktif,
                    'q' => $kataKunci,
                ]),
            ])
            ->all();

        $tabStatus = collect($pilihanStatus)
            ->map(fn (string $label, string $nilai): array => [
                'label' => $label,
                'nilai' => $nilai,
                'jumlah' => $jumlahStatus[$nilai] ?? 0,
                'href' => route('admin.verifikasi', [
                    'jenis' => $jenisAktif,
                    'status' => $nilai,
                    'q' => $kataKunci,
                ]),
            ])
            ->all();
    @endphp

    <x-admin.kepala judul="Verifikasi Konten"
        subjudul="Tinjau materi dan quiz yang dibuat oleh pengguna sebelum dipublikasikan." />

    {{-- ==================== PESAN ==================== --}}
    @if (session('sukses'))
        <div class="ad-seksi ad-alert ad-alert--sukses" role="status">
            <span class="ad-alert__ikon" aria-hidden="true">
                <x-admin.ikon nama="tanda-centang" ukuran="w-3.5 h-3.5" :tebal="2.6" />
            </span>

            <p class="min-w-0 font-medium">{{ session('sukses') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="ad-seksi ad-alert ad-alert--bahaya" role="alert">
            <span class="ad-alert__ikon" aria-hidden="true">
                <x-admin.ikon nama="silang-polos" ukuran="w-3.5 h-3.5" :tebal="2.6" />
            </span>

            <div class="min-w-0">
                <p class="font-semibold">Belum bisa diputuskan:</p>

                <ul class="mt-1 list-disc space-y-0.5 pl-5">
                    @foreach ($errors->all() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- ==================== FILTER ==================== --}}
    <div class="ad-seksi ad-alat">
        <div class="ad-alat__kiri">
            <x-admin.tab :tab="$tabJenis" :aktif="$jenisAktif" label="Filter jenis konten" />
            <x-admin.tab :tab="$tabStatus" :aktif="$statusAktif" label="Filter status konten" />
        </div>

        <form method="GET" action="{{ route('admin.verifikasi') }}" class="ad-alat__kanan">
            <input type="hidden" name="jenis" value="{{ $jenisAktif }}">
            <input type="hidden" name="status" value="{{ $statusAktif }}">

            <div class="ad-cari">
                <label for="q-verifikasi" class="sr-only">Cari konten</label>

                <x-admin.ikon nama="cari" class="ad-cari__ikon" />

                <input id="q-verifikasi" name="q" type="search" value="{{ $kataKunci }}"
                    placeholder="Cari judul atau pembuat..." autocomplete="off">
            </div>

            <button type="submit" class="ad-tombol ad-tombol--garis shrink-0">Cari</button>
        </form>
    </div>

    {{-- ==================== DAFTAR ==================== --}}
    @if ($daftar === [])
        <div class="ad-seksi">
            <x-admin.kosong ikon="tanda-centang" judul="Tidak ada konten yang menunggu verifikasi."
                teks="Materi dan quiz yang diajukan pengguna akan muncul di sini." />
        </div>
    @else
        <div class="ad-seksi ad-tinjau">
            @foreach ($daftar as $baris)
                @php
                    /*
                     * Hanya konten yang statusnya masih "menunggu" yang
                     * punya keputusan. Untuk tab lain, kolom keputusan
                     * sengaja dikosongkan supaya tidak ada tombol yang
                     * pasti ditolak server kalau diklik.
                     */
                    $bisaPutuskan = $statusAktif === 'menunggu';
                @endphp

                <article class="ad-tinjau__item">
                    <span class="ad-tinjau__ikon" style="background-color: {{ $baris['kategori_warna'] }};"
                        aria-hidden="true">
                        {{ $baris['kategori_ikon'] }}
                    </span>

                    <div class="ad-tinjau__isi">
                        <p class="ad-tinjau__judul">
                            {{ $baris['label_jenis'] }}: {{ $baris['judul'] }}
                        </p>

                        <p class="ad-tinjau__meta">
                            <span>Dibuat oleh <strong>{{ $baris['pembuat'] }}</strong></span>
                            <span>{{ $baris['kategori'] }}</span>
                            <span>{{ $baris['rincian'] }}</span>
                            <span>{{ $baris['dibuat_pada']?->translatedFormat('d M Y') }}</span>
                        </p>
                    </div>

                    <div class="ad-tinjau__kanan">
                        <x-admin.lencana :status="$bisaPutuskan ? 'pending' : $statusAktif"
                            :label="$bisaPutuskan ? 'Menunggu Verifikasi' : $pilihanStatus[$statusAktif]" />

                        <button type="button" class="ad-tombol ad-tombol--kecil {{ $bisaPutuskan ? 'ad-tombol--utama' : 'ad-tombol--garis' }}"
                            data-dialog-buka
                            data-dialog-judul="{{ $baris['label_jenis'] }}: {{ $baris['judul'] }}"
                            data-dialog-meta="{{ $baris['kategori'] }} · {{ $baris['rincian'] }} · oleh {{ $baris['pembuat'] }} ({{ $baris['dibuat_pada']?->translatedFormat('d M Y') }})"
                            data-dialog-isi="{{ $baris['isi'] }}"
                            data-dialog-setujui="{{ $bisaPutuskan ? $baris['tautan_setujui'] : '' }}"
                            data-dialog-tolak="{{ $bisaPutuskan ? $baris['tautan_tolak'] : '' }}">
                            Tinjau
                            <x-admin.ikon nama="mata" ukuran="w-3.5 h-3.5" />
                        </button>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="ad-seksi">
            {{ $paginasi->links() }}
        </div>
    @endif

    {{--
        ==================== DIALOG TINJAU ====================

        Satu dialog untuk semua baris. Isinya diisi dari data-* tombol
        yang ditekan oleh admin.js, jadi halaman ini hanya punya satu
        kotak dialog meskipun daftarnya panjang.

        Dua form di kakinya mengirim POST ke route setujui / tolak yang
        sudah ada, dengan field "kembali" supaya admin kembali ke
        halaman ini. Field "status" ikut dikirim persis seperti pada
        form yang sebelumnya tertanam langsung di tiap baris.
    --}}
    <div class="ad-dialog" data-dialog role="dialog" aria-modal="true" aria-hidden="true"
        aria-labelledby="dialog-tinjau-judul">
        <div class="ad-dialog__kartu">

            <header class="ad-dialog__kepala">
                <div class="min-w-0 flex-1">
                    <h2 class="ad-dialog__judul" id="dialog-tinjau-judul" data-dialog-judul></h2>

                    <p class="ad-teks-2 mt-0.5 !text-xs" data-dialog-meta></p>
                </div>

                <button type="button" class="ad-dialog__tutup" data-dialog-tutup aria-label="Tutup">
                    <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
                </button>
            </header>

            <div class="ad-dialog__badan">
                <p class="ad-field__label">Isi konten</p>

                <div class="ad-isi-materi mt-2" data-dialog-isi></div>

                {{--
                    Isian alasan penolakan sudah punya form-nya sendiri di
                    sini, bukan di kaki dialog. Tombol "Tolak" di kaki
                    dialog menunjuk ke form ini lewat atribut form=, jadi
                    tetap satu form HTML yang biasa dan tetap punya CSRF
                    token-nya sendiri.
                --}}
                <div class="mt-4" data-dialog-bagian-tolak hidden>
                    <form method="POST" action="" id="form-tolak-verifikasi" data-dialog-form-tolak>
                        @csrf

                        <input type="hidden" name="status" value="{{ \App\Models\Materi::STATUS_PENDING }}">
                        <input type="hidden" name="kembali" value="admin.verifikasi">

                        <label class="ad-field__label" for="alasan-tolak">Alasan penolakan</label>

                        <p class="ad-field__petunjuk">
                            Alasan ini dibaca oleh pemilik konten, jadi tulis apa yang perlu
                            diperbaiki, bukan hanya "ditolak".
                        </p>

                        <textarea class="ad-area mt-1.5" id="alasan-tolak" name="alasan" rows="3" required
                            maxlength="500" data-dialog-alasan data-awal="{{ old('alasan') }}"
                            placeholder="Contoh: Materi ini belum lengkap, tolong tambahkan contoh kode pada setiap bab."></textarea>
                    </form>
                </div>
            </div>

            <footer class="ad-dialog__kaki">
                <button type="button" class="ad-tombol ad-tombol--garis" data-dialog-tutup>Batal</button>

                <button type="submit" class="ad-tombol ad-tombol--bahaya" form="form-tolak-verifikasi"
                    data-dialog-tolak-tombol>
                    <x-admin.ikon nama="silang" ukuran="w-4 h-4" />
                    Tolak
                </button>

                <button type="submit" class="ad-tombol ad-tombol--sukses" form="form-setujui-verifikasi"
                    data-dialog-setujui>
                    <x-admin.ikon nama="centang" ukuran="w-4 h-4" />
                    Setujui
                </button>
            </footer>

            {{--
                Form Setujui tidak terlihat, tapi tetap ada di DOM supaya
                tombolnya bisa memakai atribut form=. Field "status" dan
                "kembali" sama persis dengan form yang sebelumnya tertanam
                langsung di tiap baris.
            --}}
            <form method="POST" action="" id="form-setujui-verifikasi" data-dialog-form-setujui hidden>
                @csrf

                <input type="hidden" name="status" value="{{ \App\Models\Materi::STATUS_PENDING }}">
                <input type="hidden" name="kembali" value="admin.verifikasi">
            </form>
        </div>
    </div>

@endsection
