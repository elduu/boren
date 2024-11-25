<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Retrieve authenticated user and check role
        $AuthUser = auth()->user();
        $user = User::find($AuthUser->id);

        if (!$user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string',
            'email' => 'nullable|email|unique:users',
            'phone' => 'nullable|string|unique:users',
            'password' => 'required|string|confirmed',
            'role' => 'required|in:admin,user',
            'status' => 'nullable|in:active,inactive,suspended',
        ]);

        // Create User
        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'status' => $request->status ?? 'active',
        ]);

        // Assign Role
        $newUser->assignRole($request->role);

        $token = JWTAuth::fromUser($newUser);

        return response()->json(['user' => $newUser, 'token' => $token], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        // Retrieve authenticated user and check role
        $AuthUser = auth()->user();
        $user = User::find($AuthUser->id);

        if (!$user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $userToUpdate = User::findOrFail($id);
        $userToUpdate->status = $request->status;
        $userToUpdate->save();

        return response()->json(['message' => 'User status updated successfully', 'user' => $userToUpdate]);
    }

    public function filterByPhone(Request $request)
    {

        $AuthUser = auth()->user();
        $user = User::find($AuthUser->id);

        if (!$user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $request->validate([
            'phone' => 'required|string',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json(['user' => $user]);
    }

    public function login(Request $request)
{
    $request->validate([
        'email' => 'nullable|email',
        'phone' => 'nullable|string',
        'password' => 'required|string',
    ]);

    $credentials = $request->only('password');

    // Determine if login is by email or phone
    if ($request->has('email')) {
        $user = User::where('email', $request->email)->first();
    } elseif ($request->has('phone')) {
        $user = User::where('phone', $request->phone)->first();
    }

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    $token = JWTAuth::fromUser($user);

    return response()->json(['token' => $token]);
}
public function logout()
{
    auth()->logout();
    return response()->json(['message' => 'Successfully logged out']);
}


}
