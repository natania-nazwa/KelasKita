{{--
    Panel review di kolom kanan halaman Verifikasi.

    File ini dirender dua kali dengan cara yang sama:
      1. saat halaman dibuka (include dari verifikasi.blade.php), dan
      2. sebagai fragment HTML saat admin memilih baris lain
         (GET admin/verifikasi/panel).

    Karena keduanya memakai file yang sama, tampilan panel tidak pernah
    berbeda antara muat awal dan muat lewat AJAX. Cukup ganti isi wadah
    [data-vf-panel] dengan hasilnya.

    $rincian null = belum ada konten dipilih (atau id tidak dikenal).
    Kondisi itu tetap menghasilkan kartu, bukan ruang kosong, supaya
    tinggi kolom tidak melompat-lompat.

    Keputusan setujui / tolak tidak diambil di sini: tombolnya hanya
    membuka dialog yang sudah ada di verifikasi.blade.php, dan dialog
    itu yang mengirim POST ke route lama.
--}}

@php
    $ada = is_array($rincian) && $rincian !== [];

    $jenis = $ada ? (string) $rincian['jenis'] : '';
    $status = $ada ? (string) $rincian['status'] : '';
    $bisaPutuskan = $ada && $status === \App\Models\Materi::STATUS_PENDING;

    $labelPanel = $ada ? (string) $rincian['label_jenis'] : 'Panel Review';
    $ikonPanel = $jenis === 'quiz' ? 'file-teks' : 'buku';

    $kategori = $ada ? $rincian['kategori'] : null;
    $pembuat = $ada ? $rincian['pembuat'] : null;
@endphp

<div class="ad-vf-panel__kartu{{ $ada ? '' : ' ad-vf-panel__kartu--kosong' }}" data-vf-kartu
    data-vf-jenis="{{ $jenis }}" data-vf-id="{{ $ada ? $rincian['id'] : '' }}"
    data-vf-setujui="{{ $bisaPutuskan ? $rincian['tautan_setujui'] : '' }}"
    data-vf-tolak="{{ $bisaPutuskan ? $rincian['tautan_tolak'] : '' }}"
    data-vf-judul="{{ $ada ? $rincian['judul'] : '' }}">

    {{-- ==================== KEPALA ==================== --}}
    <header class="ad-vf-panel__kepala">
        <button type="button" class="ad-vf-panel__panah" data-vf-kembali
            aria-label="Kembali ke daftar" title="Kembali ke daftar">
            <x-admin.ikon nama="panah-kiri" ukuran="w-4 h-4" />
        </button>

        <div class="ad-vf-panel__kepala-teks">
            <p class="ad-vf-panel__kepala-jenis">
                <x-admin.ikon :nama="$ikonPanel" ukuran="w-3.5 h-3.5" />

                {{ $labelPanel }}
            </p>

            <h2 class="ad-vf-panel__kepala-judul">
                {{ $ada ? $rincian['judul'] : 'Belum ada konten dipilih' }}
            </h2>
        </div>

        @if ($ada)
            <x-admin.tanda :status="$status" class="ad-vf-panel__tanda" />
        @endif

        <button type="button" class="ad-vf-panel__tutup" data-vf-tutup aria-label="Tutup panel review">
            <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
        </button>
    </header>

    {{-- ==================== BADAN ==================== --}}
    <div class="ad-vf-panel__badan" data-vf-badan>
        @unless ($ada)
            <x-admin.kosong ikon="buku-centang" judul="Pilih konten untuk ditinjau"
                teks="Klik salah satu baris di daftar kiri. Isi lengkapnya, catatan penolakan, dan tombol keputusan akan muncul di sini." />
        @else
            {{-- Identitas singkat konten yang sedang dibuka. --}}
            <div class="ad-vf-panel__identitas">
                <span class="ad-vf-panel__identitas-ikon" style="background-color: {{ $kategori['warna'] }};">
                    <x-admin.ikon :nama="$ikonPanel" ukuran="w-5 h-5" />
                </span>

                <div class="ad-vf-panel__identitas-teks">
                    <p class="ad-vf-panel__identitas-judul">{{ $rincian['judul'] }}</p>

                    <ul class="ad-vf-panel__chips">
                        <li class="ad-vf-panel__chip">
                            <x-admin.ikon nama="markah" ukuran="w-3 h-3" />
                            {{ $kategori['nama'] }}
                        </li>

                        <li class="ad-vf-panel__chip">
                            <x-admin.ikon nama="orang" ukuran="w-3 h-3" />
                            {{ $pembuat['nama'] }}
                        </li>

                        <li class="ad-vf-panel__chip">
                            <x-admin.ikon nama="kalender" ukuran="w-3 h-3" />
                            {{ $rincian['dibuat_pada']?->translatedFormat('d M Y') }}
                        </li>

                        <li class="ad-vf-panel__chip">
                            <x-admin.ikon :nama="$jenis === 'quiz' ? 'daftar-cek' : 'buku'" ukuran="w-3 h-3" />
                            {{ $rincian['rincian'] }}
                        </li>
                    </ul>
                </div>
            </div>

            {{-- ==================== TAB ==================== --}}
            <div class="ad-vf-tab" role="tablist" aria-label="Tampilan panel review">
                <button type="button" class="ad-vf-tab__tombol ad-vf-tab__tombol--aktif" role="tab"
                    id="vf-tab-pratinjau" aria-controls="vf-isi-pratinjau" aria-selected="true"
                    data-vf-tab="pratinjau">
                    <x-admin.ikon nama="mata" ukuran="w-3.5 h-3.5" />
                    Pratinjau
                </button>

                <button type="button" class="ad-vf-tab__tombol" role="tab" id="vf-tab-info"
                    aria-controls="vf-isi-info" aria-selected="false" data-vf-tab="info">
                    <x-admin.ikon nama="daftar" ukuran="w-3.5 h-3.5" />
                    Info Detail
                </button>
            </div>

            {{-- ==================== PRATINJAU ==================== --}}
            <div class="ad-vf-panel__isi" id="vf-isi-pratinjau" role="tabpanel"
                aria-labelledby="vf-tab-pratinjau" data-vf-isi="pratinjau">
                @if ($jenis === 'materi')
                    @foreach ($rincian['seksi'] as $seksi)
                        <section class="ad-vf-seksi">
                            <h3 class="ad-vf-seksi__judul">
                                <span class="ad-vf-seksi__nomor" aria-hidden="true">{{ $seksi['nomor'] }}</span>

                                {{ $seksi['judul'] }}
                            </h3>

                            @if ($seksi['blok'] === [])
                                <p class="ad-vf-seksi__kosong">Bagian ini belum diisi.</p>
                            @else
                                <div class="ad-vf-seksi__isi">
                                    @foreach ($seksi['blok'] as $blok)
                                        @if ($blok['tipe'] === 'kode')
                                            @php
                                                $baris = max(1, substr_count(str_replace(["\r\n", "\r"], "\n", $blok['kode']), "\n") + 1);
                                            @endphp

                                            <div class="ad-vf-kode">
                                                <pre class="ad-vf-kode__nomor"
                                                    aria-hidden="true">{{ implode("\n", range(1, $baris)) }}</pre>

                                                <pre class="ad-vf-kode__isi"><code class="font-mono">{!! $blok['sorot'] !!}</code></pre>
                                            </div>
                                        @else
                                            <x-materi.detail-blok :blok="$blok" />
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </section>
                    @endforeach
                @else
                    @if ($rincian['soal'] === [])
                        <x-admin.kosong ikon="soal" judul="Quiz ini belum punya soal"
                            teks="Belum ada soal yang dibuat pemilik quiz, jadi belum ada yang bisa dinilai dari sini." />
                    @else
                        <p class="ad-vf-petunjuk">
                            Jawaban benar ditandai hijau. Pratinjau ini hanya untuk dibaca:
                            keputusan tetap lewat tombol Setujui atau Tolak di bawah.
                        </p>

                        <ol class="ad-vf-soal">
                            @foreach ($rincian['soal'] as $nomor => $soal)
                                <li class="ad-vf-soal__item">
                                    <div class="ad-vf-soal__kepala">
                                        <span class="ad-vf-soal__nomor">{{ $nomor + 1 }}</span>

                                        <span class="ad-vf-soal__tipe">{{ $soal['tipe_label'] }}</span>
                                    </div>

                                    <p class="ad-vf-soal__teks">{{ $soal['teks'] }}</p>

                                    @if ($soal['pilihan'] !== [])
                                        <ul class="ad-vf-pilihan">
                                            @foreach ($soal['pilihan'] as $huruf => $teks)
                                                <li
                                                    class="ad-vf-pilihan__item{{ in_array($huruf, $soal['benar'], true) ? ' ad-vf-pilihan__item--benar' : '' }}">
                                                    <span class="ad-vf-pilihan__huruf" aria-hidden="true">{{ $huruf }}</span>

                                                    <span class="ad-vf-pilihan__teks">{{ $teks }}</span>

                                                    @if (in_array($huruf, $soal['benar'], true))
                                                        <x-admin.ikon nama="tanda-centang" ukuran="w-4 h-4"
                                                            class="ad-vf-pilihan__centang" />
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @elseif ($soal['kunci'] !== '')
                                        <p class="ad-vf-soal__kunci">
                                            <span class="ad-vf-soal__kunci-label">Kunci jawaban</span>

                                            {{ $soal['kunci'] }}
                                        </p>
                                    @else
                                        <p class="ad-vf-soal__kunci">
                                            <span class="ad-vf-soal__kunci-label">Penilaian</span>

                                            Dinilai manual oleh guru.
                                        </p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @endif
                @endif
            </div>

            {{-- ==================== INFO DETAIL ==================== --}}
            <div class="ad-vf-panel__isi" id="vf-isi-info" role="tabpanel" aria-labelledby="vf-tab-info"
                data-vf-isi="info" hidden>
                <dl class="ad-vf-info">
                    <div class="ad-vf-info__baris">
                        <dt>Jenis Konten</dt>
                        <dd>{{ $rincian['label_jenis'] }}</dd>
                    </div>

                    <div class="ad-vf-info__baris">
                        <dt>Kategori</dt>
                        <dd>{{ $kategori['nama'] }}</dd>
                    </div>

                    <div class="ad-vf-info__baris">
                        <dt>Dibuat Oleh</dt>
                        <dd>{{ $pembuat['nama'] }}</dd>
                    </div>

                    <div class="ad-vf-info__baris">
                        <dt>Tanggal Pengajuan</dt>
                        <dd>{{ $rincian['dibuat_pada']?->translatedFormat('d M Y, H:i') }}</dd>
                    </div>

                    @if ($jenis === 'materi')
                        <div class="ad-vf-info__baris">
                            <dt>Jumlah Bab</dt>
                            <dd>{{ $rincian['jumlah_bab'] }} bab</dd>
                        </div>

                        <div class="ad-vf-info__baris">
                            <dt>Estimasi Waktu Baca</dt>
                            <dd>{{ $rincian['waktu_baca'] }} menit</dd>
                        </div>
                    @else
                        <div class="ad-vf-info__baris">
                            <dt>Jumlah Soal</dt>
                            <dd>{{ $rincian['jumlah_soal'] }} soal</dd>
                        </div>

                        <div class="ad-vf-info__baris">
                            <dt>Mode Akses</dt>
                            <dd>{{ $rincian['pakai_kode'] ? 'Mode kode (tidak tayang publik)' : 'Publik' }}</dd>
                        </div>

                        <div class="ad-vf-info__baris">
                            <dt>Durasi</dt>
                            <dd>{{ $rincian['durasi'] > 0 ? $rincian['durasi'].' menit' : 'Tidak dibatasi' }}</dd>
                        </div>
                    @endif

                    <div class="ad-vf-info__baris">
                        <dt>Tingkat Kesulitan</dt>
                        <dd>{{ $rincian['tingkat_kesulitan'] !== '' ? $rincian['tingkat_kesulitan'] : 'Belum ditentukan' }}
                        </dd>
                    </div>

                    <div class="ad-vf-info__baris">
                        <dt>Status Verifikasi</dt>
                        <dd>
                            <x-admin.tanda :status="$status" />
                        </dd>
                    </div>

                    @if ($rincian['jumlah_ditolak'] > 0)
                        <div class="ad-vf-info__baris">
                            <dt>Riwayat Penolakan</dt>
                            <dd>Ditolak {{ $rincian['jumlah_ditolak'] }} kali</dd>
                        </div>
                    @endif
                </dl>

                @if (filled($rincian['deskripsi']))
                    <div class="ad-vf-catatan ad-vf-catatan--netral">
                        <p class="ad-vf-catatan__judul">
                            <x-admin.ikon nama="catatan" ukuran="w-3.5 h-3.5" />
                            Deskripsi
                        </p>

                        <p class="ad-vf-catatan__isi">{{ $rincian['deskripsi'] }}</p>
                    </div>
                @endif

                @if (filled($rincian['catatan_pengajuan']))
                    <div class="ad-vf-catatan ad-vf-catatan--pengajuan">
                        <p class="ad-vf-catatan__judul">
                            <x-admin.ikon nama="pena" ukuran="w-3.5 h-3.5" />
                            Catatan pengajuan dari pemilik
                        </p>

                        <p class="ad-vf-catatan__isi">{{ $rincian['catatan_pengajuan'] }}</p>
                    </div>
                @endif

                @if (filled($rincian['catatan_admin']))
                    <div class="ad-vf-catatan ad-vf-catatan--tolak">
                        <p class="ad-vf-catatan__judul">
                            <x-admin.ikon nama="peringatan" ukuran="w-3.5 h-3.5" />
                            Alasan ditolak sebelumnya
                        </p>

                        <p class="ad-vf-catatan__isi">{{ $rincian['catatan_admin'] }}</p>
                    </div>
                @endif
            </div>
        @endunless
    </div>

    {{-- ==================== KAKI ==================== --}}
    <footer class="ad-vf-panel__kaki" data-vf-kaki>
        @if ($bisaPutuskan)
            <button type="button" class="ad-tombol ad-tombol--garis" data-vf-buka-tolak>
                <x-admin.ikon nama="silang" ukuran="w-4 h-4" />
                Tolak
            </button>

            <button type="button" class="ad-tombol ad-tombol--sukses" data-vf-buka-setujui>
                <x-admin.ikon nama="centang" ukuran="w-4 h-4" />
                Setujui
            </button>
        @elseif ($ada)
            <p class="ad-vf-panel__kaki-ket">
                <x-admin.ikon :nama="$status === 'rejected' ? 'silang' : 'centang'" ukuran="w-4 h-4" />

                Status konten ini sudah final, jadi tidak ada keputusan yang bisa diambil lagi.
            </p>
        @endif
    </footer>
</div>
