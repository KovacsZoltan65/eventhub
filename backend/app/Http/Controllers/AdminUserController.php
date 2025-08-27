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
        // Üres stringek -> null (így nem bukik a validáció és nem szűr véletlenül)
        $data = $request->all();
        array_walk($data, function (&$v) {
            if (is_string($v)) {
                $v = trim($v);
                if ($v === '') $v = null;
            }
        });
        // frontend alias: perPage -> per_page
        if (isset($data['perPage']) && !isset($data['per_page'])) {
            $data['per_page'] = $data['perPage'];
        }
        $request->replace($data);

        $validated = $request->validate([
            'search'    => 'nullable|string|max:255',
            'role'      => 'nullable|string|max:100',
            'blocked'   => 'nullable|in:0,1', // SZŰRŐ (0|1)
            'field'     => 'nullable|in:name,email,created_at,is_blocked', // RENDEZÉS
            'order'     => 'nullable|in:asc,desc',
            'per_page'  => 'nullable|integer|min:1|max:100',
            'page'      => 'nullable|integer|min:1',
        ]);

        $q = User::query()->with('roles:id,name');

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

        // <<< Itt volt a hiba: 'blocked' paramot kell nézni, és meg kell különböztetni a '0' értéket is
        if ($request->has('blocked')) {
            $q->where('is_blocked', (int) $request->input('blocked')); // 0 vagy 1
            // alternatíva: $q->where('is_blocked', $request->boolean('blocked'));
        }

        $field = $validated['field'] ?? 'created_at';
        $order = $validated['order'] ?? 'desc';

        // Validáció már védi a mezőt, mehet az orderBy
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
