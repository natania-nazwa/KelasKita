@extends('layouts.admin')

@section('title', 'Verifikasi Konten | KelasKita')

@section('content')

    {{--
        Halaman "Verifikasi Konten": satu tempat admin memeriksa materi
        dan quiz buatan pengguna sebelum keduanya dipublikasikan.

        Susunannya tiga blok, dari atas ke bawah:
          1. hero, 2. kartu filter (tab jenis + pil status + filter
          tambahan), 3. grid dua kolom: daftar di kiri, panel review di
          kanan.

        Panel di kanan dirender dari partial verifikasi-panel.blade.php.
        Saat admin memilih baris lain, JavaScript mengambil partial yang
        sama lewat GET admin/verifikasi/panel dan mengganti isi panel.
        Tanpa JavaScript, tautan tiap baris tetap membuka halaman penuh
        dengan query "pilih", jadi panel tetap terisi dengan cara biasa.

        Halaman ini tidak pernah memutuskan apa pun sendiri. Tombol
        Setujui dan Tolak membuka dialog, dan dialog itu yang mengirim
        POST ke route yang sudah ada (admin.materi.setujui /
        admin.materi.tolak, dan padanannya untuk quiz) dengan field
        "kembali" supaya admin kembali ke halaman ini.

        Quiz mode kode tidak pernah muncul di sini: quiz seperti itu
        tidak tayang untuk semua pengguna, jadi tidak ada yang perlu
        disetujui admin.
    --}}

    @php
        /*
         * Tab dibangun di sini, bukan di controller, supaya controller
         * tidak perlu tahu bentuk URL. Yang dikirim ke view cukup nilai
         * tab yang aktif dan jumlahnya.
         *
         * Query "q" dan "kategori" ikut dibawa supaya berganti tab tidak
         * menghapus pencarian yang sedang berjalan. Query "pilih" sengaja
         * dibuang: pilihan panel mengikuti isi tab yang baru.
         */
        $dasar = array_filter([
            'q' => $kataKunci,
            'kategori' => $kategoriAktif,
        ], fn (string $nilai): bool => $nilai !== '');

        $tabJenis = collect($pilihanJenis)
            ->map(fn (string $label, string $nilai): array => [
                'label' => $label,
                'nilai' => $nilai,
                'jumlah' => $jumlahJenis[$nilai] ?? 0,
                'href' => route('admin.verifikasi', [
                    'jenis' => $nilai,
                    'status' => $statusAktif,
                    ...$dasar,
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
                    ...$dasar,
                ]),
            ])
            ->all();

        $ikonJenis = ['semua' => 'papan', 'materi' => 'buku', 'quiz' => 'file-teks'];
        $ikonStatus = ['menunggu' => 'jam', 'disetujui' => 'centang', 'ditolak' => 'silang'];

        /*
         * Tautan pilihan panel: mempertahankan seluruh query yang sedang
         * aktif (tab, pencarian, kategori, halaman) supaya memilih baris
         * tidak menggugurkan filter.
         */
        $tautanPilih = fn (string $jenis, int $id): string => route('admin.verifikasi', array_filter([
            'jenis' => $jenisAktif,
            'status' => $statusAktif,
            'q' => $kataKunci,
            'kategori' => $kategoriAktif,
            'page' => $paginasi->currentPage() > 1 ? $paginasi->currentPage() : null,
            'pilih' => $jenis.':'.$id,
        ], fn (mixed $nilai): bool => filled($nilai)));

        $terpilih = filled($pilih) ? explode(':', $pilih, 2) : null;
        $terpilihJenis = $terpilih[0] ?? null;
        $terpilihId = isset($terpilih[1]) ? (int) $terpilih[1] : 0;

        $dari = $paginasi->firstItem();
        $sampai = $paginasi->lastItem();
    @endphp

    {{--
        Kelas "ad-vf--dengan-panel" menentukan bentuk grid: tanpa kelas ini
        daftar konten memakai lebar penuh dan panel review disembunyikan;
        dengan kelasnya, halaman beralih ke dua kolom (daftar + panel).
        Nilainya diambil dari $rincian, jadi tautan "pilih" dari URL tetap
        membuka dua kolom walaupun JavaScript mati.
    --}}
    <div class="ad-vf{{ $rincian !== null ? ' ad-vf--dengan-panel' : '' }}" data-vf-halaman
        data-vf-panel-url="{{ route('admin.verifikasi.panel') }}" data-vf-url="{{ route('admin.verifikasi') }}">

        {{-- ==================== PESAN ==================== --}}
        @if (session('sukses'))
            <div class="ad-vf-toast" data-vf-toast role="status">
                <span class="ad-vf-toast__ikon" aria-hidden="true">
                    <x-admin.ikon nama="tanda-centang" ukuran="w-4 h-4" :tebal="2.4" />
                </span>

                <p class="ad-vf-toast__teks">{{ session('sukses') }}</p>

                <button type="button" class="ad-vf-toast__tutup" data-vf-toast-tutup aria-label="Tutup notifikasi">
                    <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
                </button>
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

        {{-- ==================== HERO ==================== --}}
        <section class="ad-seksi ad-vf-hero">
            <div class="ad-vf-hero__teks">
                <span class="ad-vf-hero__ikon" aria-hidden="true">
                    <x-admin.ikon nama="buku-centang" ukuran="w-7 h-7" :tebal="1.9" />
                </span>

                <div class="min-w-0">
                    <p class="ad-vf-hero__lencana">
                        <x-admin.ikon nama="kilau" ukuran="w-3.5 h-3.5" />
                        Ruang Periksa Admin
                    </p>

                    <h1 class="ad-vf-hero__judul">Verifikasi Konten</h1>

                    <p class="ad-vf-hero__sub">
                        Tinjau materi dan quiz yang dibuat oleh pengguna sebelum dipublikasikan.
                    </p>
                </div>
            </div>

            <div class="ad-vf-hero__kanan">
                <div class="ad-vf-hero__ilustrasi">
                    <img src="{{ asset('images/cover.png') }}" alt="Sampul KelasKita: ilustrasi materi dan quiz"
                        width="828" height="552" loading="eager" />
                </div>
            </div>
        </section>

        {{-- ==================== FILTER ==================== --}}
        <section class="ad-seksi ad-vf-alat">
            <div class="ad-vf-alat__baris">
                <nav class="ad-tab ad-vf-alat__jenis" aria-label="Filter jenis konten">
                    @foreach ($tabJenis as $butir)
                        <a href="{{ $butir['href'] }}"
                            class="ad-tab__item{{ $butir['nilai'] === $jenisAktif ? ' ad-tab__item--aktif' : '' }}"
                            @if ($butir['nilai'] === $jenisAktif) aria-current="page" @endif>
                            <x-admin.ikon :nama="$ikonJenis[$butir['nilai']]" ukuran="w-4 h-4" />

                            {{ $butir['label'] }}

                            <span class="ad-tab__jumlah">{{ $butir['jumlah'] }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="ad-vf-status">
                    <span class="ad-vf-status__label" id="vf-status-label">Status</span>

                    <div class="ad-vf-status__pil" role="group" aria-labelledby="vf-status-label">
                        @foreach ($tabStatus as $butir)
                            <a href="{{ $butir['href'] }}"
                                class="ad-vf-pil ad-vf-pil--{{ $butir['nilai'] }}{{ $butir['nilai'] === $statusAktif ? ' ad-vf-pil--aktif' : '' }}"
                                @if ($butir['nilai'] === $statusAktif) aria-current="page" @endif>
                                <x-admin.ikon :nama="$ikonStatus[$butir['nilai']]" ukuran="w-3.5 h-3.5" />

                                {{ $butir['label'] }}

                                <b>{{ $butir['jumlah'] }}</b>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="ad-vf-alat__aksi">
                    <button type="button" class="ad-vf-alat__tombol" data-vf-saring-buka
                        aria-expanded="false" aria-controls="vf-saring" aria-label="Buka filter tambahan"
                        title="Filter tambahan">
                        <x-admin.ikon nama="filter" ukuran="w-4 h-4" />

                        @if (filled($kataKunci) || filled($kategoriAktif))
                            <span class="ad-vf-alat__titik" aria-hidden="true"></span>
                        @endif
                    </button>
                </div>
            </div>

            <form class="ad-vf-saring" id="vf-saring" data-vf-saring method="GET"
                action="{{ route('admin.verifikasi') }}" @unless (filled($kataKunci) || filled($kategoriAktif)) hidden @endunless>
                <input type="hidden" name="jenis" value="{{ $jenisAktif }}">
                <input type="hidden" name="status" value="{{ $statusAktif }}">

                <div class="ad-field ad-vf-saring__field">
                    <label class="ad-field__label" for="vf-q">Cari judul atau pembuat</label>

                    <div class="ad-cari ad-vf-saring__cari">
                        <x-admin.ikon nama="cari" class="ad-cari__ikon" />

                        <input id="vf-q" name="q" type="search" value="{{ $kataKunci }}"
                            placeholder="Ketik nama materi, quiz, atau pembuat..." autocomplete="off">
                    </div>
                </div>

                <div class="ad-field ad-vf-saring__field">
                    <label class="ad-field__label" for="vf-kategori">Kategori</label>

                    <div class="ad-pilih__bungkus">
                        <select class="ad-pilih" id="vf-kategori" name="kategori">
                            <option value="">Semua kategori</option>

                            @foreach ($daftarPelajaran as $pelajaran)
                                <option value="{{ $pelajaran->slug }}" @selected($kategoriAktif === $pelajaran->slug)>
                                    {{ $pelajaran->nama }}
                                </option>
                            @endforeach
                        </select>

                        <x-admin.ikon nama="panah-bawah" />
                    </div>
                </div>

                <div class="ad-vf-saring__aksi">
                    <button type="submit" class="ad-tombol ad-tombol--utama">
                        <x-admin.ikon nama="cari" ukuran="w-4 h-4" />
                        Terapkan
                    </button>

                    <a class="ad-tombol ad-tombol--garis" href="{{ route('admin.verifikasi') }}">Reset</a>
                </div>
            </form>
        </section>

        {{-- ==================== DAFTAR + PANEL ==================== --}}
        <div class="ad-seksi ad-vf-konten">

            {{-- ---------- Kolom kiri: daftar ---------- --}}
            <div class="ad-vf-daftar">
                <header class="ad-vf-daftar__kepala">
                    <div class="min-w-0">
                        <h2 class="ad-vf-daftar__judul">Daftar Konten</h2>

                        <p class="ad-vf-daftar__sub">
                            {{ $pilihanStatus[$statusAktif] }} &middot;
                            {{ $paginasi->total() }} konten
                            @if ($jenisAktif !== 'semua')
                                &middot; {{ $pilihanJenis[$jenisAktif] }}
                            @endif
                            @if (filled($kategoriAktif))
                                &middot; {{ $daftarPelajaran->firstWhere('slug', $kategoriAktif)->nama ?? $kategoriAktif }}
                            @endif
                        </p>
                    </div>
                </header>

                <div class="ad-vf-daftar__badan" data-vf-daftar>
                    @if ($daftar === [])
                        <x-admin.kosong ikon="buku-centang" judul="Semua sudah diperiksa"
                            teks="Saat ini tidak ada materi atau quiz yang menunggu verifikasi." />
                    @else
                        @foreach ($daftar as $baris)
                            @php
                                $aktif = $terpilihJenis === $baris['jenis'] && $terpilihId === (int) $baris['id'];
                                $menunggu = $baris['status'] === \App\Models\Materi::STATUS_PENDING;
                            @endphp

                            <a class="ad-vf-item{{ $aktif ? ' ad-vf-item--aktif' : '' }}"
                                href="{{ $tautanPilih($baris['jenis'], (int) $baris['id']) }}" data-vf-item
                                data-jenis="{{ $baris['jenis'] }}" data-id="{{ $baris['id'] }}"
                                data-setujui="{{ $menunggu ? $baris['tautan_setujui'] : '' }}"
                                data-tolak="{{ $menunggu ? $baris['tautan_tolak'] : '' }}"
                                data-judul="{{ $baris['judul'] }}"
                                @if ($aktif) aria-current="true" @endif>

                                <span class="ad-vf-item__ikon ad-vf-item__ikon--{{ $baris['jenis'] }}"
                                    aria-hidden="true">
                                    <x-admin.ikon :nama="$ikonJenis[$baris['jenis']]" ukuran="w-5 h-5" />
                                </span>

                                <span class="ad-vf-item__isi">
                                    <span class="ad-vf-item__baris">
                                        <span
                                            class="ad-vf-item__jenis ad-vf-item__jenis--{{ $baris['jenis'] }}">
                                            {{ $baris['label_jenis'] }}
                                        </span>

                                        <span class="ad-vf-item__judul">{{ $baris['judul'] }}</span>
                                    </span>

                                    <span class="ad-vf-item__meta">
                                        <span class="ad-vf-item__meta-butir">
                                            <x-admin.ikon nama="orang" ukuran="w-3 h-3" />
                                            Dibuat oleh <b>{{ $baris['pembuat']['nama'] }}</b>
                                        </span>

                                        <span class="ad-vf-item__meta-butir">
                                            <x-admin.ikon nama="markah" ukuran="w-3 h-3" />
                                            {{ $baris['kategori']['nama'] }}
                                        </span>

                                        <span class="ad-vf-item__meta-butir">
                                            <x-admin.ikon
                                                :nama="$baris['jenis'] === 'quiz' ? 'daftar-cek' : 'buku'"
                                                ukuran="w-3 h-3" />
                                            {{ $baris['rincian'] }}
                                        </span>

                                        <span class="ad-vf-item__meta-butir">
                                            <x-admin.ikon nama="kalender" ukuran="w-3 h-3" />
                                            {{ $baris['dibuat_pada']?->translatedFormat('d M Y') }}
                                        </span>
                                    </span>
                                </span>

                                <span class="ad-vf-item__kanan">
                                    <x-admin.tanda :status="$baris['status']" />

                                    <span
                                        class="ad-tombol ad-tombol--kecil {{ $menunggu ? 'ad-tombol--utama' : 'ad-tombol--garis' }}">
                                        {{ $menunggu ? 'Tinjau' : 'Lihat' }}
                                        <x-admin.ikon nama="panah-kanan" ukuran="w-3.5 h-3.5" />
                                    </span>
                                </span>
                            </a>
                        @endforeach
                    @endif
                </div>

                @if ($dari !== null && $sampai !== null)
                    <footer class="ad-vf-daftar__kaki">
                        <p class="ad-vf-daftar__hitung">
                            Menampilkan {{ $dari }}&ndash;{{ $sampai }} dari {{ $paginasi->total() }} data
                        </p>

                        {{ $paginasi->links() }}
                    </footer>
                @endif
            </div>

            {{-- ---------- Kolom kanan: panel review ---------- --}}
            <aside class="ad-vf-panel" aria-label="Panel review" data-vf-panel>
                @include('admin.verifikasi-panel', ['rincian' => $rincian])
            </aside>
        </div>

        {{--
            ==================== DIALOG SETUJUI ====================

            Satu dialog untuk semua baris, sama seperti dialog lama:
            URL keputusan diambil dari data-* panel yang sedang dibuka,
            bukan ditulis ulang per baris.
        --}}
        <div class="ad-dialog" data-vf-dialog="setujui" role="dialog" aria-modal="true" aria-hidden="true"
            aria-labelledby="vf-setujui-judul">
            <div class="ad-dialog__kartu">
                <header class="ad-dialog__kepala">
                    <span class="ad-vf-dialog__ikon ad-vf-dialog__ikon--sukses" aria-hidden="true">
                        <x-admin.ikon nama="centang" ukuran="w-5 h-5" :tebal="2.2" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="ad-dialog__judul" id="vf-setujui-judul">Setujui Konten?</h2>

                        <p class="ad-teks-2 mt-0.5 !text-xs" data-vf-dialog-meta></p>
                    </div>

                    <button type="button" class="ad-dialog__tutup" data-vf-dialog-tutup aria-label="Tutup">
                        <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
                    </button>
                </header>

                <div class="ad-dialog__badan">
                    <div class="ad-alert ad-alert--sukses">
                        <span class="ad-alert__ikon" aria-hidden="true">
                            <x-admin.ikon nama="centang" ukuran="w-3.5 h-3.5" :tebal="2.6" />
                        </span>

                        <p class="min-w-0 font-medium">
                            Konten akan langsung tayang dan dapat diakses oleh semua pengguna.
                        </p>
                    </div>

                    <p class="ad-field__petunjuk mt-3">
                        Setelah disetujui, kamu kembali ke halaman Verifikasi dengan daftar dan
                        angka status yang sudah diperbarui.
                    </p>
                </div>

                <footer class="ad-dialog__kaki">
                    <button type="button" class="ad-tombol ad-tombol--garis" data-vf-dialog-tutup>Batal</button>

                    <button type="submit" class="ad-tombol ad-tombol--sukses" form="vf-form-setujui"
                        data-vf-dialog-kirim>
                        <x-admin.ikon nama="centang" ukuran="w-4 h-4" />
                        Setujui
                    </button>
                </footer>

                <form method="POST" action="" id="vf-form-setujui" data-vf-form="setujui" hidden>
                    @csrf

                    <input type="hidden" name="status" value="{{ \App\Models\Materi::STATUS_PENDING }}">
                    <input type="hidden" name="kembali" value="admin.verifikasi">
                </form>
            </div>
        </div>

        {{--
            ==================== DIALOG TOLAK ====================

            Alasan penolakan wajib diisi: TolakMateriRequest menolak POST
            tanpa "alasan", dan alasan itu yang dikirim ke pemilik konten.
        --}}
        <div class="ad-dialog" data-vf-dialog="tolak" role="dialog" aria-modal="true" aria-hidden="true"
            aria-labelledby="vf-tolak-judul">
            <div class="ad-dialog__kartu">
                <header class="ad-dialog__kepala">
                    <span class="ad-vf-dialog__ikon ad-vf-dialog__ikon--bahaya" aria-hidden="true">
                        <x-admin.ikon nama="silang" ukuran="w-5 h-5" :tebal="2.2" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="ad-dialog__judul" id="vf-tolak-judul">Tolak Konten?</h2>

                        <p class="ad-teks-2 mt-0.5 !text-xs" data-vf-dialog-meta></p>
                    </div>

                    <button type="button" class="ad-dialog__tutup" data-vf-dialog-tutup aria-label="Tutup">
                        <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
                    </button>
                </header>

                <form method="POST" action="" id="vf-form-tolak" data-vf-form="tolak" class="ad-vf-dialog-form">
                    <div class="ad-dialog__badan">
                        <div class="ad-alert ad-alert--bahaya">
                            <span class="ad-alert__ikon" aria-hidden="true">
                                <x-admin.ikon nama="peringatan" ukuran="w-3.5 h-3.5" :tebal="2.6" />
                            </span>

                            <p class="min-w-0 font-medium">
                                Konten tidak akan tayang, dan pemiliknya menerima alasan yang kamu tulis.
                            </p>
                        </div>

                        <div class="ad-field mt-4">
                            <label class="ad-field__label" for="vf-alasan">Alasan penolakan</label>

                            <p class="ad-field__petunjuk">
                                Alasan ini dibaca oleh pemilik konten, jadi tulis apa yang perlu
                                diperbaiki, bukan hanya "ditolak".
                            </p>

                            <textarea class="ad-area mt-1.5" id="vf-alasan" name="alasan" rows="4" required
                                maxlength="500" data-vf-alasan data-awal="{{ old('alasan') }}"
                                placeholder="Contoh: Materi ini belum lengkap, tolong tambahkan contoh kode pada setiap bab."></textarea>
                        </div>
                    </div>

                    <footer class="ad-dialog__kaki">
                        <button type="button" class="ad-tombol ad-tombol--garis" data-vf-dialog-tutup>Batal</button>

                        <button type="submit" class="ad-tombol ad-tombol--bahaya" data-vf-dialog-kirim>
                            <x-admin.ikon nama="silang" ukuran="w-4 h-4" />
                            Tolak
                        </button>
                    </footer>

                    @csrf

                    <input type="hidden" name="status" value="{{ \App\Models\Materi::STATUS_PENDING }}">
                    <input type="hidden" name="kembali" value="admin.verifikasi">
                </form>
            </div>
        </div>
    </div>

@endsection
