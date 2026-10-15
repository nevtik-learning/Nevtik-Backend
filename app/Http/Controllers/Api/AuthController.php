<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'email atau password salah',
            ], 401);
        }

        $user->tokens()->delete(); 
        $token = $user->createToken('api')->plainTextToken;

        return (new UserResource($user->load('divisi:id,nama')))
            ->additional(['status' => true, 'message' => 'login berhasil', 'token' => $token]);
    }

    public function profile(Request $request)
    {
        return (new UserResource($request->user()->load('divisi:id,nama')))
            ->additional(['status' => true]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['status' => true, 'message' => 'berhasil logout']);
    }
}