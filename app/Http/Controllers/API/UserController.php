<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function updateProfile(Request $request)
    {
        // Validate input fields
        $validateData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:15', Rule::unique('users')->ignore(Auth::id())],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore(Auth::id())],
        ]);

        // Get current the authenticated user
        $user = Auth::user();

        // Update user
        $user->update($validateData);

        return ResponseFormatter::success(
            $user,
            'Profile Updated'
        );
    }

    // Fetch the authenticated user's data
    // automatically check request user login or not using sanctum
    public function fetch(Request $request)
    {
        return ResponseFormatter::success($request->user(), 'Data profile user berhasil diambil');
    }
}
