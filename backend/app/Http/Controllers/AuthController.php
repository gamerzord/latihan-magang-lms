<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
  public function login(Request $request)
  {
    $credentials = $request->validate([
      'email' => 'required|email',
      'password' => 'required',
    ]);
    if (!Auth::attempt($credentials)) {
      return response()->json(['message' => 'Invalid credentials'], 401);
    }
    $user = Auth::user();
    $token = $user->createToken('api_token')->plainTextToken;

    return response()
      ->json(['user' => $user, 'token' => $token], 200)
      ->cookie(
        'auth-token',
        $token,
        60 * 24 * 7,
        '/',
        env('SESSION_DOMAIN'),
        true,
        true,
        false,
        'none',
      );
  }

  public function adminLogin(Request $request)
  {
    $credentials = $request->validate([
      'email' => 'required|email',
      'password' => 'required',
    ]);

    if (!Auth::attempt($credentials)) {
      return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $user = Auth::user();

    if ($user->role !== 'admin') {
      Auth::logout();
      return response()->json(
        ['message' => 'Access denied. Admins only.'],
        403,
      );
    }

    $token = $user->createToken('admin_api_token', ['admin'])->plainTextToken;

    return response()
      ->json(
        [
          'user' => $user,
          'token' => $token,
          'message' => 'Admin login successful',
        ],
        200,
      )
      ->cookie(
        'admin-auth-token',
        $token,
        60 * 24 * 7,
        '/',
        env('SESSION_DOMAIN'),
        true,
        true,
        false,
        'none',
      );
  }

  public function logout(Request $request)
  {
    $request->user()->currentAccessToken()->delete();
    return response()
      ->json(['message' => 'Logged out successfully'], 200)
      ->cookie(
        'auth-token',
        '',
        -1,
        '/',
        env('SESSION_DOMAIN'),
        true,
        true,
        false,
        'none',
      );
  }
}