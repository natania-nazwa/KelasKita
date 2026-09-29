<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('admin.dashboard', [
            'pengguna' => $request->user(),
            'jumlahPengguna' => User::query()->count(),
            'jumlahMateri' => Materi::query()->count(),
            'jumlahQuiz' => Quiz::query()->count(),
            'jumlahMateriMenunggu' => Materi::query()->menunggu()->count(),
            'jumlahQuizMenunggu' => Quiz::query()->menunggu()->count(),
            'aktivitas' => $this->aktivitasTerbaru(),
        ]);
    }

    /**
     * Materi yang baru saja berubah status, untuk daftar "Aktivitas Terbaru".
     *
     * Diurutkan dari yang paling baru diubah, bukan yang paling baru dibuat:
     * pada halaman admin yang paling berguna adalah perubahan keputusan,
     * termasuk materi yang baru saja disetujui atau ditolak.
     *
     * @return Collection<int, array{judul: string, keterangan: string, status: string, ikon: string, waktu: string}>
     */
    private function aktivitasTerbaru(): Collection
    {
        return Materi::query()
            ->with('pembuat')
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Materi $materi): array => [
                'judul' => $materi->nama,
                'keterangan' => $materi->pembuat?->nama ?? 'Admin',
                'status' => $materi->labelStatus(),
                'ikon' => $materi->status === Materi::STATUS_PENDING ? 'jam' : 'buku',
                'waktu' => $materi->updated_at?->diffForHumans() ?? 'baru saja',
            ]);
    }
}
