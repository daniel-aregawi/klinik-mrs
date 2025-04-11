<?php

namespace App\Http\Controllers;

use App\Models\BloodBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class BloodBankController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/v1/add-blood",
     *     summary="Add blood to the blood bank",
     *     description="Creates a new record in the blood bank.",
     *     operationId="addBlood",
     *     tags={"Blood Bank"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"bloodName", "bloodType", "quantity"},
     *             @OA\Property(property="bloodName", type="string", example="O+", description="Name of the blood"),
     *             @OA\Property(property="bloodType", type="string", example="O+", description="Type of the blood"),
     *             @OA\Property(property="quantity", type="integer", example=10, description="Quantity of blood to add")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Blood added successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="newBlood", type="object", description="Details of the newly added blood record"),
     *             @OA\Property(property="message", type="string", example="Blood added successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error adding blood",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error adding blood"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function addBlood(Request $request)
    {
        try {
            // Validate the incoming request data
            $validatedData = $request->validate([
                'bloodName' => 'required|string|max:255',
                'bloodType' => 'required|string|max:255',
                'quantity' => 'required|integer|min:1|max:100000',
            ]);

            // Extract the validated data
            $bloodName = $validatedData['bloodName'];
            $bloodType = $validatedData['bloodType'];
            $quantity = $validatedData['quantity'];

            // Create a new record in the BloodBank table
            $newBlood = BloodBank::create([
                'blood_name' => $bloodName,
                'blood_type' => $bloodType,
                'quantity' => $quantity,
            ]);

            // Return a success response with the newly added blood record
            return response()->json([
                'newBlood' => $newBlood,
                'message' => 'Blood added successfully',
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a JSON response with the error message
            Log::error('Error adding blood: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error adding blood',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-bloods",
     *     summary="Search for blood records",
     *     description="Fetches a paginated list of blood records from the blood bank based on a search query.",
     *     operationId="getSearchedBloods",
     *     tags={"Blood Bank"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", example="A+", description="Search term for blood name")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved searched blood records",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="blood_name", type="string"),
     *                 @OA\Property(property="blood_type", type="string"),
     *                 @OA\Property(property="quantity", type="integer")
     *             )),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching searched blood records",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching searched bloods"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function getSearchedBloods(Request $request)
    {
        try {
            // Retrieve the search query from the request
            $searchQuery = $request->query('search_query');

            // Query the BloodBank table for records matching the search query
            $bloods = BloodBank::where('blood_name', 'like', '%' . $searchQuery . '%')
                ->select('id', 'blood_name', 'blood_type', 'quantity')
                ->paginate();

            // Return a success response with the paginated blood records
            return response()->json([
                'data' => $bloods->items(),
                'current_page' => $bloods->currentPage(),
                'last_page' => $bloods->lastPage(),
                'total' => $bloods->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a JSON response with the error message
            Log::error('Error fetching bloods: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching searched bloods',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-bloods",
     *     summary="Retrieve a list of blood records",
     *     description="Fetches a paginated list of blood records from the blood bank.",
     *     operationId="getBloods",
     *     tags={"Blood Bank"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", example=15, description="Number of records per page")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved blood records",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="blood_name", type="string"),
     *                 @OA\Property(property="blood_type", type="string"),
     *                 @OA\Property(property="quantity", type="integer")
     *             )),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching blood records",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching bloods"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function getBloods(Request $request)
    {
        try {
            // Retrieve the number of records per page from the request, defaulting to 15
            $perPage = $request->query('per_page', 15);

            // Query the BloodBank table and paginate the results
            $bloods = BloodBank::select('id', 'blood_name', 'blood_type', 'quantity')
                ->paginate($perPage);

            // Return a success response with the paginated blood records
            return response()->json([
                'data' => $bloods->items(),
                'current_page' => $bloods->currentPage(),
                'last_page' => $bloods->lastPage(),
                'total' => $bloods->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a JSON response with the error message
            Log::error('Error fetching bloods: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching bloods',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Post(
     *     path="/api/v1/delete-blood",
     *     summary="Delete a blood record",
     *     description="Removes a specific blood record from the blood bank.",
     *     operationId="deleteBlood",
     *     tags={"Blood Bank"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="blood_id",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="integer", example=1, description="ID of the blood record to delete")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully deleted the blood record",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Blood deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Blood record not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Blood not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error deleting blood record",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the blood"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function deleteBlood(Request $request)
    {
        // Validate the incoming request to ensure 'blood_id' is provided and exists in the blood_banks table
        $validatedData = $request->validate([
            'blood_id' => 'required|integer|exists:blood_banks,id',
        ]);

        // Extract the validated blood ID
        $bloodId = $validatedData['blood_id'];

        try {
            // Find the blood record by ID and delete it
            $blood = BloodBank::findOrFail($bloodId);
            $blood->delete();

            // Return a success response indicating the blood record has been deleted
            return response()->json([
                'status' => 'success',
                'message' => 'Blood deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return an error response if the blood record is not found
            return response()->json([
                'status' => 'error',
                'message' => 'Blood not found',
            ], 404);
        } catch (\Exception $e) {
            // Log the error and return a generic error response if an exception occurs
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the blood',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/update-blood",
     *     summary="Update a blood record",
     *     description="Updates the details of a specific blood record.",
     *     operationId="updateBlood",
     *     tags={"Blood Bank"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="blood_id",
     *         in="path",
     *         required=true,
     *         description="ID of the blood record to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantity", "bloodName", "bloodType"},
     *             @OA\Property(property="quantity", type="integer", example=10),
     *             @OA\Property(property="bloodName", type="string", example="O+"),
     *             @OA\Property(property="bloodType", type="string", example="O+")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully updated blood record",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Blood updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error updating blood record",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error updating blood"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function updateBlood(Request $request)
    {
        try {
            // Validate the incoming request to ensure required fields are provided and valid
            $validatedData = $request->validate([
                'blood_id' => 'required|integer|exists:blood_banks,id',
                'quantity' => 'required|integer|min:1|max:100000',
                'bloodName' => 'required|string|max:255',
                'bloodType' => 'required|string|max:255',
            ]);

            // Extract the validated data
            $bloodId = $validatedData['blood_id'];
            $quantity = $validatedData['quantity'];
            $bloodName = $validatedData['bloodName'];
            $bloodType = $validatedData['bloodType'];

            // Find the blood record by ID and update its details
            $blood = BloodBank::findOrFail($bloodId);
            $blood->update([
                'quantity' => $quantity,
                'blood_name' => $bloodName,
                'blood_type' => $bloodType
            ]);

            // Return a success response indicating the blood record has been updated
            return response()->json([
                'message' => 'Blood updated successfully',
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response if an exception occurs
            Log::error('Error updating blood: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error updating blood',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v1/fetch-all-bloods",
     *     summary="Get all blood records",
     *     description="Retrieves all blood records from the blood bank.",
     *     operationId="getAllBloods",
     *     tags={"Blood Bank"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved all blood records",
     *         
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching blood records",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching bloods"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function getAllBloods()
    {
        try {
            // Retrieve all blood records from the BloodBank table
            $bloods = BloodBank::all();

            // Return a success response with all blood records
            return response()->json([
                'bloods' => $bloods,
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response if an exception occurs
            Log::error('Error fetching bloods: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching bloods',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
