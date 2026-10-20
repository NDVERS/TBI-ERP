<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect('/');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        // Support quick-login by role if submitted
        if ($request->has('quick_role')) {
            $role = $request->input('quick_role');
            $user = User::where('role', $role)->first();
            if ($user) {
                if (! $user->is_active) {
                    return back()->withErrors(['email' => 'Akun dinonaktifkan oleh administrator.']);
                }

                Auth::login($user);
                $request->session()->regenerate();

                ActivityLog::create([
                    'user_id' => $user->id,
                    'action' => 'login',
                    'description' => "Pengguna {$user->name} ({$user->role}) berhasil masuk ke sistem.",
                    'ip_address' => $request->ip(),
                ]);

                return redirect()->intended('/');
            }
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            if (! $user->is_active) {
                Auth::logout();

                return back()->withErrors(['email' => 'Akun dinonaktifkan oleh administrator.']);
            }

            $request->session()->regenerate();

            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'login',
                'description' => "Pengguna {$user->name} ({$user->role}) berhasil masuk ke sistem.",
                'ip_address' => $request->ip(),
            ]);

            return redirect()->intended('/');
        }

        return back()->withErrors([
            'email' => 'Kombinasi email dan password tidak sesuai.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($user = Auth::user()) {
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'logout',
                'description' => "Pengguna {$user->name} keluar dari sesi sistem.",
                'ip_address' => $request->ip(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
