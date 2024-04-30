<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\QueryException;
use Carbon\Carbon;

class UserController extends Controller
{
    public function index()
    {
        return User::all();
    }

    public function store(Request $request)
    {
        return User::create($request->all());
    }

    public function show(User $user)
    {
        return $user;
    }

    public function update(Request $request, User $user)
    {
        $user->update($request->all());
        return $user;
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }
    
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
    
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;
    
            try {
                // Save the token in the remember_token column
                // Save the FcmToken to fcm_token column
                $user->update(
                    [
                        'remember_token' => $token,
                        'fcm_token' => $request->input('fcm_token')
                    ]
                );
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to save token'], 500);
            }
    
            return response()->json([
                'token' => $token,
                'user' => $user,
            ]);
        }
    
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }
    
    public function signup(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6',
            'fcm_token' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
            'fcm_token' => $validatedData['fcm_token'],
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }
    
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->update(['fcm_token' => null]);
            $user->tokens()->delete();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            // Validate the request data
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|unique:users,email,' . $user->id,
                'profile_picture' => 'sometimes|nullable|image|max:2048', // Max 2MB
            ]);

            // Update the user's profile
            if ($request->has('name')) {
                $user->name = $request->name;
            }

            if ($request->has('email')) {
                $user->email = $request->email;
            }

            if ($request->hasFile('profile_picture')) {
                $profilePicturePath = $request->file('profile_picture')->store('profile_pictures', 'public');
                $user->profile_picture = $profilePicturePath;
            }
            // Save the user
            $user->save();

            return response()->json(['message' => 'Profile updated successfully', 'user' => $user]);
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Error updating user profile: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update profile', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateUserResources(Request $request)
    {
        try {
            // Find the user by ID
            $userId = $request->id;
            $user = User::findOrFail($userId);

            // Validate the request data
            $request->validate([
                'coin' => 'sometimes|integer',
                'gem' => 'sometimes|integer',
            ]);

            // Add or subtract coins //Send + - values
            if ($request->has('coin')) {
                $user->coin += $request->coin;
            }

            // Add or subtract gems //Send + - values
            if ($request->has('gem')) {
                $user->gem += $request->gem;
            }

            // Save the user
            $user->save();

            return response()->json(['message' => 'Updated successfully', 'user' => $user]);
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Error updating user profile: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update profile', 'error' => $e->getMessage()], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
    
        try {
            $user = User::where('email', $request->email)->first();
    
            if (!$user) {
                return response()->json(['message' => 'Email not found'], 404);
            }
    
            $tokenEntry = DB::table('password_reset_tokens')
                ->where('email', $user->email)
                ->orderBy('created_at', 'desc')
                ->first();
    
            if ($tokenEntry && Carbon::parse($tokenEntry->created_at)->addMinute()->isFuture()) {
                return response()->json(['message' => 'Password reset email already sent. Please wait before requesting another.'], 400);
            }
    
            $token = \Illuminate\Support\Str::random(6);
    
            if ($tokenEntry) {
                // Update existing token
                DB::table('password_reset_tokens')
                    ->where('email', $user->email)
                    ->update([
                        'token' => $token,
                        'created_at' => now(),
                    ]);
            } else {
                // Insert new token
                DB::table('password_reset_tokens')->insert([
                    'email' => $user->email,
                    'token' => $token,
                    'created_at' => now(),
                ]);
            }
            // Send email with password reset link
            Mail::to($user->email)->send(new ResetPasswordMail($user, $token));
    
            return response()->json([
                'message' => 'Password reset link sent to your email',
                'token' => $token,
            ]);
        } catch (QueryException $e) {
            return response()->json(['message' => 'Failed to send password reset email'], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:6',
        ]);

        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$passwordReset) {
            return response()->json(['message' => 'Invalid token'], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email not found'], 404);
        }

        $user->password = bcrypt($request->password);
        $user->save();

        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return response()->json(['message' => 'Password reset successfully']);
    }

}
