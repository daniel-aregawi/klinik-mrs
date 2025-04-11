<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use OpenApi\Annotations as OA;

class UsersController extends Controller
{


    /**
     * @OA\Get(
     *     path="/api/v1/fetch-users",
     *     summary="Get paginated list of users",
     *     description="Fetches a paginated list of users with optional search filtering. Users can be filtered by name or email.",
     *     operationId="getUsers",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of users per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         description="Search query to filter users by name or email",
     *         required=false,
     *         @OA\Schema(type="string", example="john")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of users retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                     @OA\Property(property="avatar", type="string", example="/storage/images/avatar.jpg"),
     *                     @OA\Property(property="is_admin", type="boolean", example=false),
     *                     @OA\Property(property="is_doctor", type="boolean", example=false),
     *                     @OA\Property(property="is_nurse", type="boolean", example=false),
     *                     @OA\Property(property="is_pharmacist", type="boolean", example=false),
     *                     @OA\Property(property="is_laboratorist", type="boolean", example=false),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000Z"),
     *                     @OA\Property(property="email_verified_at", type="string", format="date-time", example="2024-01-02T00:00:00.000Z")
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=10),
     *             @OA\Property(property="total", type="integer", example=100),
     *             @OA\Property(property="per_page", type="integer", example=15)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching users"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
     */
    public function getUsers(Request $request)
    {
        try {
            // Retrieve the number of items per page from query parameters or default to 15
            $perPage = $request->query('per_page', 15);

            // Retrieve the search query from query parameters
            $searchQuery = $request->query('search_query');

            // Fetch users with pagination and filtering based on search query
            $users = User::select('id', 'name', 'email', 'avatar', 'is_admin', 'is_doctor', 'is_nurse', 'is_pharmacist', 'is_laboratorist', 'created_at', 'email_verified_at')
                ->where('name', 'like', '%' . $searchQuery . '%')
                ->orWhere('email', 'like', '%' . $searchQuery . '%')
                ->paginate($perPage);

            // Return the paginated user data in JSON format
            return response()->json([
                'data' => $users->items(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
                'per_page' => $perPage
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching users: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching users',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-users",
     *     summary="Search and paginate users",
     *     description="Fetch a paginated list of users based on a search query. Users can be searched by name or email.",
     *     operationId="getSearchedUsers",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         description="Search query to filter users by name or email",
     *         required=false,
     *         @OA\Schema(type="string", example="john")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of searched users retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="users", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                     @OA\Property(property="avatar", type="string", example="/storage/images/avatar.jpg"),
     *                     @OA\Property(property="is_admin", type="boolean", example=false),
     *                     @OA\Property(property="is_doctor", type="boolean", example=false),
     *                     @OA\Property(property="is_nurse", type="boolean", example=false),
     *                     @OA\Property(property="is_pharmacist", type="boolean", example=false),
     *                     @OA\Property(property="is_laboratorist", type="boolean", example=false),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000Z"),
     *                     @OA\Property(property="email_verified_at", type="string", format="date-time", example="2024-01-02T00:00:00.000Z")
     *                 )
     *             ),
     *             @OA\Property(property="total", type="integer", example=10),
     *             @OA\Property(property="per_page", type="integer", example=15),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching searched users"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
     */
    public function getSearchedUsers(Request $request)
    {
        try {
            // Retrieve the search query from query parameters
            $searchQuery = $request->query('search_query');

            // Fetch users based on search query with pagination
            $users = User::where('name', 'like', '%' . $searchQuery . '%')
                ->orWhere('email', 'like', '%' . $searchQuery . '%')
                ->select('id', 'name', 'email', 'avatar', 'is_admin', 'is_doctor', 'is_nurse', 'is_pharmacist', 'is_laboratorist', 'created_at', 'email_verified_at')
                ->paginate();

            // Return the paginated user data in JSON format
            return response()->json([
                'users' => $users,
                'total' => $users->count(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage()
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching users: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching searched users',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-users-by-role",
     *     summary="Get users by role",
     *     description="Fetch a paginated list of users based on their role. Possible roles are admin, doctor, nurse, pharmacist, laboratorist, patient, or all.",
     *     operationId="getUsersByRoles",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Role of the user to filter by (e.g., admin, doctor, nurse, pharmacist, laboratorist, patient, all)",
     *         required=true,
     *         @OA\Schema(type="string", example="doctor")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="users", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                     @OA\Property(property="avatar", type="string", example="/storage/images/avatar.jpg"),
     *                     @OA\Property(property="is_admin", type="boolean", example=false),
     *                     @OA\Property(property="is_doctor", type="boolean", example=true),
     *                     @OA\Property(property="is_nurse", type="boolean", example=false),
     *                     @OA\Property(property="is_pharmacist", type="boolean", example=false),
     *                     @OA\Property(property="is_laboratorist", type="boolean", example=false),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000Z"),
     *                     @OA\Property(property="email_verified_at", type="string", format="date-time", example="2024-01-02T00:00:00.000Z")
     *                 )
     *             ),
     *             @OA\Property(property="total", type="integer", example=100),
     *             @OA\Property(property="per_page", type="integer", example=15),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=7)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching by role users"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
     */
    public function getUsersByRoles(Request $request)
    {
        try {
            // Retrieve the role from query parameters
            $role = $request->query('role');

            // Fetch users based on role with pagination
            if ($role === 'admin') {
                $users = User::where('is_admin', 1)->paginate();
            } elseif ($role === 'doctor') {
                $users = User::where('is_doctor', 1)->paginate();
            } elseif ($role === 'nurse') {
                $users = User::where('is_nurse', 1)->paginate();
            } elseif ($role === 'pharmacist') {
                $users = User::where('is_pharmacist', 1)->paginate();
            } elseif ($role === 'laboratorist') {
                $users = User::where('is_laboratorist', 1)->paginate();
            } elseif ($role === 'patient') {
                $users = User::where('is_admin', 0)
                    ->where('is_doctor', 0)
                    ->where('is_nurse', 0)
                    ->where('is_pharmacist', 0)
                    ->where('is_laboratorist', 0)
                    ->paginate();
            } elseif ($role === 'all') {
                $users = User::paginate();
            }

            // Return the paginated user data in JSON format
            return response()->json([
                'users' => $users,
                'total' => $users->count(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage()
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching users: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching by role users',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-all-users",
     *     summary="Get all users",
     *     description="Fetch all registered users.",
     *     operationId="getAllUsers",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="users", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                     @OA\Property(property="avatar", type="string", example="/storage/images/avatar.jpg"),
     *                     @OA\Property(property="is_admin", type="boolean", example=false),
     *                     @OA\Property(property="is_doctor", type="boolean", example=true),
     *                     @OA\Property(property="is_nurse", type="boolean", example=false),
     *                     @OA\Property(property="is_pharmacist", type="boolean", example=false),
     *                     @OA\Property(property="is_laboratorist", type="boolean", example=false),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000Z"),
     *                     @OA\Property(property="email_verified_at", type="string", format="date-time", example="2024-01-02T00:00:00.000Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching users"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
     */
    public function getAllUsers(Request $request)
    {
        try {
            // Fetch all users
            $users = User::all();

            // Return all user data in JSON format
            return response()->json([
                'users' => $users,
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching users: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching users',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/count-users",
     *     summary="Count users",
     *     description="Get counts of all users and their roles.",
     *     operationId="countUsers",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User counts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="allUsers", type="integer", example=100),
     *             @OA\Property(property="allAdmins", type="integer", example=10),
     *             @OA\Property(property="allDoctors", type="integer", example=30),
     *             @OA\Property(property="allNurses", type="integer", example=20),
     *             @OA\Property(property="allPharmacists", type="integer", example=15),
     *             @OA\Property(property="allLaboratorists", type="integer", example=5),
     *             @OA\Property(property="allPatients", type="integer", example=20)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching and counting users"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
     */
    public function countUsers()
    {
        try {
            $cacheKey = 'count_users';

            // Check if the user count is cached
            if (Cache::has($cacheKey)) {
                $count = Cache::get($cacheKey);
            } else {
                // Calculate user counts and cache the result
                $count = Cache::rememberForever($cacheKey, function () {
                    return [
                        'allUsers' => User::count(),
                        'allAdmins' => User::where('is_admin', 1)->count(),
                        'allDoctors' => User::where('is_doctor', 1)->count(),
                        'allNurses' => User::where('is_nurse', 1)->count(),
                        'allPharmacists' => User::where('is_pharmacist', 1)->count(),
                        'allLaboratorists' => User::where('is_laboratorist', 1)->count(),
                        'allPatients' => User::where('is_admin', 0)
                            ->where('is_doctor', 0)
                            ->where('is_nurse', 0)
                            ->where('is_pharmacist', 0)
                            ->where('is_laboratorist', 0)
                            ->count()
                    ];
                });
            }

            // Return the cached user counts in JSON format
            return response()->json($count, 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching and counting users: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching and counting users',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
