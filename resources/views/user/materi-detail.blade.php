@extends('layouts.app')

@section('title', $materi->nama.' | KelasKita')

@section('content')
    @php
        use App\Models\Pelajaran;

        $warna = Pelajaran::warna($materi->pelajaran?->slug ?? '', $materi->pelajaran?->nama ?? 'Umum');
        $kesulitan = strtolower((string) $materi->tingkat_kesulitan);

        $warnaKesulitan = [
            'mudah' => 'bg-[#dcfce7] text-[#15803d]',
            'sedang' => 'bg-[#fef3c7] text-[#b45309]',
            'sulit' => 'bg-[#fee2e2] text-[#b91c1c]',
        ];
    @endphp

    <div class="kanvas-materi -m-6 min-h-screen p-6 lg:-m-10 lg:p-10">

        {{-- Kembali ke daftar; membawa filter yang sedang aktif bila ada. --}}
        <a href="{{ route('user.materi') }}"
            class="inline-flex items-center gap-2 rounded-full border border-lavender bg-white px-4 py-2 text-xs font-semibold text-dark/70 transition hover:border-primary hover:text-primary">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>

            Kembali ke daftar materi
        </a>

        <article data-reveal
            style="--k: {{ $warna['warna'] }}; --k-gelap: {{ $warna['warna_gelap'] }};"
            class="mt-4 overflow-hidden rounded-[2rem] border border-lavender bg-white shadow-[0_24px_50px_-36px_rgba(33,26,58,0.5)]">

            {{-- Kepala halaman: banner warna kategori + judul. --}}
            <header class="relative overflow-hidden px-6 pb-7 pt-8 sm:px-9 sm:pb-9 sm:pt-10"
                style="background:
                    radial-gradient(circle at 82% 12%, color-mix(in oklab, var(--k) 30%, transparent), transparent 58%),
                    linear-gradient(135deg, color-mix(in oklab, var(--k) 16%, white), color-mix(in oklab, var(--k) 34%, white));">

                <span class="pointer-events-none absolute -right-10 -top-16 h-52 w-52 rounded-full bg-white/40 blur-3xl" aria-hidden="true"></span>

                <div class="relative">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="lencana bg-white/90 text-dark/70 shadow-[0_8px_18px_-12px_rgba(33,26,58,0.6)]">
                            <span class="mr-1.5" aria-hidden="true">{{ $warna['ikon'] }}</span>

                            {{ $warna['nama'] }}
                        </span>

                        @if (filled($materi->tingkat_kesulitan))
                            <span class="lencana capitalize {{ $warnaKesulitan[$kesulitan] ?? 'bg-white/90 text-dark/60' }}">
                                {{ $materi->tingkat_kesulitan }}
                            </span>
                        @endif

                        <span
                            class="lencana bg-white/90 text-dark/60 shadow-[0_8px_18px_-12px_rgba(33,26,58,0.6)]">
                            <svg class="mr-1.5 inline h-3 w-3 align-[-1px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>

                            {{ $materi->waktuBaca() }} menit baca
                        </span>
                    </div>

                    <h1 class="mt-4 max-w-3xl text-2xl font-extrabold leading-tight tracking-tight text-dark sm:text-3xl">
                        {{ $materi->nama }}
                    </h1>

                    @if (filled($materi->deskripsi))
                        <p class="mt-2.5 max-w-2xl text-sm leading-relaxed text-dark/65">
                            {{ $materi->deskripsi }}
                        </p>
                    @endif
                </div>
            </header>

            {{-- Isi materi. --}}
            <div class="isi-materi px-6 py-7 sm:px-9 sm:py-9">
                @if (filled($materi->isi))
                    {!! nl2br(e($materi->isi)) !!}
                @else
                    <p class="text-sm text-dark/50">
                        Isi materi ini belum tersedia. Silakan cek lagi nanti.
                    </p>
                @endif
            </div>

            @if ($materi->pembuat)
                <footer class="border-t border-lavender/70 px-6 py-5 text-xs text-dark/50 sm:px-9">
                    Ditambahkan oleh <span class="font-semibold text-dark/70">{{ $materi->pembuat->name }}</span>
                    {{ $materi->created_at?->format('d M Y') }}
                </footer>
            @endif
        </article>

        {{-- Saran baca. Kalau semua materi lain berasal dari kategori yang
             sama, namanya disebut; kalau bercampur, judulnya netral saja. --}}
        @if ($terkini->isNotEmpty())
            @php
                $semuaSatuKategori = $materi->pelajaran
                    && $terkini->every(fn ($item) => $item->pelajaran_id === $materi->pelajaran_id);
            @endphp

            <section class="mt-8">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 class="text-lg font-extrabold tracking-tight text-dark">
                        Materi lain
                        @if ($semuaSatuKategori)
                            <span class="font-semibold text-dark/45">di {{ $materi->pelajaran->nama }}</span>
                        @endif
                    </h2>

                    <a href="{{ route('user.materi') }}"
                        class="text-xs font-semibold text-primary transition hover:text-primary-dark">
                        Lihat semua
                    </a>
                </div>

                <div data-reveal-stagger
                    class="mt-4 grid min-w-0 gap-5 [grid-template-columns:repeat(1,minmax(0,18rem))] sm:[grid-template-columns:repeat(2,minmax(0,18rem))] lg:[grid-template-columns:repeat(3,minmax(0,18rem))] xl:[grid-template-columns:repeat(4,minmax(0,18rem))]">
                    @foreach ($terkini as $item)
                        @php
                            $warnaLain = Pelajaran::warna($item->pelajaran?->slug ?? '', $item->pelajaran?->nama ?? 'Umum');
                            $kesulitanLain = strtolower((string) $item->tingkat_kesulitan);
                        @endphp

                        <a href="{{ route('user.materi.detail', $item->slug) }}"
                            style="--k: {{ $warnaLain['warna'] }}; --k-gelap: {{ $warnaLain['warna_gelap'] }};"
                            class="kartu-materi group min-w-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                            aria-label="Buka materi {{ $item->nama }}">

                            <div class="kartu-materi__gambar">
                                <span class="kartu-materi__gambar-ikon">{{ $warnaLain['ikon'] }}</span>

                                <span class="lencana lencana-di-gambar">{{ $warnaLain['nama'] }}</span>
                            </div>

                            <div class="relative flex min-w-0 flex-1 flex-col px-4 pb-4 pt-3.5">
                                <h3 class="line-clamp-2 text-[0.9375rem] font-bold leading-snug tracking-tight text-dark transition group-hover:text-primary-dark">
                                    {{ $item->nama }}
                                </h3>

                                <p class="mt-1.5 line-clamp-2 text-xs leading-relaxed text-dark/55">
                                    {{ $item->ringkasan(110) }}
                                </p>

                                <div class="mt-auto flex items-center gap-1.5 border-t border-dashed border-lavender/80 pt-3">
                                    @if (filled($item->tingkat_kesulitan))
                                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold capitalize {{ $warnaKesulitan[$kesulitanLain] ?? 'bg-brand-bg text-dark/60' }}">
                                            {{ $item->tingkat_kesulitan }}
                                        </span>
                                    @endif

                                    <span class="flex shrink-0 items-center gap-1 text-[10px] font-medium text-dark/45">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>

                                        {{ $item->waktuBaca() }} menit
                                    </span>
                                </div>

                                <span class="kartu-materi__tombol mt-3 self-start">
                                    Baca Materi

                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                    </svg>
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
