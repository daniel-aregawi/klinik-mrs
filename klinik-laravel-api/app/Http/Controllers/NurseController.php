<?php

namespace App\Http\Controllers;

use App\Models\BedAllotment;
use App\Models\BirthReport;
use App\Models\BloodBank;
use App\Models\BloodDonor;
use App\Models\DeathReport;
use App\Models\User;
use App\Models\Nurse;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Pharmacist;
use App\Models\Laboratorist;
use App\Models\Medicine;
use App\Models\OperationReport;
use App\Models\Vaccine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class NurseController extends Controller
{


    /**
     * @OA\Post(
     *     path="/api/v1/add-nurse",
     *     summary="Add a new nurse",
     *     tags={"Nurse"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", example="nurse@example.com", description="Email of the nurse, must exist in the users table and have the role of nurse.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Nurse created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="newNurse", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=1, description="User ID associated with the nurse"),
     *                 @OA\Property(property="name", type="string", example="Nurse Name", description="Name of the nurse"),
     *                 @OA\Property(property="email", type="string", example="nurse@example.com", description="Email of the nurse"),
     *                 @OA\Property(property="avatar", type="string", example="avatar.png", description="Avatar URL of the nurse")
     *             ),
     *             @OA\Property(property="message", type="string", example="Nurse created successfully!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request, invalid user role",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="First change the user role from the All Users section.")
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
     *         response=406,
     *         description="Email already used in another table",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="This email is already used in the Doctors table!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while adding the nurse",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error adding nurse"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function addNurse(Request $request)
    {
        // Handle the addition of a new nurse
        try {
            // Validate the request to ensure the email is provided, is valid, and exists in the users table
            $validatedData = $request->validate([
                'email' => 'required|string|email|exists:users,email',
            ]);

            $email = $validatedData['email'];

            // Check if the email exists in various related tables to ensure it's not already in use
            $existingNurseInUserTable = User::where('email', $email)->first();
            $existingNurseInNurseTable = Nurse::where('email', $email)->first();
            $checkIfDoctor = Doctor::where('email', $email)->first();
            $checkIfPharmacist = Pharmacist::where('email', $email)->first();
            $checkIfPatient = Patient::where('email', $email)->first();
            $checkIfLaboratorist = Laboratorist::where('email', $email)->first();

            // Return an error response if the email is found in any of the related tables
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
            } elseif ($existingNurseInNurseTable) {
                return response()->json([
                    'error' => 'This email is already used in the Nurses table!',
                ], 406);
            }

            // Check if the email exists in the users table and if it has the role of nurse
            if (!$existingNurseInUserTable) {
                return response()->json([
                    'error' => 'Email not found in users!',
                ], 404);
            } elseif (!$existingNurseInUserTable->is_nurse) {
                return response()->json([
                    'error' => 'First change the user role from the All Users section.',
                ], 400);
            }

            // Create a new nurse record with the provided email and user details
            $newNurse = Nurse::create([
                'user_id' => $existingNurseInUserTable->id,
                'name' => $existingNurseInUserTable->name,
                'email' => $email,
            ]);
            $user = User::where('email', $email)->first();
            $newNurse['avatar'] = $user->avatar;

            // Return a success response with the newly created nurse details
            return response()->json([
                'newNurse' => $newNurse,
                'message' => 'Nurse created successfully!',
            ], 201);
        } catch (\Exception $e) {
            // Log the error and return a generic error response if an exception is thrown
            Log::error('Error adding nurse: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error adding nurse',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-nurses",
     *     summary="Search for nurses by name or email",
     *     tags={"Nurse"},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", example="nurse", description="Search term for nurse name or email")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of searched nurses",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1, description="ID of the nurse"),
     *                     @OA\Property(property="name", type="string", example="Nurse Name", description="Name of the nurse"),
     *                     @OA\Property(property="email", type="string", example="nurse@example.com", description="Email of the nurse"),
     *                     @OA\Property(property="avatar", type="string", example="avatar.png", description="Avatar URL of the nurse")
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1, description="Current page of the pagination"),
     *             @OA\Property(property="last_page", type="integer", example=5, description="Last page of the pagination"),
     *             @OA\Property(property="total", type="integer", example=100, description="Total number of nurses matching the search")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while fetching searched nurses",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching searched nurses"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getSearchedNurses(Request $request)
    {
        // Handle the retrieval of nurses based on a search query
        try {
            // Retrieve the search query from the request
            $searchQuery = $request->query('search_query');
            // Fetch nurses matching the search query, including user details
            $nurses = Nurse::with('user:id,avatar')
                ->where('name', 'like', '%' . $searchQuery . '%')
                ->orWhere('email', 'like', '%' . $searchQuery . '%')
                ->paginate(2);

            // Return a paginated list of nurses with their details
            return response()->json([
                'data' => $nurses->map(function ($nurse) {
                    return [
                        'id' => $nurse->id,
                        'name' => $nurse->name,
                        'email' => $nurse->email,
                        'avatar' => $nurse->user->avatar ?? null,
                    ];
                }),
                'current_page' => $nurses->currentPage(),
                'last_page' => $nurses->lastPage(),
                'total' => $nurses->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response if an exception is thrown
            Log::error('Error fetching nurses: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching searched nurses',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-nurses",
     *     summary="Retrieve all nurses",
     *     tags={"Nurse"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", example=15, description="Number of nurses to return per page")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of nurses",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1, description="ID of the nurse"),
     *                     @OA\Property(property="name", type="string", example="Nurse Name", description="Name of the nurse"),
     *                     @OA\Property(property="email", type="string", example="nurse@example.com", description="Email of the nurse"),
     *                     @OA\Property(property="avatar", type="string", example="avatar.png", description="Avatar URL of the nurse")
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1, description="Current page of the pagination"),
     *             @OA\Property(property="last_page", type="integer", example=5, description="Last page of the pagination"),
     *             @OA\Property(property="total", type="integer", example=100, description="Total number of nurses"),
     *             @OA\Property(property="per_page", type="integer", example=15, description="Number of items per page")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while fetching nurses",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching nurses"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getNurses(Request $request)
    {
        // Handle the retrieval of all nurses with pagination
        try {
            // Determine the number of items per page from the request or use a default value
            $perPage = $request->query('per_page', 15);

            // Fetch nurses with user details, paginated
            $nurses = Nurse::with('user:id,avatar')
                ->paginate($perPage);
            // Map the nurses data to include necessary details
            $nurseData = $nurses->map(function ($nurse) {
                return [
                    'id' => $nurse->id,
                    'name' => $nurse->name,
                    'email' => $nurse->email,
                    'avatar' => $nurse->user->avatar ?? null,
                ];
            });

            // Return the paginated list of nurses
            return response()->json([
                'data' => $nurseData,
                'current_page' => $nurses->currentPage(),
                'last_page' => $nurses->lastPage(),
                'total' => $nurses->total(),
                'per_page' => $perPage // Corrected this to provide the correct per_page value
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response if an exception is thrown
            Log::error('Error fetching nurses: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching nurses',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Post(
     *     path="/api/v1/delete-nurse",
     *     summary="Delete a nurse by ID",
     *     tags={"Nurse"},
     *     @OA\Parameter(
     *         name="nurse_id",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="integer", example=1, description="ID of the nurse to be deleted")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Nurse deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Nurse deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Nurse not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Nurse not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while deleting the nurse",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the nurse"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function deleteNurse(Request $request)
    {
        // Handle the deletion of a nurse
        $validatedData = $request->validate([
            'nurse_id' => 'required|integer|exists:nurses,id', // Validate that 'nurse_id' is required, an integer, and exists in the 'nurses' table
        ]);

        $nurseId = $validatedData['nurse_id'];

        try {
            // Find the nurse by ID or fail if not found
            $nurse = Nurse::findOrFail($nurseId);
            // Delete the nurse record
            $nurse->delete();

            // Return a success response
            return response()->json([
                'status' => 'success',
                'message' => 'Nurse deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return an error response if the nurse was not found
            return response()->json([
                'status' => 'error',
                'message' => 'Nurse not found',
            ], 404);
        } catch (\Exception $e) {
            // Return a generic error response if any other exception occurs
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the nurse',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-all-nurses",
     *     summary="Retrieve all nurses",
     *     tags={"Nurse"},
     *     @OA\Response(
     *         response=200,
     *         description="List of all nurses retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="nurses", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                 @OA\Property(property="avatar", type="string", example="url_to_avatar")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while fetching nurses",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching nurses"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getAllNurses()
    {
        // Handle the retrieval of all nurses
        try {
            // Fetch all nurses from the database
            $nurses = Nurse::all();
            // Return a success response with the list of nurses
            return response()->json([
                'nurses' => $nurses,
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response if an exception occurs
            Log::error('Error fetching nurses: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching nurses',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
 * @OA\Get(
 *     path="/api/v1/nurse/dashboard",
 *     summary="Get all nurse dashboard data",
 *     tags={"Nurse"},
 *     description="Retrieve counts of various data related to nurses for the dashboard.",
 *     operationId="getAllNurseDashboardData",
 *     @OA\Response(
 *         response=200,
 *         description="Successful response with counts",
 *         @OA\JsonContent(
 *             @OA\Property(property="bloods", type="integer", example=50),
 *             @OA\Property(property="bloodDonors", type="integer", example=150),
 *             @OA\Property(property="operationReports", type="integer", example=20),
 *             @OA\Property(property="birthReports", type="integer", example=10),
 *             @OA\Property(property="deathReports", type="integer", example=5),
 *             @OA\Property(property="bedAllotments", type="integer", example=100),
 *             @OA\Property(property="medicines", type="integer", example=200),
 *             @OA\Property(property="vaccines", type="integer", example=300)
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error fetching nurse dashboard data",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Error fetching nurse dashboard data"),
 *             @OA\Property(property="error", type="string", example="Detailed error message")
 *         )
 *     )
 * )
 */
    public function getAllNurseDashboardData()
    {
        // Handle the retrieval of dashboard data related to nurses
        try {
            // Count various related data for the dashboard
            $bloods = BloodBank::count();
            $bloodDonors = BloodDonor::count();
            $operationReports = OperationReport::count();
            $birthReports = BirthReport::count();
            $deathReports = DeathReport::count();
            $bedAllotments = BedAllotment::count();
            $medicines = Medicine::count();
            $vaccines = Vaccine::count();

            // Return a success response with all the counts
            return response()->json([
                'bloods' => $bloods,
                'bloodDonors' => $bloodDonors,
                'operationReports' => $operationReports,
                'birthReports' => $birthReports,
                'deathReports' => $deathReports,
                'bedAllotments' => $bedAllotments,
                'medicines' => $medicines,
                'vaccines' => $vaccines,
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response if an exception occurs
            Log::error('Error fetching nurse dashboard data: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching nurse dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
