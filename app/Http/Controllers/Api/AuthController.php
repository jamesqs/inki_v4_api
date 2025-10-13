<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Revoke previous tokens if needed
        // $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function user(Request $request): \Illuminate\Http\JsonResponse
    {
        return response()->json($request->user());
    }

    // Add these methods to AuthController
    public function allUsers(Request $request)
    {
        $query = User::query();

        // Filter by role if specified
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Filter by company if specified
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        return $query->with('company')->paginate(15);
    }

    public function updateUserRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|in:user,company-user,admin',
            'company_id' => 'required_if:role,company-user|nullable|exists:companies,id',
        ]);

        // Validate that company users must have a company
        if ($request->role === 'company-user' && empty($request->company_id)) {
            return response()->json([
                'message' => 'Company users must be associated with a company'
            ], 422);
        }

        $user->update([
            'role' => $request->role,
            'company_id' => $request->role === 'company-user' ? $request->company_id : null,
        ]);

        return response()->json($user);
    }

}
