<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique($user->getTable(), 'email')->ignore($user->getKey())],
            'phone'    => ['nullable', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:255'],
            'bio'      => ['nullable', 'string', 'max:1000'],
        ]);

        $user->update($data);

        return response()->json(['ok' => true]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', Password::min(8), 'confirmed'],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($request->input('password')),
        ])->save();

        return response()->json(['ok' => true]);
    }
}