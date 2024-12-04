<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use Spatie\Permission\Middlewares\RoleMiddleware;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
        $validatedData = $request->validate([
            'name' => 'required|string',
            'email' => 'nullable|email|unique:users',
            'phone' => 'nullable|string|unique:users',
            'password' => 'required|string|confirmed',
            'role' => 'required|in:admin,user',
            'status' => 'nullable|in:active,inactive,suspended',
        ], [
            'name.required' => 'The name field is required.',
            'name.string' => 'The name must be a valid string.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'The email address is already taken.',
            'phone.string' => 'Please provide a valid phone number.',
            'phone.unique' => 'The phone number is already taken.',
            'password.required' => 'Password is required to register.',
            'password.string' => 'Password must be a valid string.',
            'password.confirmed' => 'The password confirmation does not match.',
            'role.required' => 'Please assign a role to the user.',
            'role.in' => 'Role must be either admin or user.',
            'status.in' => 'Status must be one of: active, inactive, suspended.',
        ]);
    
        // Create User
        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'status' => $request->status ?? 'active',
        ]);
        if (!$role = Role::where('name', $request->role)->first()) {
            $role = Role::create(['name' => $request->role]);
        }

        // Assign Role
        $newUser->assignRole($role);

    
        // Assign Role
        $newUser->assignRole($request->role);
    
        // Generate JWT token for the new user
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
    $validatedData = $request->validate([
        'email' => 'nullable|email',
        'phone' => 'nullable|string',
        'password' => 'required|string',
    ], [
        // Custom error messages for validation rules
        'email.email' => 'Please provide a valid email address.',
        'phone.string' => 'Please provide a valid phone number.',
        'password.required' => 'Password is required to login.',
        'password.string' => 'Password should be a string.',
    ]);

    // Prepare the credentials based on the provided inputs
    $credentials = $request->only('password');

    // Determine if login is by email or phone
    if ($request->has('email')) {
        // Check if the user exists with the provided email
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'No user found with this email address.'], 404);
        }
    } elseif ($request->has('phone')) {
        // Check if the user exists with the provided phone number
        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json(['error' => 'No user found with this phone number.'], 404);
        }
    } else {
        return response()->json(['error' => 'Please provide either an email or phone number to login.'], 400);
    }

    // If the password doesn't match
    if (!Hash::check($request->password, $user->password)) {
        return response()->json(['error' => 'Invalid password. Please try again.'], 401);
    }

    // Generate a JWT token for the authenticated user
    $token = JWTAuth::fromUser($user);

    return response()->json(['token' => $token], 200);
}
public function logout(Request $request)
{
    Auth::guard('api')->logout();

  

    return response()->json(['message' => 'Successfully logged out'], 200);
}
public function getUserInfo()
{
    // Retrieve the authenticated user based on the token
    $user = Auth::user();

    if ($user) {
        // Return the user's information as a JSON response
        return response()->json([
            'status' => 'success',
            'data' => $user
        ], 200);
    } else {
        // Return an error response if the user is not found
        return response()->json([
            'status' => 'error',
            'message' => 'User not authenticated'
        ], 401);
    }
}


public function listAllUsers()
{
    
    $AuthUser = auth()->user();
    $user = User::find($AuthUser->id);

    // Ensure the authenticated user exists and has the 'admin' role
    if (!$user || !$user->hasRole('admin')) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    // Retrieve all users and include their roles
    $users = User::with('roles') // Eager load the roles relationship
                 ->select('id', 'name', 'email', 'created_at', 'updated_at','password') // Limit fields returned
                 ->get();

    // Structure the response to include roles information
    $users = $users->map(function ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name'), // Get the role names as a collection
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'password'=>$user->password,
        ];
    });

    return response()->json([
        'status' => 'success',
        'users' => $users,
    ], 200);
}

public function update(Request $request, $id)
{
    DB::beginTransaction();

    try {
        // Find the user record by ID
        $user = User::findOrFail($id);

        // Validate the request data
        $validatedData = $request->validate([
            'name' => 'string|max:255',
            'email' => 'string|email|max:255|unique:users,email,' . $id,
            'phone' => 'string|max:20',
            'status.in' => 'Status must be one of: active, inactive, suspended.',
            // Add any other fields you want to allow for update here
        ]);

        // Update only the fields provided in the request
        $user->fill($validatedData);

        // Save the changes to the database
        $user->save();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => $user
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();

        // Log error for debugging
        Log::error('Error updating user: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'An error occurred while updating the user.',
        ], 500);
    }
}
public function refreshToken()
{
    try {
        // Refresh the JWT token
        $newToken = JWTAuth::parseToken()->refresh();

        return response()->json([
            'success' => true,
            'token' => $newToken,
        ], 200);
    } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
        return response()->json([
            'success' => false,
            'message' => 'The token has expired and cannot be refreshed.',
        ], 401);
    } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Unable to refresh the token. Please log in again.',
        ], 500);
    }
}

}