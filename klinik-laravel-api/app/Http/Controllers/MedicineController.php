<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class MedicineController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/v1/add-medicine",
     *     summary="Add a new medicine",
     *     tags={"Medicines"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"medicineName","medicineCategory","medicinePrice","manufacturingCompany","medicineStatus","expirationDate","quantity"},
     *             @OA\Property(property="medicineName", type="string"),
     *             @OA\Property(property="medicineCategory", type="string"),
     *             @OA\Property(property="medicineDescription", type="string"),
     *             @OA\Property(property="medicinePrice", type="number", format="float"),
     *             @OA\Property(property="manufacturingCompany", type="string"),
     *             @OA\Property(property="medicineStatus", type="string", enum={"instock", "outofstock"}),
     *             @OA\Property(property="expirationDate", type="string", format="date"),
     *             @OA\Property(property="quantity", type="integer"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully added the medicine",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while adding medicine",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function addMedicine(Request $request)
    {
        // Validate the incoming request to ensure all required fields are present and correctly formatted
        try {
            $validatedData = $request->validate([
                'medicineName' => 'required|string|max:255',
                'medicineCategory' => 'required|string|max:255',
                'medicineDescription' => 'nullable|string',
                'medicinePrice' => 'required|numeric',
                'manufacturingCompany' => 'required|string|max:255',
                'medicineStatus' => 'required|string|in:instock,outofstock',
                'expirationDate' => 'required|date',
                'quantity' => 'required|integer|min:1',
            ]);

            // Create a new medicine record in the database
            $newMedicine = Medicine::create([
                'name' => $validatedData['medicineName'],
                'medicine_category' => $validatedData['medicineCategory'],
                'description' => $validatedData['medicineDescription'],
                'price' => $validatedData['medicinePrice'],
                'manufacturing_company' => $validatedData['manufacturingCompany'],
                'status' => $validatedData['medicineStatus'],
                'expiration_date' => $validatedData['expirationDate'],
                'quantity' => $validatedData['quantity'],
            ]);

            // Return a success response with the newly created medicine
            return response()->json([
                'newMedicine' => $newMedicine,
                'message' => 'Medicine added successfully',
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response
            Log::error('Error adding medicine: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error adding medicine',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-medicines",
     *     summary="Search for medicines based on a query",
     *     tags={"Medicines"},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         required=true,
     *         description="Search query for medicine name or description",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully fetched the searched medicines",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching searched medicines",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getSearchedMedicines(Request $request)
    {
        // Fetch medicines based on search query and paginate the results
        try {
            $searchQuery = $request->query('search_query');

            // Perform a search for medicines based on description or name
            $medicines = Medicine::where('description', 'like', '%' . $searchQuery . '%')
                ->orWhere('name', 'like', '%' . $searchQuery . '%')
                ->select(
                    'id',
                    'name',
                    'medicine_category',
                    'description',
                    'price',
                    'manufacturing_company',
                    'status',
                    'expiration_date',
                    'quantity'
                )
                ->paginate(2);

            // Return the search results with pagination data
            return response()->json([
                'data' => $medicines->items(),
                'current_page' => $medicines->currentPage(),
                'last_page' => $medicines->lastPage(),
                'total' => $medicines->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a response indicating an error occurred
            Log::error('Error fetching medicines: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching searched medicines',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-medicines",
     *     summary="Get a paginated list of medicines",
     *     tags={"Medicines"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of medicines per page",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully fetched the medicines",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching medicines",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getMedicines(Request $request)
    {
        // Fetch all medicines and paginate the results
        try {
            $perPage = $request->query('per_page', 15);

            // Select specific columns for the medicines and paginate the results
            $medicines = Medicine::select(
                'id',
                'name',
                'medicine_category',
                'description',
                'price',
                'manufacturing_company',
                'status',
                'expiration_date',
                'quantity'
            )
                ->paginate($perPage);

            // Return the paginated list of medicines
            return response()->json([
                'data' => $medicines->items(),
                'current_page' => $medicines->currentPage(),
                'last_page' => $medicines->lastPage(),
                'total' => $medicines->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a response indicating an error occurred
            Log::error('Error fetching medicines: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching medicines',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/delete-medicine",
     *     summary="Delete a medicine by ID",
     *     tags={"Medicines"},
     *     @OA\Parameter(
     *         name="medicineId",
     *         in="query",
     *         required=true,
     *         description="ID of the medicine to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully deleted the medicine",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Medicine deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Medicine not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Medicine not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while deleting the medicine",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function deleteMedicine(Request $request)
    {
        // Validate the request to ensure 'medicineId' is present, an integer, and exists in the medicines table
        $validatedData = $request->validate([
            'medicineId' => 'required|integer|exists:medicines,id',
        ]);

        $medicineId = $validatedData['medicineId'];

        try {
            // Find the medicine by ID or throw an exception if not found
            $medicine = Medicine::findOrFail($medicineId);
            // Delete the medicine record from the database
            $medicine->delete();

            // Return a success response with a confirmation message
            return response()->json([
                'status' => 'success',
                'message' => 'Medicine deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return an error response if the medicine was not found
            return response()->json([
                'status' => 'error',
                'message' => 'Medicine not found',
            ], 404);
        } catch (\Exception $e) {
            // Return a generic error response for any other exceptions
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the medicine',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/update-medicine",
     *     summary="Update a medicine",
     *     tags={"Medicines"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="medicineId", type="integer"),
     *             @OA\Property(property="medicineName", type="string"),
     *             @OA\Property(property="medicineCategory", type="string"),
     *             @OA\Property(property="medicineDescription", type="string", nullable=true),
     *             @OA\Property(property="medicinePrice", type="number", format="float"),
     *             @OA\Property(property="manufacturingCompany", type="string"),
     *             @OA\Property(property="medicineStatus", type="string", enum={"instock", "outofstock"}),
     *             @OA\Property(property="expirationDate", type="string", format="date"),
     *             @OA\Property(property="quantity", type="integer"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Medicine updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="updatedMedicine", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="medicine_category", type="string"),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="price", type="number", format="float"),
     *                 @OA\Property(property="manufacturing_company", type="string"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="expiration_date", type="string", format="date"),
     *                 @OA\Property(property="quantity", type="integer"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while updating medicine",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function updateMedicine(Request $request)
    {
        // Validate the request to ensure all required fields are present and correctly formatted
        try {
            $validatedData = $request->validate([
                'medicineId' => 'required|integer|exists:medicines,id',
                'medicineName' => 'required|string|max:255',
                'medicineCategory' => 'required|string|max:255',
                'medicineDescription' => 'nullable|string',
                'medicinePrice' => 'required|numeric',
                'manufacturingCompany' => 'required|string|max:255',
                'medicineStatus' => 'required|string|in:instock,outofstock',
                'expirationDate' => 'required|date',
                'quantity' => 'required|integer|min:1',
            ]);

            $medicineId = $validatedData['medicineId'];
            // Find the medicine by ID or throw an exception if not found
            $medicine = Medicine::findOrFail($medicineId);

            // Update the medicine record with the provided data
            $medicine->update([
                'name' => $validatedData['medicineName'],
                'medicine_category' => $validatedData['medicineCategory'],
                'description' => $validatedData['medicineDescription'],
                'price' => $validatedData['medicinePrice'],
                'manufacturing_company' => $validatedData['manufacturingCompany'],
                'status' => $validatedData['medicineStatus'],
                'expiration_date' => $validatedData['expirationDate'],
                'quantity' => $validatedData['quantity'],
            ]);

            // Return a success response with the updated medicine details
            return response()->json([
                'message' => 'Medicine updated successfully',
                'updatedMedicine' => $medicine
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response
            Log::error('Error updating medicine: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error updating medicine',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v1/fetch-all-medicines",
     *     summary="Get all medicines",
     *     tags={"Medicines"},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved all medicines",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="medicines", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="medicine_category", type="string"),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="price", type="number", format="float"),
     *                 @OA\Property(property="manufacturing_company", type="string"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="expiration_date", type="string", format="date"),
     *                 @OA\Property(property="quantity", type="integer"),
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching medicines",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getAllMedicines()
    {
        // Fetch all medicines from the database
        try {
            $medicines = Medicine::all();
            // Return the list of medicines
            return response()->json([
                'medicines' => $medicines,
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a response indicating an error occurred
            Log::error('Error fetching medicines: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching medicines',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
