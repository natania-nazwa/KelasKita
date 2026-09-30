@extends('layouts.admin')

@section('title', 'Pengguna | KelasKita')

@section('content')

    {{--
        Halaman "Pengguna": daftar semua akun yang memakai KelasKita.

        Halaman ini hanya membaca. Tidak ada tombol yang mengubah apa
        pun: tidak ada ubah peran, tidak ada nonaktifkan, tidak ada
        hapus. Management peran sengaja tidak dibuat karena saat ini
        hanya ada dua peran (ADMIN dan USER) dan hanya ada satu admin,
        jadi tidak ada apa pun yang perlu dikelola.
    --}}

    <x-admin.kepala judul="Pengguna" subjudul="Kelola pengguna yang menggunakan KelasKita." />

    {{-- ==================== RINGKASAN ==================== --}}
    <section class="ad-seksi ad-grid ad-grid--statistik" aria-label="Ringkasan pengguna">
        <x-admin.statistik ikon="grup" label="Total Pengguna" :nilai="$jumlahPengguna" nada="info" />

        <x-admin.statistik ikon="centang" label="Akun Aktif" :nilai="$jumlahAktif" nada="sukses"
            keterangan="Bisa masuk dan memakai platform" />

        <x-admin.statistik ikon="perisai" label="Administrator" :nilai="$jumlahAdmin" nada="peringatan"
            keterangan="Akun yang mengelola konten" />

        <x-admin.statistik ikon="buku" label="Rata-rata Karya"
            :nilai="rtrim(rtrim(number_format($rataKarya, 1, ',', ''), '0'), ',')"
            :keterangan="$totalKarya.' karya dari '.$jumlahPengguna.' pengguna'" />
    </section>

    {{-- ==================== FILTER ==================== --}}
    @php
        $status = [
            'semua' => 'Semua',
            'aktif' => 'Aktif',
            'nonaktif' => 'Nonaktif',
        ];

        $tabStatus = collect($status)
            ->map(fn (string $label, string $nilai): array => [
                'label' => $label,
                'nilai' => $nilai,
                'href' => route('admin.pengguna', ['status' => $nilai, 'q' => $kataKunci]),
            ])
            ->all();
    @endphp

    <div class="ad-seksi ad-alat">
        <x-admin.tab :tab="$tabStatus" :aktif="$statusAktif" label="Filter status akun" />

        <form method="GET" action="{{ route('admin.pengguna') }}" class="ad-alat__kanan">
            <input type="hidden" name="status" value="{{ $statusAktif }}">

            <div class="ad-cari">
                <label for="q-pengguna" class="sr-only">Cari pengguna</label>

                <x-admin.ikon nama="cari" class="ad-cari__ikon" />

                <input id="q-pengguna" name="q" type="search" value="{{ $kataKunci }}"
                    placeholder="Cari nama atau email..." autocomplete="off">
            </div>

            <button type="submit" class="ad-tombol ad-tombol--garis shrink-0">Cari</button>
        </form>
    </div>

    {{-- ==================== TABEL ==================== --}}
    @if ($daftar->isEmpty())
        <div class="ad-seksi">
            <x-admin.kosong ikon="grup"
                :judul="$kataKunci !== '' ? 'Tidak ada pengguna yang cocok' : 'Belum ada pengguna'"
                :teks="$kataKunci !== ''
                    ? 'Coba kata kunci lain, atau pindah ke tab status yang lain.'
                    : 'Pengguna yang mendaftar akan otomatis muncul di daftar ini.'" />
        </div>
    @else
        <div class="ad-seksi ad-tabel__bungkus">
            <table class="ad-tabel">
                <thead>
                    <tr>
                        <th scope="col">No</th>
                        <th scope="col">Nama</th>
                        <th scope="col">Email</th>
                        <th scope="col">Peran</th>
                        <th scope="col">Karya</th>
                        <th scope="col">Status</th>
                        <th scope="col">Bergabung</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($daftar as $pengguna)
                        @php
                            $avatar = $pengguna->warnaAvatar();
                        @endphp

                        <tr>
                            <td class="ad-tabel__nomor" data-label="No">
                                {{ $daftar->firstItem() + $loop->index }}
                            </td>

                            <td data-label="Nama">
                                <div class="ad-tabel__nama">
                                    <x-admin.avatar :inisial="$pengguna->inisial()" :warna="$avatar['warna']"
                                        :warna-gelap="$avatar['warna_gelap']" ukuran="kecil" />

                                    <span class="ad-tabel__nama-teks">
                                        <span class="ad-tabel__judul">{{ $pengguna->nama }}</span>
                                    </span>
                                </div>
                            </td>

                            <td data-label="Email">
                                <span class="break-all">{{ $pengguna->email }}</span>
                            </td>

                            <td data-label="Peran">
                                @if ($pengguna->isAdmin())
                                    <span class="ad-lencana ad-lencana--ungu">
                                        <x-admin.ikon nama="perisai" ukuran="w-3 h-3" />
                                        Admin
                                    </span>
                                @else
                                    <span class="ad-lencana ad-lencana--abu">Siswa</span>
                                @endif
                            </td>

                            <td data-label="Karya">
                                <span class="tabular-nums">{{ $pengguna->materi_count }} materi
                                    &middot; {{ $pengguna->quiz_count }} quiz</span>
                            </td>

                            <td data-label="Status">
                                @if ($pengguna->isAktif())
                                    <span class="ad-lencana ad-lencana--sukses">
                                        <span class="ad-lencana__titik" aria-hidden="true"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="ad-lencana ad-lencana--abu">
                                        <span class="ad-lencana__titik" aria-hidden="true"></span>
                                        Nonaktif
                                    </span>
                                @endif
                            </td>

                            <td data-label="Bergabung">
                                <span class="tabular-nums">{{ $pengguna->created_at?->translatedFormat('d M Y') }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="ad-seksi">
            {{ $daftar->links() }}
        </div>
    @endif

@endsection
