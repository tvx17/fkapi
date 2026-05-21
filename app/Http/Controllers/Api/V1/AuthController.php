<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Ungültige Anmeldedaten'], 401);
        }

        $user = Auth::user();

        // Alte Tokens löschen
        $user->tokens()->delete();

        // Access Token (15 Min.)
        $accessToken = $user->createToken('access', ['*'], now()->addMinutes(15))->plainTextToken;

        // Refresh Token generieren
        $refreshToken = bin2hex(random_bytes(64));
        RefreshToken::create([
            'user_id'    => $user->id,
            'token'      => hash('sha256', $refreshToken),
            'expires_at' => now()->addDays(30),
        ]);

        return response()->json(['access_token' => $accessToken])
            ->cookie('refresh_token', $refreshToken, 30 * 24 * 60, '/', null, true, true, false, 'Strict');
        //                                                             ^^^^  ^^^^
        //                                                           secure  httpOnly
    }

    public function refresh(Request $request): JsonResponse
    {
        $rawToken = $request->cookie('refresh_token');
        if (!$rawToken) {
            return response()->json(['message' => 'Kein Refresh Token'], 401);
        }

        $hashed = hash('sha256', $rawToken);
        $token  = RefreshToken::where('token', $hashed)
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$token) {
            return response()->json(['message' => 'Refresh Token ungültig'], 401);
        }

        // Token Rotation: altes Token ungültig machen
        $token->update(['revoked' => true]);

        $user = $token->user;
        $user->tokens()->delete();

        // Neue Token-Pair ausstellen
        $accessToken     = $user->createToken('access', ['*'], now()->addMinutes(15))->plainTextToken;
        $newRefreshToken = bin2hex(random_bytes(64));

        RefreshToken::create([
            'user_id'    => $user->id,
            'token'      => hash('sha256', $newRefreshToken),
            'expires_at' => now()->addDays(30),
        ]);

        return response()->json(['access_token' => $accessToken])
            ->cookie('refresh_token', $newRefreshToken, 30 * 24 * 60, '/', null, true, true, false, 'Strict');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        $raw = $request->cookie('refresh_token');
        if ($raw) {
            RefreshToken::where('token', hash('sha256', $raw))->update(['revoked' => true]);
        }

        return response()->json(['message' => 'Erfolgreich abgemeldet'])
            ->cookie(\Cookie::forget('refresh_token'));
    }
}
