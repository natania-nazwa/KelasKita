{{--
    Empat dialog di halaman /admin/pengaturan.

    Satu berkas untuk keempatnya karena isinya pendek dan semuanya memakai
    pola yang sama: dialog .ad-dialog, tombol pemicunya di halaman memakai
    data-dialog-buka="<id>", dan form-nya memakai @method yang sesuai.

    Dialog "keluar dari akun" dan "logout semua perangkat" sengaja memakai
    form yang benar-benar dikirim, bukan tombol yang hanya hilang dialognya:
    kedua aksi mengubah sesi di server dan harus tetap jalan tanpa JavaScript.

    Kelima dialog (Keamanan, Notifikasi, Publikasi, Logout semua, Keluar)
    tidak memakai pengulangan satu-dialog-untuk-semua-baris seperti dialog
    hapus konten. Bedanya: yang ini butuh isian, dan formnya punya banyak
    field, jadi isinya memang berbeda tiap dialog, bukan hanya kalimat
    judulnya yang berbeda.
--}}

{{-- ==================== 1. UBAH PASSWORD ==================== --}}
<div class="ad-dialog" data-atur-dialog="atur-keamanan" role="dialog" aria-modal="true" aria-hidden="true"
    aria-labelledby="atur-keamanan-judul">
    <div class="ad-dialog__kartu">
        <header class="ad-dialog__kepala">
            <div class="min-w-0 flex-1">
                <h2 class="ad-dialog__judul" id="atur-keamanan-judul">Ubah Password</h2>
                <p class="ad-teks-2 mt-0.5 !text-xs">Minimal 8 karakter.</p>
            </div>

            <button type="button" class="ad-dialog__tutup" data-atur-dialog-batal aria-label="Tutup">
                <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
            </button>
        </header>

        <form method="POST" action="{{ route('admin.pengaturan.keamanan.kata-sandi') }}">
            @csrf
            @method('PUT')

            <div class="ad-dialog__badan">
                <div class="space-y-4">
                    @foreach ([
                        'kata_sandi_lama' => ['Password saat ini', 'Masukkan password yang sekarang dipakai', 'current-password'],
                        'kata_sandi_baru' => ['Password baru', 'Minimal 8 karakter', 'new-password'],
                        'kata_sandi_baru_konfirmasi' => ['Konfirmasi password baru', 'Ulangi password baru', 'new-password'],
                    ] as $kolom => [$label, $petunjuk, $autocomplete])
                        {{--
                            Tombol mata hanya mengubah atribut type milik kolom
                            di sebelahnya. Isi password tidak pernah dibaca,
                            disalin, atau disimpan di mana pun; pemeriksaan
                            password lama tetap dilakukan di server.
                        --}}
                        <div class="ad-field">
                            <label class="ad-field__label" for="atur-{{ $kolom }}">{{ $label }}</label>

                            <div class="relative">
                                <input class="ad-input ad-input-ikon" type="password" name="{{ $kolom }}"
                                    id="atur-{{ $kolom }}" autocomplete="{{ $autocomplete }}"
                                    placeholder="{{ $petunjuk }}" required>

                                <button type="button"
                                    class="ad-kolom-ikon"
                                    data-atur-lihat-sandi aria-pressed="false"
                                    aria-label="Tampilkan {{ strtolower($label) }}">
                                    <x-admin.ikon nama="mata" ukuran="w-4 h-4"
                                        data-atur-mata-tertutup />
                                    <x-admin.ikon nama="mata-tutup" ukuran="w-4 h-4" class="hidden"
                                        data-atur-mata-terbuka />
                                </button>
                            </div>

                            @error($kolom)
                                <span class="ad-field__error">{{ $message }}</span>
                            @enderror
                        </div>
                    @endforeach
                </div>

                @if ($errors->any())
                    <div class="ad-alert ad-alert--bahaya mt-4" role="alert">
                        <span class="ad-alert__ikon" aria-hidden="true">
                            <x-admin.ikon nama="silang-polos" ukuran="w-3.5 h-3.5" :tebal="2.6" />
                        </span>

                        <div class="min-w-0">
                            <p class="font-semibold">Password belum bisa diganti:</p>
                            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                                @foreach ($errors->all() as $pesan)
                                    <li>{{ $pesan }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                {{--
                    Sesi di perangkat lain ikut diakhiri setelah kata sandi
                    berubah. Disampaikan di sini supaya admin tahu efeknya
                    sebelum menekan tombol, bukan baru diketahui setelahnya.
                --}}
                <div class="ad-alert mt-4">
                    <span class="ad-alert__ikon" aria-hidden="true">
                        <x-admin.ikon nama="lampu" ukuran="w-3.5 h-3.5" />
                    </span>

                    <p class="min-w-0">
                        Setelah diganti, sesi di perangkat lain ikut diakhiri.
                        Sesi di perangkat ini tetap aktif.
                    </p>
                </div>
            </div>

            <footer class="ad-dialog__kaki">
                <button type="button" class="ad-tombol ad-tombol--garis" data-atur-dialog-batal>Batal</button>

                <button type="submit" class="ad-tombol ad-tombol--utama">
                    <x-admin.ikon nama="gembok" />
                    Ubah Password
                </button>
            </footer>
        </form>
    </div>
</div>

{{-- ==================== 2. NOTIFIKASI ==================== --}}
<div class="ad-dialog" data-atur-dialog="atur-notifikasi" role="dialog" aria-modal="true" aria-hidden="true"
    aria-labelledby="atur-notifikasi-judul">
    <div class="ad-dialog__kartu">
        <header class="ad-dialog__kepala">
            <div class="min-w-0 flex-1">
                <h2 class="ad-dialog__judul" id="atur-notifikasi-judul">Notifikasi</h2>
                <p class="ad-teks-2 mt-0.5 !text-xs">Atur notifikasi yang ingin diterima.</p>
            </div>

            <button type="button" class="ad-dialog__tutup" data-atur-dialog-batal aria-label="Tutup">
                <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
            </button>
        </header>

        <form method="POST" action="{{ route('admin.pengaturan.notifikasi') }}">
            @csrf
            @method('PUT')

            <div class="ad-dialog__badan">
                {{--
                    Empat saklar, dikelompokkan dua per kelompok. Setiap
                    kelompok punya field tersembunyi 0 tepat sebelum saklarnya:
                    checkbox yang tidak dicentang tidak pernah terkirim, jadi
                    tanpa field itu mematikan saklar akan berarti field-nya
                    hilang dan nilainya tidak akan pernah tersimpan.
                --}}
                @foreach ([
                    'Notifikasi Konten' => [
                        ['notifikasi_konten_terbit', 'Konten berhasil dipublikasikan',
                            'Saat kamu menerbitkan materi atau quiz sendiri.'],
                        ['notifikasi_konten_draft', 'Konten berhasil disimpan sebagai draft',
                            'Saat materi atau quiz disimpan sebagai draft.'],
                    ],
                    'Aktivitas Pengguna' => [
                        ['notifikasi_aktivitas_kuis', 'Ada hasil kuis baru',
                            'Saat seorang pengguna menyelesaikan sebuah quiz.'],
                        ['notifikasi_aktivitas_konten', 'Konten menunggu ditinjau',
                            'Saat seorang pengguna mengirim karya ke antrean Verifikasi.'],
                    ],
                ] as $kelompok => $daftar)
                    <p class="ad-seksi__judul mt-5 !text-xs first:mt-0">{{ $kelompok }}</p>

                    <div class="mt-1">
                        @foreach ($daftar as [$kolom, $judul, $subjudul])
                            <div class="ad-atur-saklar-baris">
                                {{--
                                    Field 0 disembunyikan dengan sr-only, bukan
                                    type=hidden, supaya tetap tercatat di
                                    formulir peramban dan tetap terkirim.
                                --}}
                                <input type="hidden" name="{{ $kolom }}" value="0"
                                    data-atur-saklar-nilai>

                                <span class="ad-atur-saklar-baris__teks">
                                    <span class="ad-atur-saklar-baris__judul">{{ $judul }}</span>
                                    <span class="ad-atur-saklar-baris__sub">{{ $subjudul }}</span>
                                </span>

                                <x-admin.atur-saklar :judul="$judul" :aktif="$preferensi->{$kolom}"
                                    data-atur-saklar-nama="{{ $kolom }}" />
                            </div>
                        @endforeach
                    </div>
                @endforeach

                @if ($errors->any())
                    <div class="ad-alert ad-alert--bahaya mt-4" role="alert">
                        <span class="ad-alert__ikon" aria-hidden="true">
                            <x-admin.ikon nama="silang-polos" ukuran="w-3.5 h-3.5" :tebal="2.6" />
                        </span>

                        <div class="min-w-0">
                            <p class="font-semibold">Pengaturan belum bisa disimpan:</p>
                            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                                @foreach ($errors->all() as $pesan)
                                    <li>{{ $pesan }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                {{--
                    Catatan yang jujur soal batasannya: pengaturan ini hanya
                    menentukan kabar yang masuk ke lonceng admin. Notifikasi
                    untuk pengguna saat konten diterbitkan tidak pernah
                    membaca tabel preferensi, jadi tidak ikut berubah di sini.
                --}}
                <div class="ad-alert mt-4">
                    <span class="ad-alert__ikon" aria-hidden="true">
                        <x-admin.ikon nama="lampu" ukuran="w-3.5 h-3.5" />
                    </span>

                    <p class="min-w-0">
                        Pengaturan ini hanya untuk notifikasi yang diterima
                        <span class="font-semibold">admin</span>. Saat kamu
                        menerbitkan materi atau kuis, pengguna tetap
                        menerima notifikasi publikasi seperti biasa.
                    </p>
                </div>
            </div>

            <footer class="ad-dialog__kaki">
                <button type="button" class="ad-tombol ad-tombol--garis" data-atur-dialog-batal>Batal</button>

                <button type="submit" class="ad-tombol ad-tombol--utama">
                    <x-admin.ikon nama="tanda-centang" ukuran="w-4 h-4" :tebal="2.4" />
                    Simpan Pengaturan
                </button>
            </footer>
        </form>
    </div>
</div>

{{-- ==================== 3. PENGATURAN PUBLIKASI ==================== --}}
<div class="ad-dialog" data-atur-dialog="atur-publikasi" role="dialog" aria-modal="true" aria-hidden="true"
    aria-labelledby="atur-publikasi-judul">
    <div class="ad-dialog__kartu">
        <header class="ad-dialog__kepala">
            <div class="min-w-0 flex-1">
                <h2 class="ad-dialog__judul" id="atur-publikasi-judul">Pengaturan Publikasi</h2>
                <p class="ad-teks-2 mt-0.5 !text-xs">Atur default konten baru.</p>
            </div>

            <button type="button" class="ad-dialog__tutup" data-atur-dialog-batal aria-label="Tutup">
                <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
            </button>
        </header>

        <form method="POST" action="{{ route('admin.pengaturan.publikasi') }}">
            @csrf
            @method('PUT')

            <div class="ad-dialog__badan">
                <p class="ad-field__label">Status Default Konten Baru</p>

                <div class="ad-atur-pilih mt-2" role="radiogroup" aria-label="Status default konten baru">
                    @foreach ([
                        'draft' => ['Draft', 'Konten baru akan disimpan sebagai Draft secara default.'],
                        'published' => ['Published', 'Konten baru langsung tayang tanpa ditinjau lagi.'],
                    ] as $nilai => [$label, $subjudul])
                        <label class="ad-atur-pilih__item">
                            <input type="radio" name="status_konten_default" value="{{ $nilai }}"
                                @checked(old('status_konten_default',
                                    $preferensi->status_konten_default) === $nilai)>

                            <span>
                                <span class="ad-atur-pilih__judul">{{ $label }}</span>
                                <span class="ad-atur-pilih__sub">{{ $subjudul }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                @error('status_konten_default')
                    <span class="ad-field__error mt-2 block">{{ $message }}</span>
                @enderror

                @if ($preferensi->status_konten_default === 'draft')
                    <div class="ad-alert mt-4">
                        <span class="ad-alert__ikon" aria-hidden="true">
                            <x-admin.ikon nama="lampu" ukuran="w-3.5 h-3.5" />
                        </span>

                        <p class="min-w-0">
                            Konten baru akan disimpan sebagai <strong>Draft</strong> secara
                            default, jadi admin bisa mengecek isinya sebelum ditayangkan.
                        </p>
                    </div>
                @endif

                {{--
                    Konfirmasi sebelum publish. Field 0 tersembunyi muncul
                    lebih dulu supaya saklarnya yang tidak menyala tetap
                    mengirim "0".
                --}}
                <div class="ad-atur-saklar-baris ad-garis-atas mt-4 pt-4">
                    <input type="hidden" name="konfirmasi_publikasi" value="0" data-atur-saklar-nilai>

                    <span class="ad-atur-saklar-baris__teks">
                        <span class="ad-atur-saklar-baris__judul">Konfirmasi sebelum Publish</span>
                        <span class="ad-atur-saklar-baris__sub">
                            Tampilkan dialog konfirmasi sebelum konten ditayangkan ke pengguna.
                        </span>
                    </span>

                    <x-admin.atur-saklar judul="Konfirmasi sebelum Publish"
                        :aktif="$preferensi->konfirmasi_publikasi" data-atur-saklar-nama="konfirmasi_publikasi" />
                </div>

                @if ($errors->any())
                    <div class="ad-alert ad-alert--bahaya mt-4" role="alert">
                        <span class="ad-alert__ikon" aria-hidden="true">
                            <x-admin.ikon nama="silang-polos" ukuran="w-3.5 h-3.5" :tebal="2.6" />
                        </span>

                        <div class="min-w-0">
                            <p class="font-semibold">Pengaturan belum bisa disimpan:</p>
                            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                                @foreach ($errors->all() as $pesan)
                                    <li>{{ $pesan }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <div class="ad-alert mt-4">
                    <span class="ad-alert__ikon" aria-hidden="true">
                        <x-admin.ikon nama="lampu" ukuran="w-3.5 h-3.5" />
                    </span>

                    <p class="min-w-0">
                        Tidak ada persetujuan di aplikasi ini. Admin bisa langsung
                        menerbitkan karyanya sendiri: <strong>Draft → Publish</strong>
                        kapan saja.
                    </p>
                </div>
            </div>

            <footer class="ad-dialog__kaki">
                <button type="button" class="ad-tombol ad-tombol--garis" data-atur-dialog-batal>Batal</button>

                <button type="submit" class="ad-tombol ad-tombol--utama">
                    <x-admin.ikon nama="tanda-centang" ukuran="w-4 h-4" :tebal="2.4" />
                    Simpan Pengaturan
                </button>
            </footer>
        </form>
    </div>
</div>

{{-- ==================== 4. LOGOUT DARI SEMUA PERANGKAT ==================== --}}
<div class="ad-dialog" data-atur-dialog="atur-logout-semua" role="dialog" aria-modal="true" aria-hidden="true"
    aria-labelledby="atur-logout-semua-judul">
    <div class="ad-dialog__kartu">
        <header class="ad-dialog__kepala">
            <div class="min-w-0 flex-1">
                <h2 class="ad-dialog__judul" id="atur-logout-semua-judul">Logout dari semua perangkat?</h2>
            </div>

            <button type="button" class="ad-dialog__tutup" data-atur-dialog-batal aria-label="Tutup">
                <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
            </button>
        </header>

        <div class="ad-dialog__badan">
            <p class="ad-dialog__pesan">
                Semua sesi login akan diakhiri. Perangkat ini ikut keluar kalau
                kamu menutup halamannya.
            </p>
        </div>

        <footer class="ad-dialog__kaki">
            <button type="button" class="ad-tombol ad-tombol--garis" data-atur-dialog-batal>Batal</button>

            {{--
                type="submit" dengan atribut form=, sama seperti dialog hapus
                konten yang sudah ada. Form-nya disembunyikan supaya tidak
                menambah tinggi dialog, tapi tetap form POST yang benar.
            --}}
            <form method="POST" action="{{ route('admin.pengaturan.sesi.destroy') }}" id="form-atur-logout-semua"
                hidden>
                @csrf
                @method('DELETE')
            </form>

            <button type="submit" class="ad-tombol ad-tombol--bahaya" form="form-atur-logout-semua">
                <x-admin.ikon nama="pintu-keluar" />
                Logout Semua
            </button>
        </footer>
    </div>
</div>

{{-- ==================== 5. KELUAR DARI AKUN ==================== --}}
<div class="ad-dialog" data-atur-dialog="atur-keluar" role="dialog" aria-modal="true" aria-hidden="true"
    aria-labelledby="atur-keluar-judul">
    <div class="ad-dialog__kartu">
        <header class="ad-dialog__kepala">
            <div class="min-w-0 flex-1">
                <h2 class="ad-dialog__judul" id="atur-keluar-judul">Keluar dari akun?</h2>
            </div>

            <button type="button" class="ad-dialog__tutup" data-atur-dialog-batal aria-label="Tutup">
                <x-admin.ikon nama="silang-polos" ukuran="w-4 h-4" />
            </button>
        </header>

        <div class="ad-dialog__badan">
            <p class="ad-dialog__pesan">
                Apakah Anda yakin ingin keluar dari akun Admin? Anda akan kembali
                ke halaman Login.
            </p>
        </div>

        <footer class="ad-dialog__kaki">
            <button type="button" class="ad-tombol ad-tombol--garis" data-atur-dialog-batal>Batal</button>

            {{--
                Route "logout" yang sudah ada, tidak route baru: keluar dari
                aplikasi ini harus lewat Auth::logout() supaya sesi server ikut
                dibersihkan, bukan sekadar menghapus cookie di peramban ini.
            --}}
            <form method="POST" action="{{ route('logout') }}" id="form-atur-keluar" hidden>
                @csrf
            </form>

            <button type="submit" class="ad-tombol ad-tombol--bahaya" form="form-atur-keluar">
                <x-admin.ikon nama="pintu-keluar" />
                Keluar
            </button>
        </footer>
    </div>
</div>
