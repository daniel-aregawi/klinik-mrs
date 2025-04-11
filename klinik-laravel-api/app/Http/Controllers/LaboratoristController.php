<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Nurse;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Pharmacist;
use App\Models\Laboratorist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class LaboratoristController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/v1/add-laboratorist",
     *     summary="Add a new laboratorist",
     *     tags={"Laboratorist"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", format="email", example="laboratorist@example.com", description="Email of the user to be added as a laboratorist")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Laboratorist created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="newLaboratorist", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="laboratorist@example.com"),
     *                 @OA\Property(property="avatar", type="string", example="url_to_avatar")
     *             ),
     *             @OA\Property(property="message", type="string", example="Laboratorist created successfully!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=406,
     *         description="Email already exists in other tables",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="This email is already used in the Laboratorists table!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Email not found in users",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Email not found in users!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid role for the user",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="First change the user role from the All Users section.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error adding laboratorist",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error adding laboratorist"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function addLaboratorist(Request $request)
    {
        try {
            // Validate incoming request to ensure 'email' is provided and exists in the 'users' table
            $validatedData = $request->validate([
                'email' => 'required|string|email|exists:users,email',
            ]);

            $email = $validatedData['email'];

            // Check if the email exists in various tables to avoid duplicates
            $existingLaboratoristInUserTable = User::where('email', $email)->first();
            $checkIfLaboratorist = Laboratorist::where('email', $email)->first();
            $checkIfDoctor = Doctor::where('email', $email)->first();
            $checkIfPharmacist = Pharmacist::where('email', $email)->first();
            $checkIfPatient = Patient::where('email', $email)->first();
            $checkIfNurse = Nurse::where('email', $email)->first();

            // Return an error response if the email is already used in any of the tables
            if ($checkIfLaboratorist) {
                return response()->json([
                    'error' => 'This email is already used in the Laboratorists table!',
                ], 406);
            } elseif ($checkIfPharmacist) {
                return response()->json([
                    'error' => 'This email is already used in the Pharmacists table!',
                ], 406);
            } elseif ($checkIfPatient) {
                return response()->json([
                    'error' => 'This email is already used in the Patients table!',
                ], 406);
            } elseif ($checkIfDoctor) {
                return response()->json([
                    'error' => 'This email is already used in the Doctors table!',
                ], 406);
            } elseif ($checkIfNurse) {
                return response()->json([
                    'error' => 'This email is already used in the Nurses table!',
                ], 406);
            }

            // Check if the email exists in the users table and whether the user is a laboratorist
            if (!$existingLaboratoristInUserTable) {
                return response()->json([
                    'error' => 'Email not found in users!',
                ], 404);
            } elseif (!$existingLaboratoristInUserTable->is_laboratorist) {
                return response()->json([
                    'error' => 'First change the user role from the All Users section.',
                ], 400);
            }

            // Create a new laboratorist entry in the database
            $newLaboratorist = Laboratorist::create([
                'user_id' => $existingLaboratoristInUserTable->id,
                'name' => $existingLaboratoristInUserTable->name,
                'email' => $email,
            ]);

            // Attach the user's avatar to the laboratorist data
            $user = User::where('email', $email)->first();
            $newLaboratorist['avatar'] = $user->avatar;

            // Return a success response with the newly created laboratorist data
            return response()->json([
                'newLaboratorist' => $newLaboratorist,
                'message' => 'Laboratorist created successfully!',
            ], 201);
        } catch (\Exception $e) {
            // Log any errors that occur and return an error response
            Log::error('Error adding laboratorist: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error adding laboratorist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-laboratorists",
     *     summary="Search and retrieve laboratorists by name or email",
     *     tags={"Laboratorist"},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         description="Search query to filter laboratorists by name or email",
     *         required=true,
     *         @OA\Schema(type="string", example="John")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of paginated laboratorists matching the search query",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="laboratorist@example.com"),
     *                     @OA\Property(property="avatar", type="string", example="url_to_avatar")
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="total", type="integer", example=25)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching searched laboratorists",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching searched laboratorists"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getSearchedLaboratorists(Request $request)
    {
        try {
            // Retrieve the search query from the request
            $searchQuery = $request->query('search_query');

            // Fetch laboratorists matching the search query with their associated user avatar
            $laboratorists = Laboratorist::with('user:id,avatar')
                ->where('name', 'like', '%' . $searchQuery . '%')
                ->orWhere('email', 'like', '%' . $searchQuery . '%')
                ->paginate();

            // Return the list of laboratorists along with pagination details
            return response()->json([
                'data' => $laboratorists->map(function ($laboratorist) {
                    return [
                        'id' => $laboratorist->id,
                        'name' => $laboratorist->name,
                        'email' => $laboratorist->email,
                        'avatar' => $laboratorist->user->avatar ?? null,
                    ];
                }),
                'current_page' => $laboratorists->currentPage(),
                'last_page' => $laboratorists->lastPage(),
                'total' => $laboratorists->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log any errors that occur and return an error response
            Log::error('Error fetching laboratorists: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching searched laboratorists',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-laboratorists",
     *     summary="Get a paginated list of laboratorists",
     *     tags={"Laboratorist"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of laboratorists to return per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of paginated laboratorists",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="laboratorist@example.com"),
     *                     @OA\Property(property="avatar", type="string", example="url_to_avatar")
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="total", type="integer", example=75),
     *             @OA\Property(property="per_page", type="integer", example=15)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching laboratorists",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching laboratorists"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getLaboratorists(Request $request)
    {
        try {
            // Determine the number of results per page from the query parameter or default to 15
            $perPage = $request->query('per_page', 15);

            // Fetch laboratorists with their associated user avatars, paginated
            $laboratorists = Laboratorist::with('user:id,avatar')
                ->paginate($perPage);

            // Prepare the laboratorist data for response
            $laboratoristData = $laboratorists->map(function ($laboratorist) {
                return [
                    'id' => $laboratorist->id,
                    'name' => $laboratorist->name,
                    'email' => $laboratorist->email,
                    'avatar' => $laboratorist->user->avatar ?? null,
                ];
            });

            // Return the list of laboratorists with pagination details
            return response()->json([
                'data' => $laboratoristData,
                'current_page' => $laboratorists->currentPage(),
                'last_page' => $laboratorists->lastPage(),
                'total' => $laboratorists->total(),
                'per_page' => $perPage
            ], 200);
        } catch (\Exception $e) {
            // Log any errors that occur and return an error response
            Log::error('Error fetching laboratorists: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching laboratorists',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Post(
     *     path="/api/v1/delete-laboratorist",
     *     summary="Delete a laboratorist by ID",
     *     tags={"Laboratorist"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="laboratorist_id",
     *                 type="integer",
     *                 description="ID of the laboratorist to delete",
     *                 example=1
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Laboratorist deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Laboratorist deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Laboratorist not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Laboratorist not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while deleting the laboratorist",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the laboratorist"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function deleteLaboratorist(Request $request)
    {
        // Validate the incoming request to ensure 'laboratorist_id' is provided and exists in the 'laboratorists' table
        $validatedData = $request->validate([
            'laboratorist_id' => 'required|integer|exists:laboratorists,id',
        ]);

        $laboratoristId = $validatedData['laboratorist_id'];

        try {
            // Find the laboratorist by ID or throw an exception if not found
            $laboratorist = Laboratorist::findOrFail($laboratoristId);

            // Delete the laboratorist record
            $laboratorist->delete();

            // Return a success response indicating that the laboratorist was deleted
            return response()->json([
                'status' => 'success',
                'message' => 'Laboratorist deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return an error response if the laboratorist was not found
            return response()->json([
                'status' => 'error',
                'message' => 'Laboratorist not found',
            ], 404);
        } catch (\Exception $e) {
            // Log the error and return a generic error response for other exceptions
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the laboratorist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-all-laboratorists",
     *     summary="Get all laboratorists",
     *     tags={"Laboratorist"},
     *     @OA\Response(
     *         response=200,
     *         description="List of all laboratorists",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="laboratorists",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john.doe@example.com")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching laboratorists",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching laboratorists"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function getAllLaboratorists()
    {
        try {
            // Fetch all laboratorists from the database
            $laboratorists = Laboratorist::all();

            // Return a response with the list of laboratorists
            return response()->json([
                'laboratorists' => $laboratorists,
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a response indicating an error occurred
            Log::error('Error fetching laboratorists: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching laboratorists',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
