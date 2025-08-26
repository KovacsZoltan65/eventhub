<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AdminUserController extends Controller
{
    /**
     * Felhasználók listázása
     * Szűrés: search (name/email), role, blocked (0/1)
     * Rendezés: field (name|email|created_at|is_blocked), order (asc|desc)
     * Pagináció: per_page, page
     */
    public function userList(Request $request)
    {
        $validated = $request->validate([
            'search'    => 'sometimes|string|max:255',
            'role'      => 'sometimes|string|max:100',
            'blocked'   => 'sometimes|in:0,1',
            'field'     => 'sometimes|in:name,email,created_at,is_blocked',
            'order'     => 'sometimes|in:asc,desc',
            'per_page'  => 'sometimes|integer|min:1|max:100',
            'page'      => 'sometimes|integer|min:1',
        ]);
        
        $q = User::query()
            ->with('roles:id,name');
        
        if (!empty($validated['search'])) {
            $s = $validated['search'];
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%");
            });
        }
        
        if (!empty($validated['role'])) {
            $role = $validated['role'];
            $q->whereHas('roles', fn($rq) => $rq->where('name', $role));
        }
        
        if (isset($validated['blocked'])) {
            $q->where('is_blocked', (bool) $validated['blocked']);
        }
        
        $field = $validated['field'] ?? 'created_at';
        $order = $validated['order'] ?? 'desc';
        
        $q->orderBy($field, $order);
        
        $perPage = (int) ($validated['per_page'] ?? 20);
        $paginator = $q->paginate($perPage);
        
        return UserResource::collection($paginator);
    }
    
    /**
     * Felhasználó tiltása (is_blocked = true)
     */
    public function userBlock(Request $request, User $user)
    {
        // Biztonság: ne tudd magad letiltani
        if ((int)$request->user()->id === (int)$user->id) {
            return response()->json(['message' => 'Saját fiókot nem lehet tiltani.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        if ($user->is_blocked) {
            return response()->json(['message' => 'A felhasználó már tiltva van.'], Response::HTTP_CONFLICT);
        }
        
        DB::transaction(function() use ($request, $user) {
            $user->update(['is_blocked' => true]);
            
            activity()
                ->causedBy($request->user())
                ->performedOn($user)
                ->withProperties(['action' => 'block'])
                ->event('user.block')
                ->log('User blocked');
        });
        
        return (new UserResource($user->fresh('roles')))
            ->additional(['message' => 'Felhasználó tiltva']);
    }
    
    /**
     * Felhasználó engedélyezése (is_blocked = false)
     */
    public function userUnblock(Request $request)
    {
        if (!$user->is_blocked) {
            return response()->json(['message' => 'A felhasználó nincs tiltva.'], Response::HTTP_CONFLICT);
        }
        
        DB::transaction(function() use ($request, $user) {
            $user->update(['is_blocked' => false]);
            
            activity()
                ->causedBy($request->user())
                ->performedOn($user)
                ->withProperties(['action' => 'unblock'])
                ->event('user.unblock')
                ->log('User unblocked');
        });
        
        return (new UserResource($user->fresh('roles')))
            ->additional(['message' => 'Felhasználó engedélyezve.']);
    }
}
