@extends('layouts.app')

@section('title', 'Hasil ' . ($quiz?->judul ?? 'Quiz') . ' | KelasKita')

@section('content')
    <div class="kanvas-halaman -m-6 min-h-[calc(100dvh-4rem)] p-6 lg:-m-10 lg:p-10">
        <div class="mx-auto w-full max-w-3xl">

            {{-- ======================= KEPALA ======================= --}}
            <section class="hasil-panel">
                <div class="hasil-panel__kepala">
                    <a href="{{ route('user.hasil') }}"
                        class="hasil-kembali">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>

                        Semua Hasil
                    </a>

                    <span class="hasil-status hasil-status--{{ $status }}">{{ $statusLabel }}</span>
                </div>

                <div class="hasil-panel__badan">

                    <p class="hasil-detail__kategori">{{ $kategori['nama'] }}</p>

                    <h1 class="hasil-detail__judul">{{ $quiz?->judul ?? 'Quiz sudah dihapus' }}</h1>

                    {{-- Nilai besar di tengah: satu angka yang paling mudah
                         dibaca sekilas, sama seperti di halaman hasil sesi. --}}
                    <div class="hasil-detail__nilai-box">
                        <p class="hasil-detail__nilai">
                            {{ $pengerjaan->nilai }}<span>/100</span>
                        </p>

                        <p class="hasil-detail__nilai-label">
                            @if ($status === 'proses')
                                Nilai sementara
                            @else
                                Nilai kamu
                            @endif
                        </p>
                    </div>

                    {{-- Rincian jawaban: benar, salah, dijawab, dan tidak
                         dijawab. --}}
                    <div class="mt-5 grid grid-cols-2 gap-2.5 text-center sm:grid-cols-4">
                        <div class="hasil-detail__kotak">
                            <p class="hasil-detail__kotak-nilai hasil-detail__kotak-nilai--benar">{{ $benar }}</p>
                            <p class="hasil-detail__kotak-label">Benar</p>
                        </div>

                        <div class="hasil-detail__kotak">
                            <p class="hasil-detail__kotak-nilai hasil-detail__kotak-nilai--salah">{{ $salah }}</p>
                            <p class="hasil-detail__kotak-label">Salah</p>
                        </div>

                        <div class="hasil-detail__kotak">
                            <p class="hasil-detail__kotak-nilai">{{ $dijawab }}</p>
                            <p class="hasil-detail__kotak-label">Dijawab</p>
                        </div>

                        <div class="hasil-detail__kotak">
                            <p class="hasil-detail__kotak-nilai">{{ $belumDijawab }}</p>
                            <p class="hasil-detail__kotak-label">Tidak Dijawab</p>
                        </div>
                    </div>

                    {{-- Meta pengerjaan. --}}
                    <dl class="hasil-detail__meta">
                        <div class="hasil-detail__meta-item">
                            <dt>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('dokumen') }}" />
                                </svg>

                                Jumlah Soal
                            </dt>

                            <dd>{{ $jumlahSoal }}</dd>
                        </div>

                        <div class="hasil-detail__meta-item">
                            <dt>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('jam') }}" />
                                </svg>

                                Durasi Pengerjaan
                            </dt>

                            <dd>{{ $durasi['label'] }}</dd>
                        </div>

                        <div class="hasil-detail__meta-item">
                            <dt>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('kalender') }}" />
                                </svg>

                                Tanggal Pengerjaan
                            </dt>

                            <dd>
                                {{ ($pengerjaan->selesai_pada ?? $pengerjaan->dimulai_pada)?->translatedFormat('d M Y · H:i') ?? '-' }}
                            </dd>
                        </div>

                        <div class="hasil-detail__meta-item">
                            <dt>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ \App\Support\Ikon::path('centang') }}" />
                                </svg>

                                Status
                            </dt>

                            <dd>{{ $statusLabel }}</dd>
                        </div>
                    </dl>

                    @if ($adaRiwayatSesi && $tautanSesi !== null)
                        <p class="mt-4 text-xs text-dark/45">
                            Pengerjaan ini bagian dari sesi live. Rekap nilai seluruh peserta ada di
                            <a href="{{ $tautanSesi }}" class="font-semibold text-primary hover:text-primary-dark">halaman hasil sesi</a>.
                        </p>
                    @endif
                </div>
            </section>

            {{-- ======================= DAFTAR SOAL ======================= --}}
            <section class="hasil-panel mt-5">
                <div class="hasil-panel__kepala">
                    <h2 class="hasil-panel__judul">Jawabanmu per Soal</h2>

                    <span class="hasil-panel__lencana">{{ count($daftarSoal) }} soal</span>
                </div>

                <div class="hasil-panel__badan">
                    @forelse ($daftarSoal as $soal)
                        <article class="hasil-soal">

                            <header class="hasil-soal__kepala">
                                <span class="hasil-soal__nomor">{{ $soal['nomor'] }}</span>

                                <span class="hasil-soal__pertanyaan">{{ $soal['pertanyaan'] }}</span>

                                <span class="hasil-status hasil-status--{{ match ($soal['status']) {
                                    'benar' => 'selesai',
                                    'salah' => 'gagal',
                                    'menunggu' => 'proses',
                                    default => 'proses',
                                } }}">
                                    @switch($soal['status'])
                                        @case('benar')
                                            Benar
                                            @break

                                        @case('salah')
                                            Salah
                                            @break

                                        @case('menunggu')
                                            Belum Dinilai
                                            @break

                                        @default
                                            Tidak Dijawab
                                    @endswitch
                                </span>
                            </header>

                            @if (in_array($soal['tipe'], [\App\Models\Soal::TIPE_JAWABAN_SINGKAT, \App\Models\Soal::TIPE_PARAGRAF], true))
                                {{--
                                    Soal bertipe teks tidak punya pilihan
                                    jawaban, jadi yang ditampilkan adalah
                                    jawaban peserta sendiri. Kunci acuan
                                    hanya ditampilkan kalau quiz-nya
                                    mengizinkan jawaban dibuka.
                                --}}
                                <p class="hasil-soal__teks">
                                    <span class="hasil-soal__teks-label">Jawabanmu</span>
                                    {{ filled($soal['jawaban_teks']) ? $soal['jawaban_teks'] : 'Tidak dijawab.' }}
                                </p>
                            @else
                                {{-- Semua pilihan ditampilkan, jawaban peserta dan
                                     kunci ditandai supaya mudah dibandingkan. --}}
                                <ul class="hasil-soal__pilihan-daftar">
                                    @foreach ($soal['pilihan'] as $huruf => $teks)
                                        @php
                                            $terpilih = in_array($huruf, $soal['terpilih_huruf'], true);
                                            $kunci = $soal['benar'] === $huruf;

                                            $kelas = match (true) {
                                                $terpilih && $kunci => 'hasil-soal__pilihan--benar',
                                                $terpilih => 'hasil-soal__pilihan--salah',
                                                $kunci => 'hasil-soal__pilihan--kunci',
                                                default => '',
                                            };
                                        @endphp

                                        <li @class(['hasil-soal__pilihan', $kelas])>
                                            <span class="hasil-soal__huruf">{{ $huruf }}</span>

                                            <span class="hasil-soal__pilihan-teks">{{ $teks }}</span>

                                            @if ($terpilih)
                                                <span class="hasil-soal__tag hasil-soal__tag--terpilih">Jawabanmu</span>
                                            @endif

                                            @if ($kunci)
                                                <span class="hasil-soal__tag hasil-soal__tag--kunci">Kunci</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @if (filled($soal['pembahasan']))
                                <p class="hasil-soal__pembahasan">
                                    <span class="hasil-soal__pembahasan-label">Pembahasan</span>
                                    {{ $soal['pembahasan'] }}
                                </p>
                            @endif
                        </article>
                    @empty
                        <p class="hasil-panel__kosong">
                            Soal quiz ini sudah tidak tersedia, jadi rincian jawaban tidak bisa ditampilkan.
                        </p>
                    @endforelse
                </div>
            </section>

            <div class="mt-5 flex flex-wrap justify-center gap-3">
                <a href="{{ route('user.hasil') }}" class="tombol-garis">Kembali ke Hasil</a>

                @if ($quiz !== null)
                    <a href="{{ route('user.quiz.detail', $quiz) }}" class="tombol-garis">Lihat Halaman Quiz</a>
                @endif
            </div>
        </div>
    </div>
@endsection
