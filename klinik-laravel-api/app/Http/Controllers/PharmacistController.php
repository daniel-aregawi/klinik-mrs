<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Nurse;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Pharmacist;
use App\Models\Laboratorist;
use App\Models\Medicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class PharmacistController extends Controller
{


    /**
     * @OA\Post(
     *     path="/api/v1/add-pharmacist",
     *     summary="Add a new pharmacist",
     *     tags={"Pharmacist"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="pharmacist@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Pharmacist created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="newPharmacist", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=5),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="pharmacist@example.com"),
     *                 @OA\Property(property="avatar", type="string", example="avatar-url.jpg")
     *             ),
     *             @OA\Property(property="message", type="string", example="Pharmacist created successfully!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=406,
     *         description="Email is already used in another role",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="This email is already used in the Pharmacists table!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Email not found in users",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Email not found in users!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="User role not set as pharmacist",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="First change the user role from the All Users section.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error adding pharmacist",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error adding pharmacist"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function addPharmacist(Request $request)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'email' => 'required|string|email|exists:users,email',
            ]);

            $email = $validatedData['email'];

            // Check if the email exists in various tables
            $existingPharmacistInUserTable = User::where('email', $email)->first();
            $checkIfLaboratorist = Laboratorist::where('email', $email)->first();
            $checkIfDoctor = Doctor::where('email', $email)->first();
            $checkIfPharmacist = Pharmacist::where('email', $email)->first();
            $checkIfPatient = Patient::where('email', $email)->first();
            $checkIfNurse = Nurse::where('email', $email)->first();

            // Check if the email is already used in the Pharmacists table
            if ($checkIfPharmacist) {
                return response()->json([
                    'error' => 'This email is already used in the Pharmacists table!',
                ], 406);
            }
            // Check if the email is already used in the Laboratorists table
            elseif ($checkIfLaboratorist) {
                return response()->json([
                    'error' => 'This email is already used in the Laboratorists table!',
                ], 406);
            }
            // Check if the email is already used in the Patients table
            elseif ($checkIfPatient) {
                return response()->json([
                    'error' => 'This email is already used in the Patients table!',
                ], 406);
            }
            // Check if the email is already used in the Doctors table
            elseif ($checkIfDoctor) {
                return response()->json([
                    'error' => 'This email is already used in the Doctors table!',
                ], 406);
            }
            // Check if the email is already used in the Nurses table
            elseif ($checkIfNurse) {
                return response()->json([
                    'error' => 'This email is already used in the Nurses table!',
                ], 406);
            }

            // Check if the email exists in the users table
            if (!$existingPharmacistInUserTable) {
                return response()->json([
                    'error' => 'Email not found in users!',
                ], 404);
            }
            // Check if the user is not marked as a pharmacist
            elseif (!$existingPharmacistInUserTable->is_pharmacist) {
                return response()->json([
                    'error' => 'First change the user role from the All Users section.',
                ], 400);
            }

            // Create a new pharmacist record
            $newPharmacist = Pharmacist::create([
                'user_id' => $existingPharmacistInUserTable->id,
                'name' => $existingPharmacistInUserTable->name,
                'email' => $email,
            ]);

            // Fetch the user's avatar and add it to the new pharmacist data
            $user = User::where('email', $email)->first();
            $newPharmacist['avatar'] = $user->avatar;

            // Return a successful response with the new pharmacist details
            return response()->json([
                'newPharmacist' => $newPharmacist,
                'message' => 'Pharmacist created successfully!',
            ], 201);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error adding pharmacist: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error adding pharmacist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-pharmacists",
     *     summary="Search pharmacists by name or email",
     *     tags={"Pharmacist"},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         description="Search query to find pharmacists by name or email",
     *         required=true,
     *         @OA\Schema(type="string", example="John")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of pharmacists matching the search query with pagination details",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", format="email", example="pharmacist@example.com"),
     *                     @OA\Property(property="avatar", type="string", example="avatar-url.jpg")
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="total", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching searched pharmacists",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching searched pharmacists"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function getSearchedPharmacists(Request $request)
    {
        try {
            // Get the search query from the request
            $searchQuery = $request->query('search_query');

            // Fetch pharmacists that match the search query
            $pharmacists = Pharmacist::with('user:id,avatar')
                ->where('name', 'like', '%' . $searchQuery . '%')
                ->orWhere('email', 'like', '%' . $searchQuery . '%')
                ->paginate(2);

            // Return the search results with pagination details
            return response()->json([
                'data' => $pharmacists->map(function ($pharmacist) {
                    return [
                        'id' => $pharmacist->id,
                        'name' => $pharmacist->name,
                        'email' => $pharmacist->email,
                        'avatar' => $pharmacist->user->avatar ?? null,
                    ];
                }),
                'current_page' => $pharmacists->currentPage(),
                'last_page' => $pharmacists->lastPage(),
                'total' => $pharmacists->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching pharmacists: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching searched pharmacists',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-pharmacists",
     *     summary="Get a paginated list of pharmacists",
     *     tags={"Pharmacist"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of pharmacists to display per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of pharmacists with pagination details",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", format="email", example="pharmacist@example.com"),
     *                     @OA\Property(property="avatar", type="string", example="avatar-url.jpg")
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="total", type="integer", example=50),
     *             @OA\Property(property="per_page", type="integer", example=15)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching pharmacists",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching pharmacists"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function getPharmacists(Request $request)
    {
        try {
            // Get the number of pharmacists to display per page from the request, default is 15
            $perPage = $request->query('per_page', 15);

            // Fetch all pharmacists with pagination
            $pharmacists = Pharmacist::with('user:id,avatar')
                ->paginate(2);

            // Format the pharmacist data for the response
            $pharmacistData = $pharmacists->map(function ($pharmacist) {
                return [
                    'id' => $pharmacist->id,
                    'name' => $pharmacist->name,
                    'email' => $pharmacist->email,
                    'avatar' => $pharmacist->user->avatar ?? null,
                ];
            });

            // Return the pharmacist data with pagination details
            return response()->json([
                'data' => $pharmacistData,
                'current_page' => $pharmacists->currentPage(),
                'last_page' => $pharmacists->lastPage(),
                'total' => $pharmacists->total(),
                'per_page' => $perPage
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching pharmacists: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching pharmacists',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Post(
     *     path="/api/v1/delete-pharmacist",
     *     summary="Delete a pharmacist by ID",
     *     tags={"Pharmacist"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="pharmacist_id", type="integer", example=1, description="The ID of the pharmacist to delete")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pharmacist deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Pharmacist deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pharmacist not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Pharmacist not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while deleting the pharmacist",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the pharmacist"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function deletePharmacist(Request $request)
    {
        // Validate the request data to ensure 'pharmacist_id' is provided and is an existing integer ID in the pharmacists table
        $validatedData = $request->validate([
            'pharmacist_id' => 'required|integer|exists:pharmacists,id',
        ]);

        $pharmacistId = $validatedData['pharmacist_id'];

        try {
            // Find the pharmacist by ID or fail if not found
            $pharmacist = Pharmacist::findOrFail($pharmacistId);
            // Delete the pharmacist record from the database
            $pharmacist->delete();

            // Return a success response
            return response()->json([
                'status' => 'success',
                'message' => 'Pharmacist deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return an error response if the pharmacist was not found
            return response()->json([
                'status' => 'error',
                'message' => 'Pharmacist not found',
            ], 404);
        } catch (\Exception $e) {
            // Return an error response if an exception occurs
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the pharmacist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-all-pharmacists",
     *     summary="Get all pharmacists",
     *     tags={"Pharmacist"},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully fetched all pharmacists",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="pharmacists",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="johndoe@example.com"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-09-21T12:34:56Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-09-21T12:34:56Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching pharmacists",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching pharmacists"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function getAllPharmacists()
    {
        try {
            // Fetch all pharmacists from the database
            $pharmacists = Pharmacist::all();
            // Return the pharmacists data as a JSON response
            return response()->json([
                'pharmacists' => $pharmacists,
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching pharmacists: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching pharmacists',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/pharmacist-dashboard-data",
     *     summary="Get pharmacist dashboard data",
     *     tags={"Pharmacist"},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved pharmacist dashboard data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="medicineTypes", type="object",
     *                 @OA\Property(property="antibiotic", type="integer"),
     *                 @OA\Property(property="analgesic", type="integer"),
     *                 @OA\Property(property="antipyretic", type="integer"),
     *                 @OA\Property(property="antiseptic", type="integer"),
     *                 @OA\Property(property="antiviral", type="integer"),
     *                 @OA\Property(property="antifungal", type="integer"),
     *                 @OA\Property(property="antihistamine", type="integer"),
     *                 @OA\Property(property="antidepressant", type="integer"),
     *                 @OA\Property(property="antidiabetic", type="integer"),
     *                 @OA\Property(property="antimalarial", type="integer"),
     *                 @OA\Property(property="antitussive", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getAllPharmacistDashboardData()
    {
        try {
            // Count the number of medicines in each category
            $antibiotic = Medicine::where('medicine_category', 'antibiotic')->count();
            $analgesic = Medicine::where('medicine_category', 'analgesic')->count();
            $antipyretic = Medicine::where('medicine_category', 'antipyretic')->count();
            $antiseptic = Medicine::where('medicine_category', 'antiseptic')->count();
            $antiviral = Medicine::where('medicine_category', 'antiviral')->count();
            $antifungal = Medicine::where('medicine_category', 'antifungal')->count();
            $antihistamine = Medicine::where('medicine_category', 'antihistamine')->count();
            $antidepressant = Medicine::where('medicine_category', 'antidepressant')->count();
            $antidiabetic = Medicine::where('medicine_category', 'antidiabetic')->count();
            $antimalarial = Medicine::where('medicine_category', 'antimalarial')->count();
            $antitussive = Medicine::where('medicine_category', 'antitussive')->count();

            // Return the counts of medicines in each category as a JSON response
            return response()->json([
                'medicineTypes' => [
                    'antibiotic' => $antibiotic,
                    'analgesic' => $analgesic,
                    'antipyretic' => $antipyretic,
                    'antiseptic' => $antiseptic,
                    'antiviral' => $antiviral,
                    'antifungal' => $antifungal,
                    'antihistamine' => $antihistamine,
                    'antidepressant' => $antidepressant,
                    'antidiabetic' => $antidiabetic,
                    'antimalarial' => $antimalarial,
                    'antitussive' => $antitussive,
                ],
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching pharmacist dashboard data: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching pharmacist dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
