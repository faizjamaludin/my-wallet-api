<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function googleLogin(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $request->token,
        ]);

        if ($response->failed() || empty($response->json('sub'))) {
            return response()->json(['message' => 'Invalid Google token.'], 401);
        }

        $payload = $response->json();
        $googleId = $payload['sub'];
        $email    = $payload['email'] ?? null;
        $name     = $payload['name'] ?? ($payload['given_name'] ?? 'Google User');

        if (! $email) {
            return response()->json(['message' => 'Google account has no email.'], 422);
        }

        $user = User::where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            if (! $user->google_id) {
                $user->update(['google_id' => $googleId]);
            }
        } else {
            $user = User::create([
                'name'      => $name,
                'email'     => $email,
                'google_id' => $googleId,
                'password'  => Hash::make(Str::random(32)),
            ]);
        }

        $token = $user->createToken('mywallet-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function login(Request $request)
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

        $token = $user->createToken('mywallet-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('mywallet-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }
}
