<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
  public function index()
  {
    return response()->json(User::all(), 200);
  }

  public function show(User $user)
  {
    return response()->json($user, 200);
  }

  public function register(Request $request)
  {
    $data = $request->validate([
      'name' => 'required|string',
      'email' => 'required|unique:users|email',
      'role' => 'required|in:teacher,student',
      'password' => 'required|string|min:8',
    ]);

    $data['password'] = Hash::make($data['password']);
    $user = User::create($data);

    return response()->json(
      ['message' => 'User registered successfully', 'user' => $user],
      201,
    );
  }

  public function update(User $user, Request $request)
  {
    $data = $request->validate([
      'name' => 'required|string',
      'email' => [
        'required',
        'email',
        Rule::unique('users')->ignore($user->id),
      ],
      'password' => 'nullable|string|min:8',
    ]);

    if (!empty($data['password'])) {
      $data['password'] = Hash::make($data['password']);
    } else {
      unset($data['password']);
    }

    $user->update($data);
    return response()->json(
      ['message' => 'User updated successfully', 'user' => $user],
      200,
    );
  }

  public function destroy(User $user)
  {
    $user->delete();
    return response()->json(['message' => 'User deleted successfully'], 200);
  }
}
