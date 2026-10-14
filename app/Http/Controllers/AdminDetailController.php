<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class AdminDetailController extends Controller
{
    public function show($id)
    {
        abort_unless(auth()->check() && auth()->user()->role === 'admin', 403);

        $user = DB::table('users')
            ->where('id', $id)
            ->select('id', 'name', 'email', 'role', 'status', 'gender',
                     'first_name', 'last_name', 'contact_number',
                     'date_of_birth', 'address', 'created_at')
            ->first();

        abort_if(!$user, 404);

        $table = $user->role === 'coach' ? 'trainers' : 'members';
        $profile = DB::table($table)->where('user_id', $id)->first();

        return response()->json([
            'user'    => $user,
            'profile' => $profile,
        ]);
    }
}