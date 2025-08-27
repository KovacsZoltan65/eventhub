<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Hibás adatok'], 401);
        }
        $user = $request->user();
        $token = $user->createToken('api-token')->plainTextToken;
        
        $user->load('roles:id,name','permissions:id,name'); // direkt perm-ek
        $allPerms = $user->getAllPermissions()->pluck('name'); // szerepkörből öröklöttekkel együtt
        
        //return response()->json(['token' => $token]);
        $result = [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'roles'       => $user->roles->pluck('name'),
            'permissions' => $allPerms,
            'is_blocked'  => (bool) (auth()->user()->is_blocked ?? false),
            'token'       => $token,
        ];

        return response()->json($result);
    }
    
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
