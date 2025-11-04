<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = validator($request->all(),[
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
        ])->validate();

        User::create($data);
        return response()->json(['message' => 'registered'], 201);
        
    }

    public function login(Request $request)
    {
        $credentials = validator($request->all(),[
            'email' => ['required', 'email'],
            'password' => ['required'],
        ])->validate();

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || !Hash::check($credentials['password'], $user->password))
        {
            return response()->json([
                'message' => 'invalid_credentials'
            ],401);
        }

        $token = $user->createToken('default')->plainTextToken;

        return response()->json([
            'token'=> $token,
            'token_type' => 'Bearer',
            'message' => 'ok'
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
