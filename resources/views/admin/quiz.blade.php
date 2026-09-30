@extends('layouts.admin')

@section('title', 'Quiz | KelasKita')

@section('content')

    {{--
        Halaman "Quiz": tempat admin melihat seluruh quiz yang ada,
        dan menyetujui atau menolak yang masih menunggu.

        Halaman dibuka ke tab "Menunggu Persetujuan" supaya pekerjaan
        yang perlu dikerjakan selalu yang pertama terlihat. Penolakan
        selalu lewat isian alasan karena alasan itu dibaca pemilik di
        "Karya Saya" dan jadi dasar pengajuan ulang.

        Quiz mode kode tidak pernah minta persetujuan: kode seperti itu
        dibuat langsung berstatus draft dan tidak pernah berstatus
        menunggu, jadi tidak pernah muncul di tab "Menunggu
        Persetujuan". Quiz seperti itu tetap bisa terlihat di tab Draft,
        dan di sana lencana mode-nya membuat jelas bahwa tidak ada yang
        perlu diputuskan.

        Yang tidak berubah dari sebelumnya: query, tab status, URL, dan
        route yang dipakai tombol keputusan.
    --}}

    @php
        $tabStatus = collect($pilihanStatus)
            ->map(fn (string $label, string $nilai): array => [
                'label' => $label,
                'nilai' => $nilai,
                'jumlah' => $jumlahStatus[$nilai] ?? 0,
                'href' => route('admin.quiz', ['status' => $nilai, 'q' => $kataKunci]),
            ])
            ->all();
    @endphp

    <x-admin.kepala judul="Quiz"
        subjudul="Kelola quiz yang dibuat dan dipublikasikan oleh pengguna." />

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

    {{-- ==================== TAB + PENCARIAN ==================== --}}
    <div class="ad-seksi ad-alat">
        <x-admin.tab :tab="$tabStatus" :aktif="$statusAktif" label="Filter status quiz" />

        <form method="GET" action="{{ route('admin.quiz') }}" class="ad-alat__kanan">
            <input type="hidden" name="status" value="{{ $statusAktif }}">

            <div class="ad-cari">
                <label for="q-quiz" class="sr-only">Cari quiz</label>

                <x-admin.ikon nama="cari" class="ad-cari__ikon" />

                <input id="q-quiz" name="q" type="search" value="{{ $kataKunci }}"
                    placeholder="Cari judul atau kategori quiz..." autocomplete="off">
            </div>

            <button type="submit" class="ad-tombol ad-tombol--garis shrink-0">Cari</button>
        </form>
    </div>

    {{-- ==================== DAFTAR QUIZ ==================== --}}
    @if ($daftar === [])
        <div class="ad-seksi">
            <x-admin.kosong ikon="soal"
                :judul="$kataKunci !== ''
                    ? 'Tidak ada quiz “'.$kataKunci.'” di tab ini.'
                    : ($statusAktif === \App\Models\Quiz::STATUS_PENDING
                        ? 'Tidak ada quiz yang menunggu persetujuan.'
                        : 'Belum ada quiz dengan status ini.')"
                teks="Quiz publik yang diajukan pengguna akan muncul di sini." />
        </div>
    @else
        <div class="ad-seksi ad-tabel__bungkus">
            <table class="ad-tabel">
                <thead>
                    <tr>
                        <th scope="col">No</th>
                        <th scope="col">Judul Quiz</th>
                        <th scope="col">Mode</th>
                        <th scope="col">Soal</th>
                        <th scope="col">Status</th>
                        <th scope="col">Pembuat</th>
                        <th scope="col">Tanggal</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($daftar as $quiz)
                        @php
                            /*
                             * Hanya quiz publik yang bisa menunggu
                             * persetujuan. Quiz mode kode dibuat langsung
                             * berstatus draft dan tidak pernah punya
                             * status menunggu, jadi kondisi kedua ini
                             * hanya jaga-jaga kalau datanya berubah
                             * lewat jalur lain.
                             */
                            $bisaPutuskan = $quiz['status'] === \App\Models\Quiz::STATUS_PENDING
                                && ! $quiz['pakai_kode'];
                        @endphp

                        <tr>
                            <td class="ad-tabel__nomor" data-label="No">
                                {{ $paginasi->firstItem() + $loop->index }}
                            </td>

                            <td data-label="Judul Quiz">
                                <div class="ad-tabel__nama">
                                    <span class="ad-tinjau__ikon" style="background-color: {{ $quiz['kategori']['warna'] }};"
                                        aria-hidden="true">
                                        {{ $quiz['kategori']['ikon'] }}
                                    </span>

                                    <span class="ad-tabel__nama-teks">
                                        <span class="ad-tabel__judul">{{ $quiz['judul'] }}</span>
                                        <span class="ad-tabel__sub">
                                            {{ $quiz['kategori']['nama'] }} ·
                                            {{ $quiz['durasi'] > 0 ? '± '.$quiz['durasi'].' menit' : 'Tanpa batas waktu' }}
                                        </span>
                                    </span>
                                </div>
                            </td>

                            <td data-label="Mode">
                                <x-admin.mode :publik="! $quiz['pakai_kode']" />
                            </td>

                            <td data-label="Soal">
                                <span class="tabular-nums">{{ $quiz['jumlah_soal'] }}</span>
                            </td>

                            <td data-label="Status">
                                <x-admin.lencana :status="$quiz['status']" :label="$quiz['status_label']" />
                            </td>

                            <td data-label="Pembuat">{{ $quiz['pembuat']['nama'] }}</td>

                            <td data-label="Tanggal">
                                <span class="tabular-nums">{{ $quiz['dibuat_pada']?->translatedFormat('d M Y') }}</span>
                            </td>

                            <td data-label="">
                                <div class="ad-tabel__aksi">
                                    <button type="button" class="ad-tombol ad-tombol--kecil {{ $bisaPutuskan ? 'ad-tombol--utama' : 'ad-tombol--garis' }}"
                                        data-dialog-buka
                                        data-dialog-judul="Quiz: {{ $quiz['judul'] }}"
                                        data-dialog-meta="{{ $quiz['kategori']['nama'] }} · {{ $quiz['jumlah_soal'] }} soal · {{ $quiz['pakai_kode'] ? 'Mode kode' : 'Mode publik' }} · oleh {{ $quiz['pembuat']['nama'] }} ({{ $quiz['dibuat_pada']?->translatedFormat('d M Y') }})"
                                        data-dialog-isi="{{ $quiz['deskripsi'] ?: 'Tanpa deskripsi.' }}"
                                        data-dialog-setujui="{{ $bisaPutuskan ? $quiz['tautan_setujui'] : '' }}"
                                        data-dialog-tolak="{{ $bisaPutuskan ? $quiz['tautan_tolak'] : '' }}">
                                        <x-admin.ikon nama="mata" ukuran="w-3.5 h-3.5" />
                                        {{ $bisaPutuskan ? 'Tinjau' : 'Lihat' }}
                                    </button>
                                </div>
                            </td>
                        </tr>

                        @if (filled($quiz['catatan_pengajuan']) || filled($quiz['catatan_admin']))
                            <tr>
                                <td colspan="8" class="!py-3">
                                    @if (filled($quiz['catatan_admin']))
                                        <p class="ad-alert ad-alert--bahaya !px-3 !py-2.5">
                                            <span class="ad-alert__ikon" aria-hidden="true">
                                                <x-admin.ikon nama="silang" ukuran="w-3 h-3" :tebal="2.6" />
                                            </span>

                                            <span class="min-w-0">
                                                <strong>Alasan ditolak admin:</strong>
                                                {{ $quiz['catatan_admin'] }}
                                            </span>
                                        </p>
                                    @endif

                                    @if (filled($quiz['catatan_pengajuan']))
                                        <p class="ad-teks-2 mt-2 !text-xs">
                                            <strong class="text-[#29245C]">Catatan pengajuan ulang:</strong>
                                            {{ $quiz['catatan_pengajuan'] }}
                                        </p>
                                    @endif

                                    @if ($quiz['jumlah_ditolak'] > 0)
                                        <p class="ad-teks-2 mt-1 !text-xs">
                                            Sudah {{ $quiz['jumlah_ditolak'] }}x ditolak
                                            (sisa pengajuan {{ $quiz['sisa_pengajuan'] }}x).
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="ad-seksi">
            {{ $paginasi->links() }}
        </div>
    @endif

    {{-- ==================== DIALOG TINJAU ==================== --}}
    <div class="ad-dialog" data-dialog role="dialog" aria-modal="true" aria-hidden="true"
        aria-labelledby="dialog-quiz-judul">
        <div class="ad-dialog__kartu">

            <header class="ad-dialog__kepala">
                <div class="min-w-0 flex-1">
                    <h2 class="ad-dialog__judul" id="dialog-quiz-judul" data-dialog-judul></h2>

                    <p class="ad-teks-2 mt-0.5 !text-xs" data-dialog-meta></p>
                </div>

                <button type="button" class="ad-dialog__tutup" data-dialog-tutup aria-label="Tutup">
                    <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
                </button>
            </header>

            <div class="ad-dialog__badan">
                <p class="ad-field__label">Deskripsi quiz</p>

                <div class="ad-isi-materi mt-2" data-dialog-isi></div>

                <div class="mt-4" data-dialog-bagian-tolak hidden>
                    <form method="POST" action="" id="form-tolak-quiz" data-dialog-form-tolak>
                        @csrf

                        <input type="hidden" name="status" value="{{ $statusAktif }}">

                        <label class="ad-field__label" for="alasan-quiz">Alasan penolakan (dibaca pemilik)</label>

                        <textarea class="ad-area mt-1.5" id="alasan-quiz" name="alasan" rows="3" required
                            maxlength="500" data-dialog-alasan data-awal="{{ old('alasan') }}"
                            placeholder="Contoh: Soal nomor 4 dan 5 punya kunci jawaban yang sama, tolong periksa kembali."></textarea>
                    </form>
                </div>
            </div>

            <footer class="ad-dialog__kaki">
                <button type="button" class="ad-tombol ad-tombol--garis" data-dialog-tutup>Batal</button>

                <button type="submit" class="ad-tombol ad-tombol--bahaya" form="form-tolak-quiz"
                    data-dialog-tolak-tombol>
                    <x-admin.ikon nama="silang" ukuran="w-4 h-4" />
                    Tolak Quiz
                </button>

                <button type="submit" class="ad-tombol ad-tombol--sukses" form="form-setujui-quiz"
                    data-dialog-setujui>
                    <x-admin.ikon nama="centang" ukuran="w-4 h-4" />
                    Setujui &amp; Terbitkan
                </button>
            </footer>

            <form method="POST" action="" id="form-setujui-quiz" data-dialog-form-setujui hidden>
                @csrf

                <input type="hidden" name="status" value="{{ $statusAktif }}">
            </form>
        </div>
    </div>

@endsection
