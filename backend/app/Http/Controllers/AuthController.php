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
\Log::info('$result: ' . print_r($result, true));
        return response()->json($result);
        
    }
    /*
    public function login(Request $request)
    {
        // CSRF cookie-t a frontend kéri a /sanctum/csrf-cookie-ról
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        
        // Előbb ellenőrizzük a felhasználót és a tiltást
        $user = User::where('email', $credentials['email'])->first();
        
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        if ($user->is_blocked) {
            return response()->json(['message' => 'User is blocked'], Response::HTTP_FORBIDDEN);
        }
        
        // Bejelentkeztetés és session regenerálás
        Auth::login($user, remember: false);
        $request->session()->regenerate();
        
        // Napló
        activity()
            ->causedBy($user)
            ->event('auth.login')
            ->log('User logged in');
        
        // Válasz a frontnak (role-okkal)
        $user->load('roles:id,name','permissions:id,name'); // direkt perm-ek
        $allPerms = $user->getAllPermissions()->pluck('name'); // szerepkörből öröklöttekkel együtt
        
        return response()->json([
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'roles'       => $user->roles->pluck('name'),
            'permissions' => $allPerms,
            'is_blocked'  => (bool) (auth()->user()->is_blocked ?? false),
            
//            'user' => [
//                'id'    => $user->id,
//                'name'  => $user->name,
//                'email' => $user->email,
//                'roles' => $user->roles->pluck('name'),
//                'permissions' => $allPerms,
//            ],
            
        ]);
    }
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
            /*
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'permissions' => $allPerms,
            ]
            */
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
