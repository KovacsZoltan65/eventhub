<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Hitelesíti a felhasználót, és egy választ ad vissza a tokennel.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        // Felhasználó bejelentkezésének kísérlete
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Hibás adatok'], 401);
        }

        // Szerezd meg a felhasználót
        $user = $request->user();

        // Szerezd meg a tokent
        $token = $user->createToken('api-token')->plainTextToken;

        // Szerepkörök és engedélyek betöltése
        $user->load('roles:id,name','permissions:id,name');

        // Minden jogosultság lekérése (közvetlen és örökölt)
        $allPerms = $user->getAllPermissions()->pluck('name');

        // Építsd fel a választ
        $result = [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'roles'       => $user->roles->pluck('name'),
            'permissions' => $allPerms,
            'is_blocked'  => (bool) (auth()->user()->is_blocked ?? false),
            'token'       => $token,
        ];

        // A válasz visszaküldése
        return response()->json($result);
    }

    /**
     * Bejelentkezett felhasználó adatainak lekérése.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('roles:id,name');

        if (!$user) return response()->json(['user'=>null]);

        $allPerms = $user->getAllPermissions()->pluck('name');

        return response()->json([
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'roles'       => $user->roles->pluck('name'),
            'permissions' => $allPerms,
            'is_blocked'  => (bool) (auth()->user()->is_blocked ?? false),
        ]);
    }

    /**
     * Bejelentkezett felhasználó kijelentkezése.
     *
     * A session-t is invalidálja, és új tokent generál.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function logout(Request $request)
    {
        if ($request->user()) {

            activity()
                ->causedBy($request->user())
                ->event('auth.logout')
                ->log('User logged out');
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }
}
