<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Aplikasi;
use Illuminate\View\View;

/**
 * "Informasi Sistem" dan "Tentang Kelas Kita" di area Pengaturan admin.
 *
 * Dua halaman, satu sumber data: App\Support\Aplikasi. Versinya dibaca dari
 * APP_VERSION di .env, bukan ditulis di view, jadi naik versi cukup mengubah satu
 * baris.
 *
 * Status server dan database diukur, bukan ditulis. Request yang sedang
 * dilayani sudah sampai ke controller ini kalau halaman sampai terender, jadi
 * "Online" di situ berarti permintaan ini sendiri berhasil. Database diuji
 * dengan satu koneksi dan satu pernyataan SELECT 1, sehingga ketika server
 * sedang salah, halamannya menunjukkan keadaan yang sebenarnya.
 */
class SistemController extends Controller
{
    public function __construct(private readonly Aplikasi $aplikasi) {}

    /**
     * Informasi Sistem: nama, versi, lingkungan, dan status nyata.
     */
    public function index(): View
    {
        return view('admin.pengaturan.sistem', [
            'aplikasi' => $this->aplikasi,
            'statusServer' => $this->aplikasi->statusServer(),
            'statusBasisData' => $this->aplikasi->statusBasisData(),
        ]);
    }

    /**
     * Tentang Kelas Kita: nama, versi, tahun, dan singkatannya.
     */
    public function tentang(): View
    {
        return view('admin.pengaturan.tentang', [
            'aplikasi' => $this->aplikasi,
        ]);
    }
}
