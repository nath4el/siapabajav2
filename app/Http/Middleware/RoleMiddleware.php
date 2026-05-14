<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Normalisasi role user
        $userRole = strtolower(trim((string) ($user->role ?? '')));

        // 🔥 Support legacy "super admin"
        if ($userRole === 'super admin') {
            $userRole = 'super_admin';
        }

        // Normalisasi semua role yang diizinkan
        $roles = array_map(function ($role) {
            $role = strtolower(trim($role));
            return $role === 'super admin' ? 'super_admin' : $role;
        }, $roles);

        // Cek apakah user punya akses
        if (!in_array($userRole, $roles, true)) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}