<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\BedAllotment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class BedAllotmentController extends Controller
{


    /**
     * @OA\Post(
     *     path="/api/v1/add-bed-allotment",
     *     summary="Add a bed allotment record",
     *     tags={"Bed Allotments"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"bedNumber", "bedType", "allotmentDate", "patientEmail"},
     *             @OA\Property(property="bedNumber", type="integer", example=101),
     *             @OA\Property(property="bedType", type="string", example="Private"),
     *             @OA\Property(property="allotmentDate", type="string", format="date", example="2024-09-21"),
     *             @OA\Property(property="dischargeDate", type="string", format="date", example="2024-09-30"),
     *             @OA\Property(property="patientEmail", type="string", example="patient@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bed allotment added successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=406,
     *         description="Patient not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while adding the bed allotment",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function addBedAllotment(Request $request)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'bedNumber' => 'required|integer',
                'bedType' => 'required|string',
                'allotmentDate' => 'required|date',
                'dischargeDate' => 'nullable|date',
                'patientEmail' => 'required|email|max:255',
            ]);

            // Find the patient by email
            $patient = Patient::where('email', $validatedData['patientEmail'])->first();

            if (!$patient) {
                return response()->json([
                    'error' => 'Can\'t find the patient email in the patients table!',
                ], 406);
            }

            // Create a new bed allotment record
            $newBedAllotment = BedAllotment::create([
                'bed_number' => $validatedData['bedNumber'],
                'bed_type' => $validatedData['bedType'],
                'allotment_time' => $validatedData['allotmentDate'],
                'discharge_time' => $validatedData['dischargeDate'],
                'patient_email' => $validatedData['patientEmail'],
                'patient_id' => $patient->id,
                'patient_name' => $patient->name
            ]);

            // Return a successful response with the new bed allotment data
            return response()->json([
                'newBedAllotment' => $newBedAllotment,
                'message' => 'Bed allotment added successfully',
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a JSON response with the error message
            Log::error('Error adding bed allotment: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error adding bed allotment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-bed-allotments",
     *     summary="Search for bed allotments",
     *     tags={"Bed Allotments"},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         required=true,
     *         description="Query to search for patient name or email",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Searched bed allotments retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching searched bed allotments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getSearchedBedAllotments(Request $request)
    {
        try {
            $searchQuery = $request->query('search_query');

            // Fetch bed allotments matching the search query
            $bedAllotments = BedAllotment::where('patient_name', 'like', '%' . $searchQuery . '%')
                ->orWhere('patient_email', 'like', '%' . $searchQuery . '%')
                ->select(
                    'id',
                    'bed_number',
                    'bed_type',
                    'patient_name',
                    'patient_email',
                    'allotment_time',
                    'discharge_time'
                )
                ->paginate(2);

            // Return the paginated results
            return response()->json([
                'data' => $bedAllotments->items(),
                'current_page' => $bedAllotments->currentPage(),
                'last_page' => $bedAllotments->lastPage(),
                'total' => $bedAllotments->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a JSON response with the error message
            Log::error('Error fetching bed allotments: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching searched bed allotments',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-bed-allotments",
     *     summary="Retrieve paginated bed allotments",
     *     tags={"Bed Allotments"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated bed allotments retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching bed allotments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getBedAllotments(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 15);

            // Fetch paginated bed allotments
            $bedAllotments = BedAllotment::select(
                'id',
                'bed_number',
                'bed_type',
                'patient_name',
                'patient_email',
                'allotment_time',
                'discharge_time'
            )
                ->paginate($perPage);

            // Return the paginated results
            return response()->json([
                'data' => $bedAllotments->items(),
                'current_page' => $bedAllotments->currentPage(),
                'last_page' => $bedAllotments->lastPage(),
                'total' => $bedAllotments->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a JSON response with the error message
            Log::error('Error fetching bed allotments: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching bed allotments',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/delete-bed-allotment",
     *     summary="Delete a bed allotment",
     *     tags={"Bed Allotments"},
     *     @OA\Parameter(
     *         name="bedAllotmentId",
     *         in="path",
     *         required=true,
     *         description="ID of the bed allotment to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bed allotment deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bed allotment not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while deleting the bed allotment",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function deleteBedAllotment(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'bedAllotmentId' => 'required|integer|exists:bed_allotments,id',
        ]);

        $bedAllotmentId = $validatedData['bedAllotmentId'];

        try {
            // Find the bed allotment by ID and delete it
            $bedAllotment = BedAllotment::findOrFail($bedAllotmentId);
            $bedAllotment->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Bed allotment deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return a 404 error if the bed allotment is not found
            return response()->json([
                'status' => 'error',
                'message' => 'Bed allotment not found',
            ], 404);
        } catch (\Exception $e) {
            // Return a 500 error for any other exceptions
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the bed allotment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/update-bed-allotment",
     *     summary="Update a bed allotment",
     *     tags={"Bed Allotments"},
     *     @OA\Parameter(
     *         name="bedAllotmentId",
     *         in="path",
     *         required=true,
     *         description="ID of the bed allotment to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"bedNumber","bedType","allotmentDate","patientEmail"},
     *             @OA\Property(property="bedNumber", type="integer"),
     *             @OA\Property(property="bedType", type="string"),
     *             @OA\Property(property="allotmentDate", type="string", format="date"),
     *             @OA\Property(property="dischargeDate", type="string", format="date"),
     *             @OA\Property(property="patientEmail", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully updated the bed allotment",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=406,
     *         description="Can't find the patient email in the patients table",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while updating the bed allotment",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function updateBedAllotment(Request $request)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'bedAllotmentId' => 'required|integer|exists:bed_allotments,id',
                'bedNumber' => 'required|integer',
                'bedType' => 'required|string',
                'allotmentDate' => 'required|date',
                'dischargeDate' => 'nullable|date',
                'patientEmail' => 'required|email|max:255',
            ]);

            $bedAllotmentId = $validatedData['bedAllotmentId'];
            $bedAllotment = BedAllotment::findOrFail($bedAllotmentId);

            $patient = Patient::where('email', $validatedData['patientEmail'])->first();

            if (!$patient) {
                return response()->json([
                    'error' => 'Can\'t find the patient email in the patients table!',
                ], 406);
            }

            // Update the bed allotment with new data
            $bedAllotment->update([
                'bed_number' => $validatedData['bedNumber'],
                'bed_type' => $validatedData['bedType'],
                'allotment_time' => $validatedData['allotmentDate'],
                'discharge_time' => $validatedData['dischargeDate'],
                'patient_email' => $validatedData['patientEmail'],
                'patient_id' => $patient->id,
                'patient_name' => $patient->name
            ]);

            return response()->json([
                'message' => 'Bed allotment updated successfully',
                'updatedBedAllotment' => $bedAllotment
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a JSON response with the error message
            Log::error('Error updating bed allotment: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error updating bed allotment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/bed-allotments",
     *     summary="Get all bed allotments",
     *     tags={"Bed Allotments"},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved all bed allotments",
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching bed allotments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getAllBedAllotments()
    {
        try {
            // Retrieve all bed allotments
            $bedAllotments = BedAllotment::all();
            return response()->json([
                'bedAllotments' => $bedAllotments,
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a JSON response with the error message
            Log::error('Error fetching bed allotments: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching bed allotments',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
