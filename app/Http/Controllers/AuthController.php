<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use Spatie\Permission\Middlewares\RoleMiddleware;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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
         try {
            $validatedData = $request->validate([
                'name' => 'required|string',
                'email' => 'nullable|email|unique:users,email',
                'phone' => 'nullable|string|unique:users,phone',
                'password' => 'required|string|confirmed',
                'role' => 'required|in:admin,writer,read_only',
                'status' => 'nullable|in:active,inactive,suspended',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error occurred',
                'errors' => $e->errors(),
            ], 422);
        }
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
public function getUserInfo(Request $request)
{
    

        // Check if the authenticated user is an admin
        $currentUser = $request->user();
        if (!$currentUser || !$currentUser->hasRole('admin')) { // Adjust based on your RBAC implementation
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update users.',
            ], 403);
        }
// Retrieve the authenticated user from the token
    $user = $request->user();


    if ($user) {
        // Load the roles relationship to include role information
        $user->load('roles');

        // Assume the user has only one role
        $role = $user->roles->first() ? $user->roles->first()->name : 'No role assigned';

        // Structure the user's information excluding sensitive data
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
            'password'=>$user->password, // Include the user's specific role
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];

        // Return the user's information as a JSON response
        return response()->json([
            'status' => 'success',
            'data' => $userData,
        ], 200);
    } else {
        // Return an error response if the user is not authenticated
        return response()->json([
            'status' => 'error',
            'message' => 'User not authenticated.',
        ], 401);
    }
}

            
public function listAllUsers()
{
    $AuthUser = auth()->user();
    $user = User::find($AuthUser->id);

    if (!$user->hasRole('admin')) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    return response()->json(User::all(), 200);
}

public function update(Request $request, $id)
{
    DB::beginTransaction();
    
    
        // k if the authenticated user is an admin
        $currentUser = $request->user();
        if (!$currentUser || !$currentUser->hasRole('admin')) { // Adjust based on your RBAC implementation
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update users.',
            ], 403);
        }

    try {
        // Find the user record by ID
        $user = User::findOrFail($id);

        // Validate the request data
        $validatedData = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive,suspended', // Fixed syntax for `in` rule
            'password' => 'nullable|string|confirmed', // Password confirmation
        ]);

        // Handle password separately to hash before saving
        if (!empty($validatedData['password'])) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        }

        // Update only the fields provided in the request
        $user->fill($validatedData);

        // Save the changes to the database
        $user->save();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => $user->only(['id', 'name', 'email', 'phone', 'status', 'created_at', 'updated_at']), // Exclude sensitive fields
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
public function updateAdminCredentials(Request $request)
{
    try {
        // Step 1: Validate the incoming request data
        $validatedData = $request->validate([
            'email' => 'required|email|unique:users,email,' . auth()->id(),
            'password' => 'nullable|min:8|confirmed',
        ]);

        // Step 2: Get the authenticated admin user
        $AuthUser = auth()->user();
    $user = User::find($AuthUser->id);

    if (!$user->hasRole('admin')) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

        // Step 3: Update the admin's email and password
        $user->email = $validatedData['email'];

        if (!empty($validatedData['password'])) {
            $user->password = Hash::make($validatedData['password']);
        }

        $user->save();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Credentials updated successfully.',
        ], 200);
    } catch (\Exception $e) {
        // Log the error with more details
        Log::error('Error updating admin credentials:', [
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
            'stack_trace' => $e->getTraceAsString(),
        ]);
        return response()->json([
            'success' => false,
            'message' => 'An unexpected error occurred while updating credentials.',
        ], 500);
    }
}
}