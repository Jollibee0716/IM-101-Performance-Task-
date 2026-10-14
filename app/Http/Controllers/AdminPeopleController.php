<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminPeopleController extends Controller
{
    private function adminOnly(Request $r): void
    {
        abort_unless($r->user()->role === 'admin', 403, 'Admins only.');
    }

    // GET /api/admin/people  -> { users: [...], coaches: [...] }
    public function index(Request $r)
    {
        $this->adminOnly($r);

        $all = User::where('role', '!=', 'admin')->orderBy('name')->get();

        $users = $all->where('role', '!=', 'coach')->map(fn ($u) => [
            'id'     => $u->id,
            'name'   => $u->name,
            'email'  => $u->email,
            'joined' => optional($u->created_at)->toDateString() ?? now()->toDateString(),
            // members: status "active" = active, anything else (pending, rejected) = inactive
            'active' => strtolower((string) $u->status) === 'active',
        ])->values();

        $coaches = $all->where('role', 'coach')->map(function ($u) {
            // database -> dashboard: the 'active' status means an approved coach
            $approval = match (strtolower((string) $u->status)) {
                'active'   => 'approved',
                'rejected' => 'rejected',
                default    => 'pending',
            };
            return [
                'id'        => $u->id,
                'name'      => $u->name,
                'specialty' => $u->specialty ?? 'General fitness',
                'clients'   => 0, // no clients table yet
                'approval'  => $approval,
                'active'    => $approval === 'approved',
                'date'      => optional($u->updated_at)->toDateString() ?? now()->toDateString(),
            ];
        })->values();

        return ['users' => $users, 'coaches' => $coaches];
    }

    // PATCH /api/admin/coaches/{user}/approval  { status: approved|rejected|pending }
    public function approval(Request $r, User $user)
    {
        $this->adminOnly($r);
        abort_unless($user->role === 'coach', 404, 'Not a coach.');

        $data = $r->validate(['status' => 'required|in:approved,rejected,pending']);

        // dashboard -> database: 'approved' is saved as 'active'
        $user->status = $data['status'] === 'approved' ? 'active' : $data['status'];
        $user->save();

        return ['ok' => true];
    }

    // DELETE /api/admin/users/{user}
    public function destroy(Request $r, User $user)
    {
        $this->adminOnly($r);
        abort_if($user->id === $r->user()->id, 422, 'You cannot delete your own account.');
        abort_if($user->role === 'admin', 422, 'Admin accounts cannot be deleted here.');

        $user->delete();

        return ['ok' => true];
    }
}