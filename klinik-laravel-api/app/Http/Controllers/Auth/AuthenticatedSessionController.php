<?php

namespace App\Http\Controllers\Auth;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use OpenApi\Annotations as OA;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\Auth\LoginRequest;
use Laravel\Socialite\Facades\Socialite;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */

    /**
     * @OA\Post(
     *     path="/api/v1/login",
     *     summary="Log in a user",
     *     description="This endpoint authenticates a user and returns an authentication token upon successful login.",
     *     operationId="loginUser",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com", description="User's email address"),
     *             @OA\Property(property="password", type="string", example="password123", description="User's password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJh..."),
     *             @OA\Property(property="status", type="string", example="Logged in successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="These credentials do not match our records.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while processing your request.")
     *         )
     *     )
     * )
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'status' => 'Logged in successfully'
        ]);
    }

    /**
     * Destroy an authenticated session.
     */


    /**
     * @OA\Post(
     *     path="/api/v1/logout",
     *     summary="Log out the user",
     *     description="Logs out the authenticated user, invalidates the session, and regenerates the CSRF token.",
     *     operationId="logoutUser",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=204,
     *         description="Logout successful, no content returned"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while processing your request.")
     *         )
     *     )
     * )
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }


    // public function handleProviderCallback(Request $request)
    // {
    //     try {
    //         $user = Socialite::driver('facebook')->user();

    //         $existingUser = User::where('facebook_id', $user->id)->first();

    //         if ($existingUser) {
    //             $token = auth()->login($existingUser); // Assuming Laravel Sanctum or similar
    //             return response()->json(['success' => true, 'token' => $token]);
    //         } else {
    //             $newUser = User::create([
    //                 'name' => $user->name,
    //                 'email' => $user->email,
    //                 'facebook_id' => $user->id,
    //             ]);
    //             auth()->login($newUser);
    //             $token = auth()->login($newUser);
    //             return response()->json(['success' => true, 'token' => $token]);
    //         }
    //     } catch (Exception $e) {
    //         return response()->json(['success' => false, 'message' => 'Failed to login using Facebook'], 400);
    //     }
    // }


    // public function authFacebook()
    // {
    //     return Socialite::driver('facebook')->redirect();
    // }

    // public function callBackSocialite()
    // {
    //     try {
    //         $user = Socialite::driver('facebook')->user();
    //         $findUser = User::where('facebook_id', $user->id)->first();
    //         if ($findUser) {
    //             Auth::login($findUser);
    //             return redirect()->route('home');
    //         } else {

    //             $existingUser = User::where('email', $user->email)->first();

    //             if ($existingUser) {
    //                 return response()->json('User already exists');
    //             }

    //             $newUser = User::updateOrCreate([
    //                 'name' => $user->name,
    //                 'email' => $user->email,
    //                 'facebook_id' => $user->id,
    //                 'password' => encrypt('ez25yepB'),
    //                 'email_verified_at' => Carbon::now(),
    //             ]);
    //             Auth::login($newUser);
    //             return response()->json('User Created');
    //         }
    //     } catch (\Throwable $th) {
    //         throw $th;
    //     }
    // }
}
