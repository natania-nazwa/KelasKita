@extends ('layouts.guest')

@section ('title', 'KelasKita - Belajar Lebih Mudah')

@section ('content')
    <div class="scroll-progress" data-scroll-progress aria-hidden="true"></div>

    <header
        class="fixed inset-x-0 top-0 z-50 bg-white border-b border-dark/5 shadow-md shadow-dark/5"
    >
        <nav class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
            <div
                class="flex items-center justify-between gap-6 h-20 lg:h-[84px]"
            >
                {{-- Pembuka --}}
                <a
                    href="{{ url('') }}"
                    class="flex items-center gap-3 lg:gap-3.5 shrink-0"
                >
                    <img
                        src="{{ asset('images/logo.png') }}"
                        alt="Logo KelasKita"
                        class="w-11 h-11 lg:w-12 lg:h-12 object-contain"
                    />
                    <span>
                        <span
                            class="block font-display text-xl lg:text-2xl font-extrabold leading-none"
                            ><span class="text-primary">Kelas</span
                            ><span class="text-slate-900">kita</span></span
                        >
                        <span
                            class="block text-[11px] lg:text-xs font-light text-dark/50 mt-1.5 tracking-wide"
                            >Belajar • Latihan • Naik Level</span
                        >
                    </span>
                </a>

                <div
                    class="hidden md:flex flex-1 items-center justify-center gap-6 font-mono text-xs uppercase tracking-[0.14em] font-semibold"
                >
                    <a
                        href="#tentang"
                        class="nav-link text-primary hover:text-primary-dark transition-colors duration-200 pb-1.5"
                        >Tentang<span class="nav-bar"></span
                    ></a>
                    <a
                        href="#fitur"
                        class="nav-link text-primary hover:text-primary-dark transition-colors duration-200 pb-1.5"
                        >Fitur<span class="nav-bar"></span
                    ></a>
                    <a
                        href="#materi"
                        class="nav-link text-primary hover:text-primary-dark transition-colors duration-200 pb-1.5"
                        >Materi<span class="nav-bar"></span
                    ></a>
                    <a
                        href="#carakerja"
                        class="nav-link text-primary hover:text-primary-dark transition-colors duration-200 pb-1.5"
                        >Cara Kerja<span class="nav-bar"></span
                    ></a>
                </div>

                <div
                    class="hidden md:flex items-center gap-3 lg:gap-4 shrink-0"
                >
                    <a
                        href="{{ url('/login') }}"
                        class="inline-flex items-center justify-center px-6 py-2.5 rounded-xl font-mono text-xs font-semibold uppercase tracking-[0.14em] text-primary bg-white border-2 border-primary hover:bg-primary hover:text-white transition-colors duration-200"
                    >
                        Login/Daftar
                    </a>
                </div>

                <label
                    for="nav-toggle"
                    class="md:hidden flex items-center justify-center w-11 h-11 rounded-xl border border-dark/10 text-dark cursor-pointer hover:bg-lavender transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </label>
            </div>

            <input type="checkbox" id="nav-toggle" class="hidden peer" />
            <div
                class="md:hidden hidden peer-checked:block border-t border-dark/5 pb-6 pt-4"
            >
                <div class="flex flex-col gap-1.5 font-mono text-xs uppercase tracking-[0.14em] font-semibold">
                    <a
                        href="#tentang"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl text-dark/60 hover:bg-brand-bg hover:text-primary transition-colors"
                    >
                        Tentang
                    </a>
                    <a
                        href="#fitur"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl text-dark/60 hover:bg-brand-bg hover:text-primary transition-colors"
                    >
                        Fitur
                    </a>
                    <a
                        href="#carakerja"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl text-dark/60 hover:bg-brand-bg hover:text-primary transition-colors"
                    >
                        Cara Kerja
                    </a>
                </div>
                <div class="mt-4">
                    <a
                        href="{{ url('/login') }}"
                        class="inline-flex items-center justify-center w-full px-5 py-3 rounded-xl font-mono text-xs font-semibold uppercase tracking-[0.14em] text-primary bg-white border-2 border-primary hover:bg-primary hover:text-white transition-colors duration-200"
                    >
                        Login/Daftar
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <div class="h-20 lg:h-[84px]"></div>

    <section id="beranda" class="relative overflow-hidden bg-[#faf9ff]">
        {{-- Background decoration --}}
        <div
            class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-[#eee8ff]"
        ></div>
        <div
            class="absolute bottom-0 -left-32 w-80 h-80 rounded-full bg-[#f0ebff]"
        ></div>

        <div class="relative mx-auto max-w-7xl px-6 sm:px-8 lg:px-12">
            <div
                class="grid lg:grid-cols-2 items-center gap-8 lg:gap-2 min-h-[520px]"
            >
                {{-- =========================
                 BAGIAN KIRI
            ========================== --}}
                <div class="relative z-10 py-14 lg:py-20">
                    {{-- Label --}}
                    <span
                        data-reveal
                        style="--reveal-delay: 60ms"
                        class="inline-flex items-center px-4 py-2 rounded-full bg-primary text-white border-2 border-white/50 ring-2 ring-white/20 font-mono text-xs sm:text-sm font-semibold uppercase tracking-[0.14em]"
                    >
                        Platform Belajar Online
                    </span>

                    {{-- Judul --}}
                    <h1
                        data-reveal
                        style="--reveal-delay: 140ms"
                        class="mt-5 text-4xl sm:text-5xl lg:text-[52px] font-extrabold leading-[1.12] text-dark"
                    >
                        Belajar lebih mudah,

                        <span class="block font-serif italic font-medium text-primary">
                            latihan lebih terarah.
                        </span>
                    </h1>

                    {{-- Deskripsi --}}
                    <p
                        data-reveal
                        style="--reveal-delay: 220ms"
                        class="mt-5 max-w-lg text-base sm:text-lg font-light leading-relaxed text-dark/65"
                    >Pelajari materi dan uji pemahamanmu melalui soal-soal interaktif. Tingkatkan kemampuanmu bersama KelasKita!</p>

                    {{-- Button --}}
                    <div
                        data-reveal
                        style="--reveal-delay: 300ms"
                        class="mt-8 flex flex-wrap items-center gap-3"
                    >
                        <a
                            href="{{ url('/register') }}"
                            class="inline-flex flex-1 items-center justify-center gap-2.5 rounded-2xl bg-primary px-3 py-4 text-sm font-semibold text-white shadow-lg shadow-primary/25 transition hover:bg-primary-dark md:flex-none md:px-8"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.63 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.841m2.581-5.841a14.927 14.927 0 0 1 5.841-2.58m-.119 8.54a6 6 0 0 0 7.381-5.84h-4.8m-2.581 5.84a14.926 14.926 0 0 0 2.58-5.841m-2.581 5.841a14.926 14.926 0 0 1-5.841 2.58" />
                            </svg>
                            Mulai Belajar
                        </a>

                        <a
                            href="#quiz"
                            class="inline-flex flex-1 items-center justify-center gap-2.5 rounded-2xl border-2 border-primary/20 bg-white px-3 py-4 text-sm font-semibold text-primary transition hover:border-primary/40 hover:bg-[#f1ecff] md:flex-none md:px-8"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            Lihat Quiz
                        </a>
                    </div>
                </div>

                {{-- =========================
                 BAGIAN KANAN
            ========================== --}}
                <div
                    data-reveal="zoom"
                    style="--reveal-delay: 180ms"
                    class="relative mt-6 flex items-end justify-center self-end -mb-1 lg:mt-0 lg:justify-end lg:-mb-2"
                >
                    {{-- Illustration --}}
                    <img
                        src="{{ asset('images/cover.png') }}"
                        alt="Ilustrasi KelasKita"
                        class="relative z-10 w-[min(80vw,420px)] lg:w-[760px] xl:w-[850px] h-auto object-contain drop-shadow-xl"
                    />
                </div>
            </div>
        </div>

        {{-- =========================
         WAVE BAWAH HERO
    ========================== --}}
        <svg
            class="relative block w-full h-14 sm:h-16 lg:h-20"
            viewBox="0 0 1440 90"
            preserveAspectRatio="none"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <path
                fill="#6c4de6"
                d="M0,64
               C240,96 480,0 720,32
               C960,64 1200,96 1440,48
               L1440,90
               L0,90 Z"
            />
        </svg>
    </section>

    <section
        id="tentang"
        class="relative overflow-hidden bg-white border-y border-dark/5"
    >
        <div
            class="relative mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-16 lg:py-20"
        >
            <div class="text-center max-w-2xl mx-auto">
                <h2
                    data-reveal
                    class="mt-4 text-3xl sm:text-4xl font-extrabold text-dark"
                >
                    Kenapa <span class="text-primary">KelasKita</span> Dibuat?
                </h2>
            </div>

            <div class="mt-14 grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <p
                        data-reveal="left"
                        class="text-base font-light text-dark/70 leading-relaxed"
                    >KelasKita lahir dari keprihatinan sederhana: belajar seharusnya mudah dan bisa dinikmati siapa saja. Banyak pelajar kesulitan menemukan materi yang rapi dan latihan soal yang sesuai dalam satu tempat. KelasKita dibuat untuk menyatukan keduanya secara sederhana, terbuka, dan terus dikembangkan bersama-sama oleh komunitas penggunanya.</p>

                    <ul class="mt-8 space-y-4">
                        <li
                            data-reveal="left"
                            style="--reveal-delay: 80ms"
                            class="flex items-center gap-4 rounded-2xl bg-brand-bg border border-lavender px-5 py-4 hover:shadow-lg hover:shadow-dark/5 transition"
                        >
                            <span
                                class="flex items-center justify-center w-9 h-9 rounded-full bg-primary text-white shrink-0"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </span>
                            <p class="text-base font-semibold text-dark">Membantu pelajar membaca dan memahami materi dengan tampilan yang rapi.</p>
                        </li>
                        <li
                            data-reveal="left"
                            style="--reveal-delay: 160ms"
                            class="flex items-center gap-4 rounded-2xl bg-brand-bg border border-lavender px-5 py-4 hover:shadow-lg hover:shadow-dark/5 transition"
                        >
                            <span
                                class="flex items-center justify-center w-9 h-9 rounded-full bg-primary text-white shrink-0"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </span>
                            <p class="text-base font-semibold text-dark">Menyediakan tempat untuk menguji pemahaman melalui quiz interaktif.</p>
                        </li>
                        <li
                            data-reveal="left"
                            style="--reveal-delay: 240ms"
                            class="flex items-center gap-4 rounded-2xl bg-brand-bg border border-lavender px-5 py-4 hover:shadow-lg hover:shadow-dark/5 transition"
                        >
                            <span
                                class="flex items-center justify-center w-9 h-9 rounded-full bg-primary text-white shrink-0"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </span>
                            <p class="text-base font-semibold text-dark">Membangun komunitas belajar yang saling berbagi materi dan soal.</p>
                        </li>
                    </ul>
                </div>

                <div
                    data-reveal="right"
                    class="relative rounded-3xl bg-white border border-lavender shadow-xl shadow-dark/5 p-8"
                >
                    <span
                        class="misi-bar absolute inset-x-3 top-0 h-1 rounded-b-full bg-gradient-to-r from-white via-primary to-primary"
                    ></span>

                    <div class="flex items-center gap-2.5">
                        <span
                            class="flex items-center justify-center w-10 h-10 rounded-xl bg-lavender text-primary"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672 13.684 16.6m0 0-2.51 2.225.569-9.47 5.227 7.917-3.286-.672Zm-7.518-.267A8.25 8.25 0 1 1 20.25 10.5M8.288 14.212A5.25 5.25 0 1 1 17.25 10.5" />
                            </svg>
                        </span>
                        <h3 class="text-base font-extrabold text-dark">
                            Misi Kami
                        </h3>
                    </div>

                    <div class="mt-6 space-y-4">
                        <div class="flex gap-3 rounded-2xl bg-brand-bg p-4">
                            <span
                                class="flex items-center justify-center w-8 h-8 rounded-xl bg-gradient-to-br from-primary to-primary-dark text-white text-xs font-bold shrink-0"
                                >1</span
                            >
                            <div>
                                <p class="text-sm font-bold text-dark">Belajar Lebih Mudah</p>
                                <p class="mt-1 text-xs text-dark/60 leading-relaxed">Belajar jadi lebih mudah dan menyenangkan untuk semua pelajar.</p>
                            </div>
                        </div>
                        <div class="flex gap-3 rounded-2xl bg-brand-bg p-4">
                            <span
                                class="flex items-center justify-center w-8 h-8 rounded-xl bg-gradient-to-br from-primary to-primary-dark text-white text-xs font-bold shrink-0"
                                >2</span
                            >
                            <div>
                                <p class="text-sm font-bold text-dark">Akses Tanpa Ribet</p>
                                <p class="mt-1 text-xs text-dark/60 leading-relaxed">Memberi akses materi dan latihan secara sederhana tanpa ribet.</p>
                            </div>
                        </div>
                        <div class="flex gap-3 rounded-2xl bg-brand-bg p-4">
                            <span
                                class="flex items-center justify-center w-8 h-8 rounded-xl bg-gradient-to-br from-primary to-primary-dark text-white text-xs font-bold shrink-0"
                                >3</span
                            >
                            <div>
                                <p class="text-sm font-bold text-dark">Tumbuh Bersama</p>
                                <p class="mt-1 text-xs text-dark/60 leading-relaxed">Wadah komunitas untuk tumbuh dan berbagi ilmu bersama.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="fitur" class="relative overflow-hidden bg-white">
        <div
            class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-lavender/50"
        ></div>
        <div
            class="absolute -bottom-24 -left-24 w-96 h-96 rounded-full bg-lavender/50"
        ></div>

        <div class="relative mx-auto max-w-7xl px-6 py-16 lg:px-10 lg:py-20">
            {{-- HEADER --}}
            <div data-reveal class="text-center">
                <span
                    class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-lavender text-primary font-mono text-xs font-semibold uppercase tracking-[0.14em]"
                >
                    Fitur Utama
                </span>

                <h2 class="mt-4 text-3xl font-extrabold text-dark sm:text-4xl">
                    Kenapa Memilih <span class="text-primary">KelasKita?</span>
                </h2>

                <p class="mt-3 font-light text-base text-dark/60">Kami menyediakan solusi belajar yang praktis dan menyenangkan.</p>
            </div>

            {{-- CARDS --}}
            <div
                data-reveal-stagger
                class="mt-12 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4"
            >
                {{-- 1 --}}
                <div
                    class="group relative min-h-[240px] overflow-hidden rounded-2xl border border-[#e9e6f2] bg-white p-7 transition-all duration-300 hover:-translate-y-1.5 hover:border-[#dcd7f0] hover:shadow-xl hover:shadow-dark/5"
                >
                    <span
                        class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-[#a66bea]/10 transition-transform duration-300 group-hover:scale-150"
                    ></span>

                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-[#a66bea] to-[#8b3df6] text-white shadow-lg shadow-[#a66bea]/30"
                    >
                        <svg
                            class="h-7 w-7"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 6.042A8.967 8.967 0 0 0 6 3.75
                            c-1.052 0-2.062.18-3 .512v14.25
                            A8.987 8.987 0 0 1 6 18
                            c2.305 0 4.408.867 6 2.292
                            m0-14.25a8.966 8.966 0 0 1 6-2.292
                            c1.052 0 2.062.18 3 .512v14.25
                            A8.987 8.987 0 0 0 18 18
                            a8.967 8.967 0 0 0-6 2.292
                            m0-14.25v14.25"
                            />
                        </svg>
                    </div>

                    <h3 class="mt-5 text-lg font-bold text-dark">
                        Materi Pembelajaran
                    </h3>

                    <p class="mt-2 text-sm leading-relaxed text-dark/60">Materi disusun berdasarkan kategori dan topik yang mudah dipahami.</p>
                </div>

                {{-- 2 --}}
                <div
                    class="group relative min-h-[240px] overflow-hidden rounded-2xl border border-[#e9e6f2] bg-white p-7 transition-all duration-300 hover:-translate-y-1.5 hover:border-[#dcd7f0] hover:shadow-xl hover:shadow-dark/5"
                >
                    <span
                        class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-[#5595ed]/10 transition-transform duration-300 group-hover:scale-150"
                    ></span>

                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-[#5595ed] to-[#2f6fe0] text-white shadow-lg shadow-[#5595ed]/30"
                    >
                        <svg
                            class="h-7 w-7"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12.75 11.25 15 15 9.75
                            M21 12a9 9 0 1 1-18 0
                            9 9 0 0 1 18 0Z"
                            />
                        </svg>
                    </div>

                    <h3 class="mt-5 text-lg font-bold text-dark">
                        Latihan Soal
                    </h3>

                    <p class="mt-2 text-sm leading-relaxed text-dark/60">Kerjakan soal untuk menguji pemahamanmu setelah mempelajari materi.</p>
                </div>

                {{-- 3 --}}
                <div
                    class="group relative min-h-[240px] overflow-hidden rounded-2xl border border-[#e9e6f2] bg-white p-7 transition-all duration-300 hover:-translate-y-1.5 hover:border-[#dcd7f0] hover:shadow-xl hover:shadow-dark/5"
                >
                    <span
                        class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-[#45c69b]/10 transition-transform duration-300 group-hover:scale-150"
                    ></span>

                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-[#45c69b] to-[#1da97c] text-white shadow-lg shadow-[#45c69b]/30"
                    >
                        <svg
                            class="h-7 w-7"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3 13.5 7.5 9l4.5 4.5L21 4.5"
                            />

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3 20h18"
                            />
                        </svg>
                    </div>

                    <h3 class="mt-5 text-lg font-bold text-dark">
                        Hasil & Nilai
                    </h3>

                    <p class="mt-2 text-sm leading-relaxed text-dark/60">Lihat skor setiap latihan yang telah kamu kerjakan.</p>
                </div>

                {{-- 4 --}}
                <div
                    class="group relative min-h-[240px] overflow-hidden rounded-2xl border border-[#e9e6f2] bg-white p-7 transition-all duration-300 hover:-translate-y-1.5 hover:border-[#dcd7f0] hover:shadow-xl hover:shadow-dark/5"
                >
                    <span
                        class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-[#f5b43c]/10 transition-transform duration-300 group-hover:scale-150"
                    ></span>

                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-[#f5b43c] to-[#e8961c] text-white shadow-lg shadow-[#f5b43c]/30"
                    >
                        <svg
                            class="h-7 w-7"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            viewBox="0 0 24 24"
                        >
                            <circle cx="12" cy="12" r="8.5" />

                            <path stroke-linecap="round" d="M12 7v5l3.5 2" />
                        </svg>
                    </div>

                    <h3 class="mt-5 text-lg font-bold text-dark">
                        Riwayat Belajar
                    </h3>

                    <p class="mt-2 text-sm leading-relaxed text-dark/60">Pantau latihan yang sudah pernah kamu kerjakan kapan saja.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="materi" class="bg-white">
        <div class="mx-auto max-w-7xl px-6 sm:px-8 lg:px-10 py-16 lg:py-20">
            {{-- =========================
             HEADER
        ========================== --}}
            <div data-reveal class="text-center">
                <span
                    class="inline-flex items-center gap-2 rounded-full bg-lavender px-4 py-1.5 font-mono text-xs font-semibold uppercase tracking-[0.14em] text-primary"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>

                    Kategori Materi
                </span>

                <h2 class="mt-4 text-3xl sm:text-4xl font-extrabold text-dark">
                    Pilih Mata Pelajaran yang Ingin Kamu Pelajari
                </h2>

                <p class="mx-auto mt-3 max-w-2xl font-light text-sm sm:text-base text-dark/60">Temukan berbagai materi pelajaran sekolah yang bisa kamu pelajari sesuai kebutuhanmu.</p>
            </div>

            {{-- =========================
             KATEGORI
        ========================== --}}
            <div
                data-reveal-stagger
                class="mt-10 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-5"
            >
                {{-- Bahasa Indonesia --}}
                <a
                    href="#"
                    class="group relative min-h-[150px] overflow-hidden rounded-3xl border border-lavender bg-white p-5 transition-all duration-300 hover:-translate-y-2 hover:border-[#f492d3] hover:shadow-2xl hover:shadow-[#ed78c1]/20"
                >
                    <span
                        class="absolute -top-14 -right-14 h-32 w-32 rounded-full bg-[#ed78c1]/10 transition-transform duration-500 group-hover:scale-[2]"
                    ></span>

                    <span
                        class="misi-bar absolute inset-x-3 top-0 h-1 rounded-b-full bg-gradient-to-r from-white via-[#f492d3] to-[#ed78c1]"
                    ></span>

                    <div class="relative flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:gap-3.5">
                        <span
                            class="relative flex h-14 w-14 shrink-0 items-center justify-center"
                        >
                            <span
                                class="absolute inset-0 rounded-2xl bg-[#ed78c1]/15 transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110"
                            ></span>
                            <span
                                class="relative flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[#f492d3] to-[#ed78c1] text-white text-lg font-extrabold shadow-lg shadow-[#ed78c1]/30"
                            >
                                A
                            </span>
                        </span>
                        <div class="min-w-0">
                            <h3
                                class="line-clamp-2 text-sm font-bold text-dark transition-colors duration-300 group-hover:text-[#d94da8]"
                            >
                                Bahasa Indonesia
                            </h3>
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs text-dark/50">
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-[#ed78c1]"
                                ></span>
                                5 Materi
                            </p>
                        </div>
                    </div>

                    <span
                        class="absolute bottom-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-[#ed78c1]/10 text-[#d94da8] text-sm transition-all duration-300 group-hover:bg-[#ed78c1] group-hover:text-white group-hover:translate-x-0.5"
                        >→</span
                    >
                </a>

                {{-- PPKN --}}
                <a
                    href="#"
                    class="group relative min-h-[150px] overflow-hidden rounded-3xl border border-lavender bg-white p-5 transition-all duration-300 hover:-translate-y-2 hover:border-[#f6cd6b] hover:shadow-2xl hover:shadow-[#f2bd45]/20"
                >
                    <span
                        class="absolute -top-14 -right-14 h-32 w-32 rounded-full bg-[#f2bd45]/10 transition-transform duration-500 group-hover:scale-[2]"
                    ></span>

                    <span
                        class="misi-bar absolute inset-x-3 top-0 h-1 rounded-b-full bg-gradient-to-r from-white via-[#f6cd6b] to-[#f2bd45]"
                    ></span>

                    <div class="relative flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:gap-3.5">
                        <span
                            class="relative flex h-14 w-14 shrink-0 items-center justify-center"
                        >
                            <span
                                class="absolute inset-0 rounded-2xl bg-[#f2bd45]/15 transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110"
                            ></span>
                            <span
                                class="relative flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[#f6cd6b] to-[#f2bd45] text-white text-lg font-extrabold shadow-lg shadow-[#f2bd45]/30"
                            >
                                🇮🇩
                            </span>
                        </span>
                        <div class="min-w-0">
                            <h3
                                class="line-clamp-2 text-sm font-bold text-dark transition-colors duration-300 group-hover:text-[#c99213]"
                            >
                                PPKN
                            </h3>
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs text-dark/50">
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-[#f2bd45]"
                                ></span>
                                5 Materi
                            </p>
                        </div>
                    </div>

                    <span
                        class="absolute bottom-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-[#f2bd45]/10 text-[#c99213] text-sm transition-all duration-300 group-hover:bg-[#f2bd45] group-hover:text-white group-hover:translate-x-0.5"
                        >→</span
                    >
                </a>

                {{-- Sejarah --}}
                <a
                    href="#"
                    class="group relative min-h-[150px] overflow-hidden rounded-3xl border border-lavender bg-white p-5 transition-all duration-300 hover:-translate-y-2 hover:border-[#8b80e6] hover:shadow-2xl hover:shadow-[#7164d9]/20"
                >
                    <span
                        class="absolute -top-14 -right-14 h-32 w-32 rounded-full bg-[#7164d9]/10 transition-transform duration-500 group-hover:scale-[2]"
                    ></span>

                    <span
                        class="misi-bar absolute inset-x-3 top-0 h-1 rounded-b-full bg-gradient-to-r from-white via-[#8b80e6] to-[#7164d9]"
                    ></span>

                    <div class="relative flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:gap-3.5">
                        <span
                            class="relative flex h-14 w-14 shrink-0 items-center justify-center"
                        >
                            <span
                                class="absolute inset-0 rounded-2xl bg-[#7164d9]/15 transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110"
                            ></span>
                            <span
                                class="relative flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[#8b80e6] to-[#7164d9] text-white text-lg font-extrabold shadow-lg shadow-[#7164d9]/30"
                            >
                                🏛
                            </span>
                        </span>
                        <div class="min-w-0">
                            <h3
                                class="line-clamp-2 text-sm font-bold text-dark transition-colors duration-300 group-hover:text-[#5a4cc9]"
                            >
                                Sejarah
                            </h3>
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs text-dark/50">
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-[#7164d9]"
                                ></span>
                                5 Materi
                            </p>
                        </div>
                    </div>

                    <span
                        class="absolute bottom-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-[#7164d9]/10 text-[#5a4cc9] text-sm transition-all duration-300 group-hover:bg-[#7164d9] group-hover:text-white group-hover:translate-x-0.5"
                        >→</span
                    >
                </a>

                {{-- Matematika --}}
                <a
                    href="#"
                    class="group relative min-h-[150px] overflow-hidden rounded-3xl border border-lavender bg-white p-5 transition-all duration-300 hover:-translate-y-2 hover:border-[#f0858b] hover:shadow-2xl hover:shadow-[#ed6970]/20"
                >
                    <span
                        class="absolute -top-14 -right-14 h-32 w-32 rounded-full bg-[#ed6970]/10 transition-transform duration-500 group-hover:scale-[2]"
                    ></span>

                    <span
                        class="misi-bar absolute inset-x-3 top-0 h-1 rounded-b-full bg-gradient-to-r from-white via-[#f0858b] to-[#ed6970]"
                    ></span>

                    <div class="relative flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:gap-3.5">
                        <span
                            class="relative flex h-14 w-14 shrink-0 items-center justify-center"
                        >
                            <span
                                class="absolute inset-0 rounded-2xl bg-[#ed6970]/15 transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110"
                            ></span>
                            <span
                                class="relative flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[#f0858b] to-[#ed6970] text-white text-lg font-extrabold shadow-lg shadow-[#ed6970]/30"
                            >
                                π
                            </span>
                        </span>
                        <div class="min-w-0">
                            <h3
                                class="line-clamp-2 text-sm font-bold text-dark transition-colors duration-300 group-hover:text-[#d94a53]"
                            >
                                Matematika
                            </h3>
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs text-dark/50">
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-[#ed6970]"
                                ></span>
                                5 Materi
                            </p>
                        </div>
                    </div>

                    <span
                        class="absolute bottom-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-[#ed6970]/10 text-[#d94a53] text-sm transition-all duration-300 group-hover:bg-[#ed6970] group-hover:text-white group-hover:translate-x-0.5"
                        >→</span
                    >
                </a>

                {{-- IPA --}}
                <a
                    href="#"
                    class="group relative min-h-[150px] overflow-hidden rounded-3xl border border-lavender bg-white p-5 transition-all duration-300 hover:-translate-y-2 hover:border-[#6bcb91] hover:shadow-2xl hover:shadow-[#52bd7d]/20"
                >
                    <span
                        class="absolute -top-14 -right-14 h-32 w-32 rounded-full bg-[#52bd7d]/10 transition-transform duration-500 group-hover:scale-[2]"
                    ></span>

                    <span
                        class="misi-bar absolute inset-x-3 top-0 h-1 rounded-b-full bg-gradient-to-r from-white via-[#6bcb91] to-[#52bd7d]"
                    ></span>

                    <div class="relative flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:gap-3.5">
                        <span
                            class="relative flex h-14 w-14 shrink-0 items-center justify-center"
                        >
                            <span
                                class="absolute inset-0 rounded-2xl bg-[#52bd7d]/15 transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110"
                            ></span>
                            <span
                                class="relative flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[#6bcb91] to-[#52bd7d] text-white text-lg font-extrabold shadow-lg shadow-[#52bd7d]/30"
                            >
                                🔬
                            </span>
                        </span>
                        <div class="min-w-0">
                            <h3
                                class="line-clamp-2 text-sm font-bold text-dark transition-colors duration-300 group-hover:text-[#379458]"
                            >
                                IPA
                            </h3>
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs text-dark/50">
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-[#52bd7d]"
                                ></span>
                                5 Materi
                            </p>
                        </div>
                    </div>

                    <span
                        class="absolute bottom-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-[#52bd7d]/10 text-[#379458] text-sm transition-all duration-300 group-hover:bg-[#52bd7d] group-hover:text-white group-hover:translate-x-0.5"
                        >→</span
                    >
                </a>

                {{-- Bahasa Inggris --}}
                <a
                    href="#"
                    class="group relative min-h-[150px] overflow-hidden rounded-3xl border border-lavender bg-white p-5 transition-all duration-300 hover:-translate-y-2 hover:border-[#4dbdcf] hover:shadow-2xl hover:shadow-[#36a7bb]/20"
                >
                    <span
                        class="absolute -top-14 -right-14 h-32 w-32 rounded-full bg-[#36a7bb]/10 transition-transform duration-500 group-hover:scale-[2]"
                    ></span>

                    <span
                        class="misi-bar absolute inset-x-3 top-0 h-1 rounded-b-full bg-gradient-to-r from-white via-[#4dbdcf] to-[#36a7bb]"
                    ></span>

                    <div class="relative flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:gap-3.5">
                        <span
                            class="relative flex h-14 w-14 shrink-0 items-center justify-center"
                        >
                            <span
                                class="absolute inset-0 rounded-2xl bg-[#36a7bb]/15 transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110"
                            ></span>
                            <span
                                class="relative flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[#4dbdcf] to-[#36a7bb] text-white text-lg font-extrabold shadow-lg shadow-[#36a7bb]/30"
                            >
                                EN
                            </span>
                        </span>
                        <div class="min-w-0">
                            <h3
                                class="line-clamp-2 text-sm font-bold text-dark transition-colors duration-300 group-hover:text-[#2088a0]"
                            >
                                Bahasa Inggris
                            </h3>
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs text-dark/50">
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-[#36a7bb]"
                                ></span>
                                5 Materi
                            </p>
                        </div>
                    </div>

                    <span
                        class="absolute bottom-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-[#36a7bb]/10 text-[#2088a0] text-sm transition-all duration-300 group-hover:bg-[#36a7bb] group-hover:text-white group-hover:translate-x-0.5"
                        >→</span
                    >
                </a>

                {{-- Seni Budaya --}}
                <a
                    href="#"
                    class="group relative min-h-[150px] overflow-hidden rounded-3xl border border-lavender bg-white p-5 transition-all duration-300 hover:-translate-y-2 hover:border-[#f5a564] hover:shadow-2xl hover:shadow-[#f2924a]/20"
                >
                    <span
                        class="absolute -top-14 -right-14 h-32 w-32 rounded-full bg-[#f2924a]/10 transition-transform duration-500 group-hover:scale-[2]"
                    ></span>

                    <span
                        class="misi-bar absolute inset-x-3 top-0 h-1 rounded-b-full bg-gradient-to-r from-white via-[#f5a564] to-[#f2924a]"
                    ></span>

                    <div class="relative flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:gap-3.5">
                        <span
                            class="relative flex h-14 w-14 shrink-0 items-center justify-center"
                        >
                            <span
                                class="absolute inset-0 rounded-2xl bg-[#f2924a]/15 transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110"
                            ></span>
                            <span
                                class="relative flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[#f5a564] to-[#f2924a] text-white text-lg font-extrabold shadow-lg shadow-[#f2924a]/30"
                            >
                                🎨
                            </span>
                        </span>
                        <div class="min-w-0">
                            <h3
                                class="line-clamp-2 text-sm font-bold text-dark transition-colors duration-300 group-hover:text-[#d96a21]"
                            >
                                Seni Budaya
                            </h3>
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs text-dark/50">
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-[#f2924a]"
                                ></span>
                                5 Materi
                            </p>
                        </div>
                    </div>

                    <span
                        class="absolute bottom-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-[#f2924a]/10 text-[#d96a21] text-sm transition-all duration-300 group-hover:bg-[#f2924a] group-hover:text-white group-hover:translate-x-0.5"
                        >→</span
                    >
                </a>

                {{-- PJOK --}}
                <a
                    href="#"
                    class="group relative min-h-[150px] overflow-hidden rounded-3xl border border-lavender bg-white p-5 transition-all duration-300 hover:-translate-y-2 hover:border-[#6ba0f5] hover:shadow-2xl hover:shadow-[#4f8ff0]/20"
                >
                    <span
                        class="absolute -top-14 -right-14 h-32 w-32 rounded-full bg-[#4f8ff0]/10 transition-transform duration-500 group-hover:scale-[2]"
                    ></span>

                    <span
                        class="misi-bar absolute inset-x-3 top-0 h-1 rounded-b-full bg-gradient-to-r from-white via-[#6ba0f5] to-[#4f8ff0]"
                    ></span>

                    <div class="relative flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:gap-3.5">
                        <span
                            class="relative flex h-14 w-14 shrink-0 items-center justify-center"
                        >
                            <span
                                class="absolute inset-0 rounded-2xl bg-[#4f8ff0]/15 transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110"
                            ></span>
                            <span
                                class="relative flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[#6ba0f5] to-[#4f8ff0] text-white text-lg font-extrabold shadow-lg shadow-[#4f8ff0]/30"
                            >
                                🏃
                            </span>
                        </span>
                        <div class="min-w-0">
                            <h3
                                class="line-clamp-2 text-sm font-bold text-dark transition-colors duration-300 group-hover:text-[#2a66cc]"
                            >
                                PJOK
                            </h3>
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs text-dark/50">
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-[#4f8ff0]"
                                ></span>
                                5 Materi
                            </p>
                        </div>
                    </div>

                    <span
                        class="absolute bottom-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-[#4f8ff0]/10 text-[#2a66cc] text-sm transition-all duration-300 group-hover:bg-[#4f8ff0] group-hover:text-white group-hover:translate-x-0.5"
                        >→</span
                    >
                </a>

                {{-- Geografi --}}
                <a
                    href="#"
                    class="group relative min-h-[150px] overflow-hidden rounded-3xl border border-lavender bg-white p-5 transition-all duration-300 hover:-translate-y-2 hover:border-[#d8a06a] hover:shadow-2xl hover:shadow-[#c88a4f]/20"
                >
                    <span
                        class="absolute -top-14 -right-14 h-32 w-32 rounded-full bg-[#c88a4f]/10 transition-transform duration-500 group-hover:scale-[2]"
                    ></span>

                    <span
                        class="misi-bar absolute inset-x-3 top-0 h-1 rounded-b-full bg-gradient-to-r from-white via-[#d8a06a] to-[#c88a4f]"
                    ></span>

                    <div class="relative flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:gap-3.5">
                        <span
                            class="relative flex h-14 w-14 shrink-0 items-center justify-center"
                        >
                            <span
                                class="absolute inset-0 rounded-2xl bg-[#c88a4f]/15 transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110"
                            ></span>
                            <span
                                class="relative flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[#d8a06a] to-[#c88a4f] text-white text-lg font-extrabold shadow-lg shadow-[#c88a4f]/30"
                            >
                                🌍
                            </span>
                        </span>
                        <div class="min-w-0">
                            <h3
                                class="line-clamp-2 text-sm font-bold text-dark transition-colors duration-300 group-hover:text-[#9c611f]"
                            >
                                Geografi
                            </h3>
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs text-dark/50">
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-[#c88a4f]"
                                ></span>
                                5 Materi
                            </p>
                        </div>
                    </div>

                    <span
                        class="absolute bottom-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-[#c88a4f]/10 text-[#9c611f] text-sm transition-all duration-300 group-hover:bg-[#c88a4f] group-hover:text-white group-hover:translate-x-0.5"
                        >→</span
                    >
                </a>

                {{-- Ekonomi --}}
                <a
                    href="#"
                    class="group relative min-h-[150px] overflow-hidden rounded-3xl border border-lavender bg-white p-5 transition-all duration-300 hover:-translate-y-2 hover:border-[#6c7cf0] hover:shadow-2xl hover:shadow-[#4f46e5]/20"
                >
                    <span
                        class="absolute -top-14 -right-14 h-32 w-32 rounded-full bg-[#4f46e5]/10 transition-transform duration-500 group-hover:scale-[2]"
                    ></span>

                    <span
                        class="misi-bar absolute inset-x-3 top-0 h-1 rounded-b-full bg-gradient-to-r from-white via-[#6c7cf0] to-[#4f46e5]"
                    ></span>

                    <div class="relative flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:gap-3.5">
                        <span
                            class="relative flex h-14 w-14 shrink-0 items-center justify-center"
                        >
                            <span
                                class="absolute inset-0 rounded-2xl bg-[#4f46e5]/15 transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110"
                            ></span>
                            <span
                                class="relative flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[#6c7cf0] to-[#4f46e5] text-white text-lg font-extrabold shadow-lg shadow-[#4f46e5]/30"
                            >
                                💰
                            </span>
                        </span>
                        <div class="min-w-0">
                            <h3
                                class="line-clamp-2 text-sm font-bold text-dark transition-colors duration-300 group-hover:text-[#3326c4]"
                            >
                                Ekonomi
                            </h3>
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs text-dark/50">
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-[#4f46e5]"
                                ></span>
                                5 Materi
                            </p>
                        </div>
                    </div>

                    <span
                        class="absolute bottom-4 right-4 flex h-8 w-8 items-center justify-center rounded-full bg-[#4f46e5]/10 text-[#3326c4] text-sm transition-all duration-300 group-hover:bg-[#4f46e5] group-hover:text-white group-hover:translate-x-0.5"
                        >→</span
                    >
                </a>
            </div>
        </div>
    </section>

    <section id="carakerja" class="bg-[#faf9ff]">
        <div class="mx-auto max-w-7xl px-6 sm:px-8 lg:px-10 py-14 lg:py-16">
            {{-- =========================
             HEADER
        ========================== --}}
            <div data-reveal class="text-center">
                <div class="flex items-center justify-center gap-3">
                    <span class="w-10 h-px bg-primary/50"></span>

                    <span class="font-mono text-xs sm:text-sm font-semibold uppercase tracking-[0.14em] text-dark/70">
                        Cara Kerja
                    </span>

                    <span class="w-10 h-px bg-primary/50"></span>
                </div>

                <h2 class="mt-2 text-2xl sm:text-3xl font-extrabold text-dark">
                    Mulai Belajar dalam 4 Langkah Sederhana
                </h2>
            </div>

            {{-- =========================
             STEPS
        ========================== --}}
            <div class="relative mt-10 lg:mt-12">
                {{-- Tanda panah penghubung --}}
                <span
                    class="absolute left-[25%] top-10 hidden -translate-x-1/2 -translate-y-1/2 text-primary min-[480px]:flex"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </span>

                <span
                    class="absolute left-1/2 top-10 hidden -translate-x-1/2 -translate-y-1/2 text-primary min-[480px]:flex"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </span>

                <span
                    class="absolute left-[75%] top-10 hidden -translate-x-1/2 -translate-y-1/2 text-primary min-[480px]:flex"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </span>

                <div data-reveal-stagger class="grid grid-cols-2 min-[480px]:grid-cols-4 gap-5 lg:gap-6">
                    {{-- STEP 1 --}}
                    <div class="relative text-center">
                        <div class="relative mx-auto w-fit">
                            <div
                                class="flex items-center justify-center w-20 h-20 rounded-full bg-[#f0eaff] text-primary"
                            >
                                <svg
                                    class="w-10 h-10"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    viewBox="0 0 24 24"
                                >
                                    <circle cx="12" cy="8" r="3.5" />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M5 20c.7-3.5 3.3-5.5 7-5.5s6.3 2 7 5.5"
                                    />
                                </svg>
                            </div>

                            {{-- Nomor --}}
                            <span
                                class="absolute -bottom-2 left-1/2 -translate-x-1/2 flex items-center justify-center w-6 h-6 rounded-full bg-primary text-white font-mono text-[11px] font-bold ring-4 ring-[#faf9ff]"
                            >
                                1
                            </span>
                        </div>

                        <h3 class="mt-6 text-sm font-bold text-dark">
                            Daftar / Login
                        </h3>

                        <p
                            class="mt-2 mx-auto max-w-[190px] text-xs sm:text-sm leading-relaxed text-dark/55"
                        >Buat akun atau masuk ke akun yang sudah ada.</p>
                    </div>

                    {{-- STEP 2 --}}
                    <div class="relative text-center">
                        <div class="relative mx-auto w-fit">
                            <div
                                class="flex items-center justify-center w-20 h-20 rounded-full bg-[#f0eaff] text-primary"
                            >
                                <svg
                                    class="w-10 h-10"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21V5.5Z"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        d="M4 18.5A2.5 2.5 0 0 1 6.5 16H20"
                                    />
                                </svg>
                            </div>

                            <span
                                class="absolute -bottom-2 left-1/2 -translate-x-1/2 flex items-center justify-center w-6 h-6 rounded-full bg-primary text-white font-mono text-[11px] font-bold ring-4 ring-[#faf9ff]"
                            >
                                2
                            </span>
                        </div>

                        <h3 class="mt-6 text-sm font-bold text-dark">
                            Pelajari Materi
                        </h3>

                        <p
                            class="mt-2 mx-auto max-w-[190px] text-xs sm:text-sm leading-relaxed text-dark/55"
                        >Baca materi sesuai topik yang kamu pilih.</p>
                    </div>

                    {{-- STEP 3 --}}
                    <div class="relative text-center">
                        <div class="relative mx-auto w-fit">
                            <div
                                class="flex items-center justify-center w-20 h-20 rounded-full bg-[#f0eaff] text-primary"
                            >
                                <svg
                                    class="w-10 h-10"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    viewBox="0 0 24 24"
                                >
                                    <rect
                                        x="5"
                                        y="3"
                                        width="14"
                                        height="18"
                                        rx="2"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m8.5 12 2 2 5-5"
                                    />
                                </svg>
                            </div>

                            <span
                                class="absolute -bottom-2 left-1/2 -translate-x-1/2 flex items-center justify-center w-6 h-6 rounded-full bg-primary text-white font-mono text-[11px] font-bold ring-4 ring-[#faf9ff]"
                            >
                                3
                            </span>
                        </div>

                        <h3 class="mt-6 text-sm font-bold text-dark">
                            Kerjakan Soal
                        </h3>

                        <p
                            class="mt-2 mx-auto max-w-[190px] text-xs sm:text-sm leading-relaxed text-dark/55"
                        >Uji pemahamanmu dengan latihan soal interaktif.</p>
                    </div>

                    {{-- STEP 4 --}}
                    <div class="relative text-center">
                        <div class="relative mx-auto w-fit">
                            <div
                                class="flex items-center justify-center w-20 h-20 rounded-full bg-[#f0eaff] text-primary"
                            >
                                <svg
                                    class="w-10 h-10"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M8 21h8M12 17v4M6 4h12v3a6 6 0 0 1-12 0V4Z"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6 6H3a4 4 0 0 0 4 5M18 6h3a4 4 0 0 1-4 5"
                                    />
                                </svg>
                            </div>

                            <span
                                class="absolute -bottom-2 left-1/2 -translate-x-1/2 flex items-center justify-center w-6 h-6 rounded-full bg-primary text-white font-mono text-[11px] font-bold ring-4 ring-[#faf9ff]"
                            >
                                4
                            </span>
                        </div>

                        <h3 class="mt-6 text-sm font-bold text-dark">
                            Lihat Nilai
                        </h3>

                        <p
                            class="mt-2 mx-auto max-w-[190px] text-xs sm:text-sm leading-relaxed text-dark/55"
                        >Dapatkan hasil dan pembahasan dari setiap soal.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-8 lg:py-10">
        <div
            data-reveal="zoom"
            class="relative overflow-hidden rounded-2xl bg-[#9d8df0] text-white px-6 py-5 lg:px-10 lg:py-6"
        >
            {{-- Dekorasi background --}}
            <div
                class="absolute -top-16 -left-16 w-36 h-36 rounded-full bg-white/10"
            ></div>
            <div
                class="absolute -bottom-16 right-16 w-48 h-48 rounded-full bg-white/5"
            ></div>

            <div class="relative flex items-center justify-between gap-6">
                {{-- =========================
                 ILUSTRASI KIRI
            ========================== --}}
                <div
                    class="hidden md:flex w-[24%] shrink-0 items-center justify-center"
                >
                    <img
                        src="{{ asset('images/cover.png') }}"
                        alt="Ilustrasi belajar"
                        class="w-[180px] lg:w-[230px] h-[100px] lg:h-[150px] object-contain object-center"
                    />
                </div>

                {{-- =========================
                 TEXT TENGAH
            ========================== --}}
                <div class="flex-1 text-center">
                    <h2 class="font-serif text-lg lg:text-xl font-bold">
                        Siap mulai belajar?
                    </h2>

                    <p class="mt-1.5 text-xs lg:text-sm text-white/80">Jangan tunggu nanti, tingkatkan skill-mu sekarang juga!</p>

                    <a
                        href="{{ url('/register') }}"
                        class="mt-3 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white text-primary text-xs lg:text-sm font-bold shadow-md hover:-translate-y-0.5 hover:shadow-lg transition"
                    >
                        Mulai Sekarang
                        <span>→</span>
                    </a>
                </div>

                {{-- =========================
                 TULISAN KANAN
            ========================== --}}
                <div class="hidden md:block w-[24%] shrink-0 text-center">
                    <p
                        class="font-hand text-xl lg:text-2xl font-semibold leading-snug text-white/90 rotate-[-3deg]"
                    >Langkah kecil<br />
                    hari ini, untuk<br />
                    masa depan yang<br />
                    lebih besar ♥</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-white">
        <div class="mx-auto max-w-7xl px-6 sm:px-8 lg:px-10 py-10 lg:py-12">
            <div data-reveal-stagger class="grid grid-cols-2 md:grid-cols-3 gap-10 lg:gap-16">
                {{-- =========================
                 BRAND
            ========================== --}}
                <div class="col-span-2 md:col-span-1">
                    <a
                        href="{{ url('/') }}"
                        class="inline-flex items-center gap-3"
                    >
                        <span
                            class="flex items-center justify-center w-10 h-10 rounded-xl bg-white text-[#17172f]"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path d="M12 3 3 7.5 12 12l7-3.5V14h2V7.5L12 3Z" />
                                <path d="M5 10v5.5c0 1.5 3.13 4.5 7 4.5s7-3 7-4.5V10l-7 3.5L5 10Z" />
                            </svg>
                        </span>

                        <span>
                            <span class="block text-lg font-bold leading-none">
                                KelasKita
                            </span>

                            <span class="mt-1 block font-light text-[10px] text-white/60">
                                Belajar · Latihan · Naik Level
                            </span>
                        </span>
                    </a>

                    <p class="mt-6 max-w-sm font-light text-sm leading-relaxed text-white/60">KelasKita adalah platform belajar online yang membantu kamu memahami materi dan melatih kemampuan melalui soal-soal interaktif.</p>
                </div>

                {{-- =========================
                 MENU
            ========================== --}}
                <div>
                    <h3 class="text-sm font-bold text-white">Menu</h3>

                    <nav class="mt-4 flex flex-col gap-2">
                        <a
                            href="#beranda"
                            class="w-fit font-mono text-xs uppercase tracking-[0.14em] text-white/60 hover:text-white transition"
                        >
                            Beranda
                        </a>

                        <a
                            href="#materi"
                            class="w-fit font-mono text-xs uppercase tracking-[0.14em] text-white/60 hover:text-white transition"
                        >
                            Materi
                        </a>

                        <a
                            href="#tentang"
                            class="w-fit font-mono text-xs uppercase tracking-[0.14em] text-white/60 hover:text-white transition"
                        >
                            Tentang
                        </a>

                        <a
                            href="{{ url('/login') }}"
                            class="w-fit font-mono text-xs uppercase tracking-[0.14em] text-white/60 hover:text-white transition"
                        >
                            Login
                        </a>

                        <a
                            href="{{ url('/register') }}"
                            class="w-fit font-mono text-xs uppercase tracking-[0.14em] text-white/60 hover:text-white transition"
                        >
                            Daftar
                        </a>
                    </nav>
                </div>

                {{-- =========================
                 KONTAK
            ========================== --}}
                <div>
                    <h3 class="text-sm font-bold text-white">Kontak</h3>

                    {{-- Email --}}
                    <a
                        href="mailto:kelaskita@gmail.com"
                        class="mt-4 flex w-fit max-w-full flex-wrap items-center gap-2 text-sm text-white/60 hover:text-white transition"
                    >
                        <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            viewBox="0 0 24 24"
                        >
                            <rect x="3" y="5" width="18" height="14" rx="2" />

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m3 7 9 6 9-6"
                            />
                        </svg>

                        kelaskita@gmail.com
                    </a>

                    {{-- Social Media --}}
                    <div class="mt-5 flex items-center gap-5">
                        {{-- Instagram --}}
                        <a
                            href="#"
                            aria-label="Instagram"
                            class="text-white/70 hover:text-white transition"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                viewBox="0 0 24 24"
                            >
                                <rect
                                    x="3"
                                    y="3"
                                    width="18"
                                    height="18"
                                    rx="5"
                                />

                                <circle cx="12" cy="12" r="4" />

                                <circle
                                    cx="17.5"
                                    cy="6.5"
                                    r=".7"
                                    fill="currentColor"
                                    stroke="none"
                                />
                            </svg>
                        </a>

                        {{-- TikTok --}}
                        <a
                            href="#"
                            aria-label="TikTok"
                            class="text-white/70 hover:text-white transition"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M15.5 3c.3 2 1.4 3.3 3.5 3.5v3
                                     c-1.5 0-2.8-.4-3.9-1.1v6.4
                                     a5.2 5.2 0 1 1-4.5-5.2v3
                                     a2.2 2.2 0 1 0 1.5 2.1V3h3.4Z"
                                />
                            </svg>
                        </a>

                        {{-- YouTube --}}
                        <a
                            href="#"
                            aria-label="YouTube"
                            class="text-white/70 hover:text-white transition"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M21.6 7.2a2.8 2.8 0 0 0-2-2
                                     C17.8 4.7 12 4.7 12 4.7
                                     s-5.8 0-7.6.5a2.8 2.8 0 0 0-2
                                     2A29 29 0 0 0 2 12a29 29 0 0 0
                                     .4 4.8 2.8 2.8 0 0 0 2 2
                                     c1.8.5 7.6.5 7.6.5s5.8 0 7.6-.5
                                     a2.8 2.8 0 0 0 2-2A29 29 0 0 0
                                     22 12a29 29 0 0 0-.4-4.8ZM10 15.5
                                     v-7l6 3.5-6 3.5Z"
                                />
                            </svg>
                        </a>

                        {{-- X --}}
                        <a
                            href="#"
                            aria-label="X"
                            class="text-white/70 hover:text-white transition"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M5 4h4.5l3.4 4.6L16.8 4H19l-5.1
                                     5.8L20 20h-4.5l-3.8-5.1L7.2 20H5l5.5-6.3L5 4Zm3
                                     1.8 7.7 12.4h1.6L9.6 5.8H8Z"
                                />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            {{-- =========================
             BOTTOM
        ========================== --}}
            <div class="relative mt-10 pt-6 border-t border-white/10">
                <p class="text-center font-mono text-xs text-white/50 md:text-right">© 2026 KelasKita. All rights reserved.</p>
            </div>
        </div>
    </footer>

@endsection
