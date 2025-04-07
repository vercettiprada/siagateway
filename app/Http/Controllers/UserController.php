<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponser;
use App\Models\User;
use App\Services\User1Service;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    use ApiResponser;

    protected $user1Service;

    public function __construct(User1Service $user1Service)
    {
        $this->user1Service = $user1Service;
    }

    /**
     * Login and get JWT token
     */
    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);

        if (!$token = Auth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated user
     */
    public function me()
    {
        return response()->json(Auth::user());
    }

    /**
     * Logout user (Invalidate the token)
     */
    public function logout()
    {
        Auth::logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token
     */
    public function refresh()
    {
        return $this->respondWithToken(Auth::refresh());
    }

    /**
     * Format response with JWT token
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60 // default: 1 hour
        ]);
    }

    /**
     * Get all users (protected route)
     */
    public function index()
    {
        return $this->successResponse($this->user1Service->obtainUsers1());
    }

    /**
     * Create a new user
     */
    public function add(Request $request)
    {
        return $this->successResponse($this->user1Service->createUser1($request->all(), Response::HTTP_CREATED));
    }

    /**
     * Get user by ID
     */
    public function show($id)
    {
        return $this->successResponse($this->user1Service->getUser1($id));
    }

    /**
     * Delete user
     */
    public function delete($id)
    {
        return $this->successResponse($this->user1Service->deleteUser1($id));
    }
}
