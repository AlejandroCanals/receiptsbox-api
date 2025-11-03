<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;

class AuthController extends Controller
{
    //
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
        ]);

        User::create($data);
        return response()->json(['message' => 'registered'], 201);
        
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials->input('email'))->first();


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
}
