<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'user', // Explicitly set role for new registrations
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
        $user = $request->user()->load('profilePicture');

        $userData = $user->toArray();

        // Add profile picture URL if exists
        if ($user->profilePicture) {
            $userData['profile_picture'] = [
                'id' => $user->profilePicture->id,
                'url' => $user->profilePicture->url,
                'name' => $user->profilePicture->name,
            ];
        } else {
            $userData['profile_picture'] = null;
        }

        return response()->json($userData);
    }

    /**
     * Upload or update profile picture
     */
    public function uploadProfilePicture(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'photo' => 'required|file|image|mimes:jpeg,png,jpg,webp|max:10240', // 10MB max
        ]);

        $user = $request->user();

        try {
            // Use media controller to upload
            $mediaController = new \App\Modules\Media\Http\Controllers\MediaController();

            // Create a new request for media upload
            $mediaRequest = new Request();
            $mediaRequest->files->set('file', $request->file('photo'));
            $mediaRequest->merge([
                'collection' => 'user_avatars',
                'mediable_type' => User::class,
                'mediable_id' => $user->id
            ]);
            $mediaRequest->setUserResolver($request->getUserResolver());

            $response = $mediaController->upload($mediaRequest);
            $responseData = json_decode($response->getContent(), true);

            if (!$responseData['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload profile picture',
                ], 500);
            }

            // Delete old profile picture if exists
            if ($user->profile_picture_id) {
                $oldPicture = \App\Modules\Media\Models\Media::find($user->profile_picture_id);
                if ($oldPicture) {
                    $oldPicture->forceDelete();
                }
            }

            // Update user with new profile picture
            $user->update([
                'profile_picture_id' => $responseData['data']['id']
            ]);

            $user->load('profilePicture');

            return response()->json([
                'success' => true,
                'message' => 'Profile picture uploaded successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_picture' => [
                        'id' => $user->profilePicture->id,
                        'url' => $user->profilePicture->url,
                        'name' => $user->profilePicture->name,
                    ]
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Profile picture upload error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error uploading profile picture',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Delete profile picture
     */
    public function deleteProfilePicture(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        if (!$user->profile_picture_id) {
            return response()->json([
                'success' => false,
                'message' => 'No profile picture to delete',
            ], 404);
        }

        try {
            $profilePicture = \App\Modules\Media\Models\Media::find($user->profile_picture_id);

            if ($profilePicture) {
                $profilePicture->forceDelete();
            }

            $user->update(['profile_picture_id' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Profile picture deleted successfully',
            ]);

        } catch (\Exception $e) {
            \Log::error('Profile picture deletion error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error deleting profile picture',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Update user profile settings
     */
    public function updateProfile(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'first_name' => 'sometimes|nullable|string|max:255',
            'last_name' => 'sometimes|nullable|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'show_phone' => 'sometimes|boolean',
        ]);

        $user->update($validated);

        // Reload user with profile picture
        $user->load('profilePicture');
        $userData = $user->toArray();

        // Format profile picture
        if ($user->profilePicture) {
            $userData['profile_picture'] = [
                'id' => $user->profilePicture->id,
                'url' => $user->profilePicture->url,
                'name' => $user->profilePicture->name,
            ];
        } else {
            $userData['profile_picture'] = null;
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $userData,
        ]);
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

    /**
     * Handle Google OAuth authentication
     */
    public function googleAuth(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'access_token' => 'required|string',
        ]);

        try {
            // Verify the Google token and get user info
            $response = Http::get('https://www.googleapis.com/oauth2/v3/userinfo', [
                'access_token' => $request->access_token,
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'message' => 'Invalid Google token'
                ], 401);
            }

            $googleUser = $response->json();

            // Find or create user
            $user = User::where('email', $googleUser['email'])->first();

            if (!$user) {
                // Create new user
                $user = User::create([
                    'name' => $googleUser['name'] ?? '',
                    'first_name' => $googleUser['given_name'] ?? '',
                    'last_name' => $googleUser['family_name'] ?? '',
                    'email' => $googleUser['email'],
                    'password' => Hash::make(Str::random(32)), // Random password for OAuth users
                    'email_verified_at' => now(), // Google emails are verified
                    'role' => 'user', // Explicitly set role for OAuth users
                ]);
            }

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Google authentication failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Facebook OAuth authentication
     */
    public function facebookAuth(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'access_token' => 'required|string',
        ]);

        try {
            // Verify the Facebook token and get user info
            $response = Http::get('https://graph.facebook.com/me', [
                'fields' => 'id,name,email,first_name,last_name',
                'access_token' => $request->access_token,
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'message' => 'Invalid Facebook token'
                ], 401);
            }

            $facebookUser = $response->json();

            // Facebook doesn't always provide email
            if (empty($facebookUser['email'])) {
                return response()->json([
                    'message' => 'Email is required. Please allow email access in Facebook permissions.'
                ], 422);
            }

            // Find or create user
            $user = User::where('email', $facebookUser['email'])->first();

            if (!$user) {
                // Create new user
                $user = User::create([
                    'name' => $facebookUser['name'] ?? '',
                    'first_name' => $facebookUser['first_name'] ?? '',
                    'last_name' => $facebookUser['last_name'] ?? '',
                    'email' => $facebookUser['email'],
                    'password' => Hash::make(Str::random(32)), // Random password for OAuth users
                    'email_verified_at' => now(), // Facebook emails are verified
                    'role' => 'user', // Explicitly set role for OAuth users
                ]);
            }

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Facebook authentication failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
