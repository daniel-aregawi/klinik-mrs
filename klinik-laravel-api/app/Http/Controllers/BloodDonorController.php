<?php

namespace App\Http\Controllers;

use App\Models\BloodDonor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class BloodDonorController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/v1/add-blood-donor",
     *     summary="Add a new blood donor",
     *     description="Creates a new record for a blood donor.",
     *     operationId="addBloodDonor",
     *     tags={"Blood Donors"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"donorName", "donorAge", "donorSex", "quantity", "bloodName", "bloodType", "lastDonationDate", "identityCardId"},
     *             @OA\Property(property="donorName", type="string", example="John Doe"),
     *             @OA\Property(property="donorAge", type="integer", example=30),
     *             @OA\Property(property="donorSex", type="string", example="Male"),
     *             @OA\Property(property="quantity", type="integer", example=1),
     *             @OA\Property(property="bloodName", type="string", example="O+"),
     *             @OA\Property(property="bloodType", type="string", example="O+"),
     *             @OA\Property(property="lastDonationDate", type="string", format="date", example="2023-09-15"),
     *             @OA\Property(property="identityCardId", type="string", example="123456789")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully added blood donor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="newBloodDonor", type="object"),
     *             @OA\Property(property="message", type="string", example="Blood donor added successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error adding blood donor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error adding blood donor"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function addBloodDonor(Request $request)
    {
        try {
            // Validate the incoming request to ensure all required fields are provided and valid
            $validatedData = $request->validate([
                'donorName' => 'required|string|max:255',
                'donorAge' => 'required|integer|min:1|max:120',
                'donorSex' => 'required|string|max:10',
                'quantity' => 'required|integer|min:1|max:100000',
                'bloodName' => 'required|string|max:255',
                'bloodType' => 'required|string|max:255',
                'lastDonationDate' => 'required|date',
                'identityCardId' => 'required|string|max:255',
            ]);

            // Create a new blood donor record with the validated data
            $newBloodDonor = BloodDonor::create([
                'name' => $validatedData['donorName'],
                'age' => $validatedData['donorAge'],
                'sex' => $validatedData['donorSex'],
                'quantity' => $validatedData['quantity'],
                'blood_name' => $validatedData['bloodName'],
                'blood_type' => $validatedData['bloodType'],
                'last_donation_date' => $validatedData['lastDonationDate'],
                'identity_card_id' => $validatedData['identityCardId'],
            ]);

            // Return a success response with the newly created blood donor record
            return response()->json([
                'newBloodDonor' => $newBloodDonor,
                'message' => 'Blood donor added successfully',
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response if an exception occurs
            Log::error('Error adding blood donor: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error adding blood donor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-blood-donors",
     *     summary="Search for blood donors",
     *     description="Retrieves paginated records of blood donors based on search criteria.",
     *     operationId="getSearchedBloodDonors",
     *     tags={"Blood Donors"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         required=true,
     *         description="Search term to filter blood donors",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved searched blood donors",
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching searched blood donors",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching searched blood donors"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function getSearchedBloodDonors(Request $request)
    {
        try {
            // Retrieve the search query from the request
            $searchQuery = $request->query('search_query');

            // Query the BloodDonor model to find records matching the search query
            $bloodDonors = BloodDonor::where('blood_name', 'like', '%' . $searchQuery . '%')
                ->orWhere('blood_type', 'like', '%' . $searchQuery . '%')
                ->orWhere('name', 'like', '%' . $searchQuery . '%')
                ->orWhere('identity_card_id', 'like', '%' . $searchQuery . '%')
                ->select(
                    'id',
                    'name',
                    'age',
                    'sex',
                    'blood_name',
                    'blood_type',
                    'quantity',
                    'last_donation_date',
                    'identity_card_id'
                )
                ->paginate();

            // Return the search results along with pagination details
            return response()->json([
                'data' => $bloodDonors->items(),
                'current_page' => $bloodDonors->currentPage(),
                'last_page' => $bloodDonors->lastPage(),
                'total' => $bloodDonors->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response if an exception occurs
            Log::error('Error fetching blood donors: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching searched blood donors',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-blood-donors",
     *     summary="Get a list of blood donors",
     *     description="Retrieves paginated records of blood donors.",
     *     operationId="getBloodDonors",
     *     tags={"Blood Donors"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved blood donors",
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching blood donors",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching blood donors"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function getBloodDonors(Request $request)
    {
        try {
            // Retrieve the number of items per page from the request, default to 15
            $perPage = $request->query('per_page', 15);

            // Retrieve blood donor records with pagination
            $bloodDonors = BloodDonor::select(
                'id',
                'name',
                'age',
                'sex',
                'blood_name',
                'blood_type',
                'quantity',
                'last_donation_date',
                'identity_card_id'
            )
                ->paginate($perPage);

            // Return the blood donor records along with pagination details
            return response()->json([
                'data' => $bloodDonors->items(),
                'current_page' => $bloodDonors->currentPage(),
                'last_page' => $bloodDonors->lastPage(),
                'total' => $bloodDonors->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response if an exception occurs
            Log::error('Error fetching blood donors: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching blood donors',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/delete-blood-donor",
     *     summary="Delete a blood donor",
     *     description="Deletes a specified blood donor record.",
     *     operationId="deleteBloodDonor",
     *     tags={"Blood Donors"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="bloodDonorId",
     *         in="path",
     *         required=true,
     *         description="ID of the blood donor to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully deleted blood donor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Blood donor deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Blood donor not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Blood donor not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error deleting blood donor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the blood donor"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function deleteBloodDonor(Request $request)
    {
        // Validate that the blood donor ID is provided and exists in the database
        $validatedData = $request->validate([
            'bloodDonorId' => 'required|integer|exists:blood_donors,id',
        ]);

        $bloodDonorId = $validatedData['bloodDonorId'];

        try {
            // Find the blood donor by ID or fail if not found
            $bloodDonor = BloodDonor::findOrFail($bloodDonorId);
            // Delete the blood donor record from the database
            $bloodDonor->delete();

            // Return a success response if deletion is successful
            return response()->json([
                'status' => 'success',
                'message' => 'Blood donor deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return an error response if the blood donor was not found
            return response()->json([
                'status' => 'error',
                'message' => 'Blood donor not found',
            ], 404);
        } catch (\Exception $e) {
            // Return a generic error response if an unexpected error occurs
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the blood donor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/update-blood-donor",
     *     summary="Update a blood donor",
     *     description="Updates the details of an existing blood donor.",
     *     operationId="updateBloodDonor",
     *     tags={"Blood Donors"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"bloodId", "donorName", "donorAge", "donorSex", "quantity", "bloodName", "bloodType", "lastDonationDate", "identityCardId"},
     *             @OA\Property(property="bloodId", type="integer", example=1),
     *             @OA\Property(property="donorName", type="string", example="John Doe"),
     *             @OA\Property(property="donorAge", type="integer", example=30),
     *             @OA\Property(property="donorSex", type="string", example="Male"),
     *             @OA\Property(property="quantity", type="integer", example=2),
     *             @OA\Property(property="bloodName", type="string", example="O+"),
     *             @OA\Property(property="bloodType", type="string", example="O Positive"),
     *             @OA\Property(property="lastDonationDate", type="string", format="date", example="2024-09-20"),
     *             @OA\Property(property="identityCardId", type="integer", example="123456789"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Blood donor updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Blood donor updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error updating blood donor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error updating blood donor"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function updateBloodDonor(Request $request)
    {
        try {
            // Validate the incoming request to ensure all required fields are provided and valid
            $validatedData = $request->validate([
                'bloodId' => 'required|integer|exists:blood_donors,id',
                'donorName' => 'required|string|max:255',
                'donorAge' => 'required|integer|min:1|max:120',
                'donorSex' => 'required|string|max:10',
                'quantity' => 'required|integer|min:1|max:100000',
                'bloodName' => 'required|string|max:255',
                'bloodType' => 'required|string|max:255',
                'lastDonationDate' => 'required|date',
                'identityCardId' => 'required|integer',
            ]);

            $bloodDonorId = $validatedData['bloodId'];
            // Find the blood donor by ID or fail if not found
            $bloodDonor = BloodDonor::findOrFail($bloodDonorId);

            // Update the blood donor record with the validated data
            $bloodDonor->update([
                'name' => $validatedData['donorName'],
                'age' => $validatedData['donorAge'],
                'sex' => $validatedData['donorSex'],
                'quantity' => $validatedData['quantity'],
                'blood_name' => $validatedData['bloodName'],
                'blood_type' => $validatedData['bloodType'],
                'last_donation_date' => $validatedData['lastDonationDate'],
                'identity_card_id' => $validatedData['identityCardId'],
            ]);

            // Return a success response if the update is successful
            return response()->json([
                'message' => 'Blood donor updated successfully',
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response if an exception occurs
            Log::error('Error updating blood donor: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error updating blood donor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v1/fetch-all-blood-donors",
     *     summary="Get all blood donors",
     *     description="Retrieves a list of all blood donors.",
     *     operationId="getAllBloodDonors",
     *     tags={"Blood Donors"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of blood donors",
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching blood donors",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching blood donors"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function getAllBloodDonors()
    {
        try {
            // Retrieve all blood donor records from the database
            $bloodDonors = BloodDonor::all();
            // Return the blood donors in the response
            return response()->json([
                'bloodDonors' => $bloodDonors,
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response if an exception occurs
            Log::error('Error fetching blood donors: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching blood donors',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
