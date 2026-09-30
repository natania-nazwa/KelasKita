@extends('layouts.admin')

@section('title', 'Hasil & Statistik | KelasKita')

@section('content')

    {{--
        Halaman "Hasil & Statistik": rekap pembelajaran di seluruh platform.

        Halaman ini sepenuhnya membaca, dan semua angkanya dihitung di
        App\Support\StatistikAdmin, kelas yang sama dengan yang dipakai
        dashboard. Jadi angka "Total Quiz Dikerjakan" di sini selalu sama
        dengan angka di kartu statistik dashboard, bukan dua hasil hitung
        yang bisa berbeda.
    --}}

    @php
        $jumlah = fn (string $kunci): int => (int) array_sum(array_column($aktivitasBelajar, $kunci));
    @endphp

    <x-admin.kepala judul="Hasil & Statistik"
        subjudul="Rekap belajar siswa, nilai, dan pelajaran yang paling sering dikerjakan." />

    {{-- ==================== STATISTIK UTAMA ==================== --}}
    <section class="ad-seksi ad-grid ad-grid--statistik" aria-label="Ringkasan hasil belajar">
        <x-admin.statistik ikon="soal" label="Total Quiz Dikerjakan" :nilai="$ringkasan['quiz_dikerjakan']" nada="sukses"
            :keterangan="count($aktivitasBelajar).' hari terakhir: '.$jumlah('quiz_dikerjakan').' dikerjakan'" />

        <x-admin.statistik ikon="target" label="Rata-rata Nilai"
            :nilai="rtrim(rtrim(number_format($nilai['rata'], 1, ',', ''), '0'), ',')"
            :keterangan="'Tertinggi '.$nilai['tertinggi'].' · 30 hari terakhir'" />

        <x-admin.statistik ikon="buku" label="Materi Dipelajari" :nilai="$ringkasan['materi_dipelajari']"
            keterangan="Materi yang disimpan siswa" />

        <x-admin.statistik ikon="grup" label="Pengguna Aktif" :nilai="$ringkasan['pengguna_aktif']" nada="info"
            :keterangan="'Dari '.$ringkasan['pengguna'].' pengguna · login 24 jam terakhir'" />
    </section>

    {{-- ==================== DUA GRAFIK GARIS ==================== --}}
    <section class="ad-seksi ad-grid ad-grid--seri">
        @foreach ([
            [
                'ikon' => 'grup',
                'judul' => 'Pengguna yang Login',
                'sub' => 'Pengguna unik per hari, 7 hari terakhir',
                'data' => $trenLogin,
                'warna' => '#6D4AFF',
                'label' => 'pengguna unik',
            ],
            [
                'ikon' => 'naik',
                'judul' => 'Pengguna Baru',
                'sub' => 'Akun yang mendaftar per hari',
                'data' => $trenPenggunaBaru,
                'warna' => '#5B8DEF',
                'label' => 'pengguna baru',
            ],
        ] as $grafik)
            <div class="ad-kartu">
                <header class="ad-kartu__kepala">
                    <div class="ad-kartu__kepala-titik">
                        <span class="ad-cepat__ikon" aria-hidden="true">
                            <x-admin.ikon :nama="$grafik['ikon']" ukuran="w-5 h-5" />
                        </span>

                        <div class="min-w-0">
                            <h2 class="ad-kartu__kepala-judul">{{ $grafik['judul'] }}</h2>
                            <p class="ad-kartu__subjudul">{{ $grafik['sub'] }}</p>
                        </div>
                    </div>
                </header>

                <div class="ad-kartu__badan">
                    <x-admin.grafik-garis :data="$grafik['data']" :warna="$grafik['warna']"
                        :label-nilai="$grafik['label']" />
                </div>
            </div>
        @endforeach
    </section>

    {{-- ==================== AKTIVITAS BELAJAR ==================== --}}
    <section class="ad-seksi">
        <div class="ad-kartu">
            <header class="ad-kartu__kepala">
                <div class="ad-kartu__kepala-titik">
                    <span class="ad-cepat__ikon" aria-hidden="true">
                        <x-admin.ikon nama="grafik" ukuran="w-5 h-5" />
                    </span>

                    <div class="min-w-0">
                        <h2 class="ad-kartu__kepala-judul">Aktivitas Belajar</h2>
                        <p class="ad-kartu__subjudul">Quiz dikerjakan, materi dibaca, dan pengguna baru</p>
                    </div>
                </div>
            </header>

            <div class="ad-kartu__badan">
                <x-admin.grafik-batang :data="array_map(
                    fn (array $hari): array => [
                        'label' => $hari['label'],
                        'nilai' => $hari['quiz_dikerjakan'],
                    ],
                    $aktivitasBelajar
                )" label-nilai="quiz dikerjakan" />

                {{--
                    Legenda memakai angka yang sama dengan deret di atas
                    grafik, jadi ketiga aktivitas ini bisa dibandingkan
                    satu sama lain tanpa harus ditotalkan di kepala.
                --}}
                <div class="ad-legenda">
                    @foreach ([
                        ['Quiz dikerjakan', '#6D4AFF', $jumlah('quiz_dikerjakan')],
                        ['Materi dibaca', '#35B779', $jumlah('materi_dibaca')],
                        ['Pengguna baru', '#F4A340', $jumlah('pengguna_baru')],
                    ] as $item)
                        <span class="ad-legenda__item">
                            <span class="ad-legenda__titik" style="background-color: {{ $item[1] }}"
                                aria-hidden="true"></span>

                            {{ $item[0] }}
                            <span class="ad-legenda__nilai">{{ $item[2] }}</span>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== PELAJARAN TERPOPULER + PIE ==================== --}}
    <section class="ad-seksi ad-grid ad-grid--dua">
        <div class="ad-kartu">
            <header class="ad-kartu__kepala">
                <div class="ad-kartu__kepala-titik">
                    <span class="ad-cepat__ikon" aria-hidden="true">
                        <x-admin.ikon nama="piala" ukuran="w-5 h-5" />
                    </span>

                    <div class="min-w-0">
                        <h2 class="ad-kartu__kepala-judul">Pelajaran Terpopuler</h2>
                        <p class="ad-kartu__subjudul">Paling sering dikerjakan siswa</p>
                    </div>
                </div>
            </header>

            <div class="ad-kartu__badan">
                @forelse ($pelajaranTerpopuler as $butir)
                    <div class="mb-3 last:mb-0">
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span class="min-w-0 truncate font-bold text-[#29245C]">{{ $butir['label'] }}</span>

                            <span class="shrink-0 tabular-nums text-[#77739A]">
                                {{ $butir['nilai'] }}×
                                <span class="font-bold text-[#29245C]">{{ $butir['persentase'] }}%</span>
                            </span>
                        </div>

                        {{--
                            Lebar batang minimum 2%: pelajaran yang hanya
                            satu pengerjaan akan tetap terlihat ada,
                            bukan hilang jadi nol.
                        --}}
                        <div class="ad-meter mt-1.5">
                            <div class="ad-meter__isi"
                                style="width: {{ max(2, $butir['persentase']) }}%; background-color: {{ $butir['warna'] }};">
                            </div>
                        </div>
                    </div>
                @empty
                    <x-admin.kosong ikon="soal" judul="Belum ada quiz yang dikerjakan"
                        teks="Grafik ini akan terisi begitu siswa pertama selesai mengerjakan quiz." />
                @endforelse
            </div>
        </div>

        <div class="ad-kartu">
            <header class="ad-kartu__kepala">
                <div class="ad-kartu__kepala-titik">
                    <span class="ad-cepat__ikon" aria-hidden="true">
                        <x-admin.ikon nama="pie" ukuran="w-5 h-5" />
                    </span>

                    <div class="min-w-0">
                        <h2 class="ad-kartu__kepala-judul">Isi per Pelajaran</h2>
                        <p class="ad-kartu__subjudul">Materi + quiz yang tersedia</p>
                    </div>
                </div>
            </header>

            <div class="ad-kartu__badan">
                <x-admin.pie :data="$isiPelajaran" label-nilai="konten" />
            </div>
        </div>
    </section>

@endsection
