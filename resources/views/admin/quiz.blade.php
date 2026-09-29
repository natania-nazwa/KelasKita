@extends('layouts.admin')

@section('title', 'Tinjau Quiz | KelasKita')

@section('content')
    {{--
        Halaman "Tinjau Quiz": tempat admin menyetujui atau menolak quiz mode
        publik yang diajukan pengguna.

        Halaman dibuka ke tab "Menunggu Persetujuan" supaya pekerjaan yang
        perlu dikerjakan selalu yang pertama terlihat. Setiap baris punya
        tombol Setujui, dan penolakan selalu lewat isian alasan karena alasan
        itu dibaca pemilik di "Karya Saya" dan jadi dasar pengajuan ulang.

        Quiz mode kode tidak masuk halaman ini: quiz seperti itu tidak pernah
        tayang untuk semua pengguna, jadi tidak ada yang perlu disetujui.
    --}}

    <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-dark">Tinjau Quiz</h1>

        <p class="mt-1 text-dark/60">
            Quiz publik yang dibuat pengguna hanya tayang setelah kamu menyetujuinya.
        </p>
    </div>

    @if (session('sukses'))
        <div class="mt-6 flex items-start gap-3 rounded-2xl border border-lavender bg-white px-4 py-3 text-sm text-dark">
            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary"
                aria-hidden="true">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </span>

            <p class="min-w-0 font-medium">{{ session('sukses') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div role="alert"
            class="mt-6 rounded-2xl border border-[#f4c7cd] bg-white px-4 py-3 text-sm text-[#a33a46]">
            <p class="font-semibold">Belum bisa diputuskan:</p>

            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                @foreach ($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- =========================
         TAB STATUS + PENCARIAN
    ========================== --}}
    <div class="mt-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <nav aria-label="Filter status quiz" class="flex flex-wrap gap-2">
            @foreach ($pilihanStatus as $nilai => $label)
                @php $jumlah = $jumlahStatus[$nilai] ?? 0; @endphp

                <a href="{{ route('admin.quiz', ['status' => $nilai, 'q' => $kataKunci]) }}"
                    class="inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-xs font-bold transition
                        {{ $statusAktif === $nilai
                            ? 'bg-primary text-white'
                            : 'bg-white text-dark/70 border border-lavender hover:border-primary' }}">
                    {{ $label }}

                    <span class="tabular-nums {{ $statusAktif === $nilai ? 'text-white/75' : 'text-dark/45' }}">
                        {{ $jumlah }}
                    </span>
                </a>
            @endforeach
        </nav>

        <form method="GET" action="{{ route('admin.quiz') }}" class="flex gap-2">
            <input type="hidden" name="status" value="{{ $statusAktif }}">

            <label for="q" class="sr-only">Cari quiz</label>

            <input id="q" name="q" type="search" value="{{ $kataKunci }}"
                placeholder="Cari judul atau kategori quiz..."
                class="w-full rounded-lg border border-lavender bg-white px-3.5 py-2 text-sm text-dark sm:w-72">

            <button type="submit"
                class="shrink-0 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark">
                Cari
            </button>
        </form>
    </div>

    {{-- =========================
         DAFTAR QUIZ
    ==========================
         Isian alasan yang gagal divalidasi hanya dikembalikan ke baris
         pertama, supaya satu teks tidak ikut terisi di semua textarea. --}}
    @if ($daftar === [])
        <div class="mt-6 rounded-2xl border border-lavender bg-white px-6 py-12 text-center">
            <p class="text-base font-bold text-dark">
                @if ($kataKunci !== '')
                    Tidak ada quiz "{{ $kataKunci }}" di tab ini.
                @elseif ($statusAktif === \App\Models\Quiz::STATUS_PENDING)
                    Tidak ada quiz yang menunggu persetujuan.
                @else
                    Belum ada quiz dengan status ini.
                @endif
            </p>

            <p class="mt-1 text-sm text-dark/60">
                Quiz publik yang diajukan pengguna akan muncul di sini.
            </p>
        </div>
    @else
        <div class="mt-6 space-y-5">
            @foreach ($daftar as $quiz)
                <article class="rounded-2xl bg-white border border-lavender overflow-hidden">

                    {{-- A. Kepala: judul, status, dan pembuat. --}}
                    <div class="flex flex-wrap items-start gap-4 px-6 py-5">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-lg font-extrabold text-white"
                            style="background-color: {{ $quiz['kategori']['warna'] }}"
                            aria-hidden="true">
                            {{ $quiz['kategori']['ikon'] }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-extrabold text-dark">{{ $quiz['judul'] }}</h2>

                                <span
                                    class="karya-status karya-status--{{ $quiz['warna_status'] }}">
                                    <span class="karya-status__titik" aria-hidden="true"></span>

                                    {{ $quiz['status_label'] }}
                                </span>
                            </div>

                            <p class="mt-1 text-xs font-semibold text-dark/50">
                                {{ $quiz['kategori']['nama'] }} &middot;
                                {{ $quiz['tingkat_kesulitan'] }} &middot;
                                {{ $quiz['jumlah_soal'] }} soal &middot;
                                {{ $quiz['durasi'] > 0 ? '± '.$quiz['durasi'].' menit' : 'Tanpa batas waktu' }}
                            </p>

                            <p class="mt-2 text-sm leading-relaxed text-dark/70">
                                {{ $quiz['deskripsi'] ?: 'Tanpa deskripsi.' }}
                            </p>

                            <p class="mt-2 text-xs text-dark/50">
                                Oleh {{ $quiz['pembuat']['nama'] }} &middot;
                                {{ $quiz['dibuat_pada']?->translatedFormat('d M Y') }}

                                @if ($quiz['jumlah_ditolak'] > 0)
                                    &middot; sudah {{ $quiz['jumlah_ditolak'] }}x ditolak
                                    (sisa pengajuan {{ $quiz['sisa_pengajuan'] }}x)
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- C. Catatan pengajuan ulang dari pemilik. --}}
                    @if (filled($quiz['catatan_pengajuan']))
                        <div class="border-t border-dark/5 px-6 py-4">
                            <p class="text-xs font-bold text-dark/50">Catatan pengajuan ulang dari pemilik</p>

                            <p class="mt-1 text-sm leading-relaxed text-dark/80">{{ $quiz['catatan_pengajuan'] }}</p>
                        </div>
                    @endif

                    {{-- D. Alasan penolakan sebelumnya. --}}
                    @if (filled($quiz['catatan_admin']))
                        <div class="border-t border-dark/5 bg-[#fdecee] px-6 py-4">
                            <p class="text-xs font-bold text-[#a8323c]">Alasan ditolak sebelumnya</p>

                            <p class="mt-1 text-sm leading-relaxed text-[#a8323c]">{{ $quiz['catatan_admin'] }}</p>
                        </div>
                    @endif

                    {{-- E. Keputusan admin: setujui atau tolak. --}}
                    @if ($statusAktif === \App\Models\Quiz::STATUS_PENDING)
                        <div class="flex flex-col gap-4 border-t border-dark/5 px-6 py-5 lg:flex-row lg:items-start lg:justify-between">
                            <form method="POST" action="{{ $quiz['tautan_setujui'] }}"
                                class="shrink-0">
                                @csrf

                                <input type="hidden" name="status" value="{{ $statusAktif }}">

                                <button type="submit"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-primary px-5 py-2.5 text-sm font-bold text-white transition hover:bg-primary-dark lg:w-auto">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>

                                    Setujui &amp; Terbitkan
                                </button>
                            </form>

                            <form method="POST" action="{{ $quiz['tautan_tolak'] }}"
                                class="min-w-0 flex-1">
                                @csrf

                                <input type="hidden" name="status" value="{{ $statusAktif }}">

                                <label for="alasan-{{ $quiz['id'] }}"
                                    class="block text-xs font-bold text-dark/60">
                                    Alasan penolakan (dibaca pemilik)
                                </label>

                                <textarea id="alasan-{{ $quiz['id'] }}" name="alasan" rows="2" required
                                    maxlength="500"
                                    placeholder="Contoh: Soal nomor 4 dan 5 punya kunci jawaban yang sama, tolong periksa kembali."
                                    class="mt-1.5 w-full rounded-xl border border-lavender bg-white px-3.5 py-2.5 text-sm text-dark placeholder:text-dark/35">{{ $loop->first ? old('alasan') : '' }}</textarea>

                                <button type="submit"
                                    class="mt-2.5 inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#d9535f] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#c2414a] lg:w-auto">
                                    Tolak Quiz
                                </button>
                            </form>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $paginasi->links() }}
        </div>
    @endif
@endsection
