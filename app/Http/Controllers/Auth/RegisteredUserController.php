<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:tb_pengguna,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        /*
         * "peran" SENGAJA tidak diambil dari input.
         * Tidak ada kolom peran di form, jadi tidak ada cara
         * mendaftarkan dirinya sebagai admin. Nilai default 'user'
         * berasal dari default kolom di migration.
         */
        $user = User::create([
            'nama' => $data['name'],
            'email' => $data['email'],
            'kata_sandi' => $data['password'],
        ]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('user.dashboard');
    }
}
