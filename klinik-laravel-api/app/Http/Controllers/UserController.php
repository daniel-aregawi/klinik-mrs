<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="api/v1/check-user-auth",
     *     summary="Check User Authentication",
     *     tags={"Authentication"},
     *     @OA\Response(
     *         response=200,
     *         description="User is authenticated",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User is not authenticated",
     *     ),
     * )
     */
    public function checkAuth()
    {
        // Check if the user is authenticated
        return auth()->user()
            ? response()->json(true) // Return true if user is authenticated
            : response()->json(false); // Return false if user is not authenticated
    }




    /**
     * @OA\Get(
     *     path="/api/v1/get-user-data",
     *     summary="Retrieve Authenticated User Data",
     *     tags={"User"},
     *     @OA\Response(
     *         response=200,
     *         description="User data retrieved successfully",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User is not authenticated",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *     ),
     * )
     */
    public function getUserData()
    {
        // Get the authenticated user's ID
        $userId = auth()->user()->id;
        $cacheKey = "userData_{$userId}"; // Create a cache key for the user's data

        // Check if user data is cached
        if (Cache::has($cacheKey)) {
            // Retrieve the user data from cache
            $user = Cache::get($cacheKey);
        } else {
            // Retrieve the user data from the database and cache it
            $user = Cache::rememberForever($cacheKey, function () {
                return User::where('id', auth()->user()->id)->first();
            });
        }

        // Return the user data as JSON response
        return response()->json($user);
    }



    /**
     * @OA\Get(
     *     path="/api/v1/get-user-photo",
     *     summary="Get authenticated user's profile photo",
     *     description="Retrieves the authenticated user's profile photo URL.",
     *     operationId="getUserProfilePhoto",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profile photo retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="string", example="Profile photo retrieved successfully"),
     *             @OA\Property(property="photo_url", type="string", example="/storage/images/avatar.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="User not authenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Profile photo not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Profile photo not found")
     *         )
     *     )
     * )
     */
    public function getUserProfilePhoto()
    {
        // Retrieve the authenticated user
        $user = auth()->user();

        // Get the user's profile photo path
        $photo = $user->avatar;

        

        // Return the profile photo URL as JSON response
        return response()->json([
            'success' => 'Profile photo retrieved successfully',
            'photo_url' => $photo ? Storage::url($photo) : null
        ], 200);
    }



    /**
     * @OA\Get(
     *     path="/api/v1/is-user-admin",
     *     summary="Check if the authenticated user is an admin",
     *     description="Checks if the authenticated user has admin privileges by retrieving the status from cache or database.",
     *     operationId="isUserAdmin",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Admin status retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="isAdmin", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="User not authenticated")
     *         )
     *     )
     * )
     */
    public function isUserAdmin()
    {
        $user = auth()->user();
        $cacheKey = 'user_is_admin_' . $user->id;

        if (Cache::has($cacheKey)) {
            $isAdmin = Cache::get($cacheKey);
        } else {
            $isAdmin = $user->is_admin;

            if ($isAdmin) {
                Cache::rememberForever($cacheKey, function () use ($isAdmin) {
                    return $isAdmin;
                });
            } else {
                $isAdmin = false;
            }
        }

        return response()->json($isAdmin);
    }



    /**
     * @OA\Post(
     *     path="/api/v1/change-user-role",
     *     summary="Change user role",
     *     description="Update the role of a specified user.",
     *     operationId="changeUserRole",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         required=true,
     *         description="The new role to assign to the user.",
     *         @OA\Schema(type="string", enum={"admin", "doctor", "nurse", "pharmacist", "laboratorist", "patient"})
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         required=true,
     *         description="The ID of the user to update.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User role updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="User role updated successfully"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="An error occurred while updating the user role"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
     */

    public function changeUserRole(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'role' => 'required|string|in:admin,doctor,nurse,pharmacist,laboratorist,patient',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $role = $validatedData['role']; // The new role to assign
        $userId = $validatedData['user_id']; // The ID of the user to update

        try {
            // Find the user by ID or throw a ModelNotFoundException if not found
            $user = User::findOrFail($userId);

            // Clear relevant caches
            Cache::forget('user_is_admin_' . $userId);
            $cacheKey = "userData_{$userId}";
            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
            }

            // Update user roles based on the new role
            $user->update([
                'is_admin' => $role === 'admin' ? 1 : 0,
                'is_doctor' => $role === 'doctor' ? 1 : 0,
                'is_nurse' => $role === 'nurse' ? 1 : 0,
                'is_pharmacist' => $role === 'pharmacist' ? 1 : 0,
                'is_laboratorist' => $role === 'laboratorist' ? 1 : 0,
            ]);

            // Clear general user data cache
            $cacheKey = 'userData';
            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
            }

            // Return a success response with updated user data
            return response()->json([
                'status' => 'success',
                'message' => 'User role updated successfully',
                'user' => $user,
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return an error response if user is not found
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        } catch (\Exception $e) {
            // Return a generic error response if an exception occurs
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating the user role',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/delete-user",
     *     summary="Delete user",
     *     description="Remove a user from the system.",
     *     operationId="deleteUser",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         required=true,
     *         description="The ID of the user to delete.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="User deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the user"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
     */
    public function deleteUser(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $userId = $validatedData['user_id']; // The ID of the user to delete

        try {
            // Find the user by ID or throw a ModelNotFoundException if not found
            $user = User::findOrFail($userId);
            $user->delete(); // Delete the user

            // Clear relevant caches
            Cache::forget('count_users');
            $cacheKey = "userData_{$userId}";
            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
            }

            // Return a success response
            return response()->json([
                'status' => 'success',
                'message' => 'User deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return an error response if user is not found
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        } catch (\Exception $e) {
            // Return a generic error response if an exception occurs
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/v1/fetch-user-role",
     *     summary="Get the authenticated user's role",
     *     description="Fetches the role of the authenticated user (admin, doctor, nurse, laboratorist, pharmacist, or patient) based on their attributes.",
     *     operationId="getUserRole",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User role retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="role", type="string", example="admin")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error occurred while fetching the user role.")
     *         )
     *     )
     * )
     */
    public function getUserRole()
    {
        try {
            // Get the authenticated user
            $user = User::where('id', Auth::id())->first();

            // Return an error if the user is not found
            if (!$user) {
                return response()->json([
                    'error' => 'User not found',
                ], 200);
            }

            // Determine the user's role
            $role = $user->is_admin ? 'admin'
                : ($user->is_doctor ? 'doctor'
                    : ($user->is_nurse ? 'nurse'
                        : ($user->is_laboratorist ? 'laboratorist'
                            : ($user->is_pharmacist ? 'pharmacist' : 'patient'))));

            // Return the user's role as JSON response
            return response()->json([
                'data' => [
                    'role' => $role,
                ],
            ], 200);
        } catch (\Throwable $th) {
            // Log the error and return a generic error response
            Log::error('Error fetching user role: ', ['error' => $th->getMessage()]);

            return response()->json([
                'error' => 'An error occurred while fetching the user role.',
            ], 500);
        }
    }
}
