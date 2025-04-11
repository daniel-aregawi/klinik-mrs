<?php

namespace App\Http\Controllers;

use App\Models\Nurse;
use App\Models\Patient;
use App\Models\Vaccine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class VaccineController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/v1/add-vaccines",
     *     summary="Add a vaccine record",
     *     tags={"Vaccines"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"patientEmail", "nurseEmail", "vaccineName", "doseNumber", "dateGiven"},
     *             @OA\Property(property="patientEmail", type="string", format="email", description="Email of the patient"),
     *             @OA\Property(property="nurseEmail", type="string", format="email", description="Email of the nurse"),
     *             @OA\Property(property="vaccineName", type="string", description="Name of the vaccine"),
     *             @OA\Property(property="serialNumber", type="string", description="Serial number of the vaccine"),
     *             @OA\Property(property="doseNumber", type="string", description="Dose number"),
     *             @OA\Property(property="dateGiven", type="string", format="date", description="Date when the vaccine was given"),
     *             @OA\Property(property="note", type="string", description="Additional notes"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vaccine added successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="newVaccine", type="object", 
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="patient_id", type="integer"),
     *                 @OA\Property(property="given_by", type="integer"),
     *                 @OA\Property(property="patient_name", type="string"),
     *                 @OA\Property(property="nurse_name", type="string"),
     *                 @OA\Property(property="vaccine_name", type="string"),
     *                 @OA\Property(property="serial_number", type="string"),
     *                 @OA\Property(property="dose_number", type="string"),
     *                 @OA\Property(property="date_given", type="string", format="date"),
     *                 @OA\Property(property="note", type="string"),
     *                 @OA\Property(property="patient_email", type="string"),
     *                 @OA\Property(property="nurse_email", type="string"),
     *             ),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=406,
     *         description="Error finding patient or nurse",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while adding vaccine",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function addVaccine(Request $request)
    {
        try {
            // Validate the incoming request data
            $validatedData = $request->validate([
                'patientEmail' => 'required|email',
                'nurseEmail' => 'required|email',
                'vaccineName' => 'required|string|max:255',
                'serialNumber' => 'nullable|string|max:255',
                'doseNumber' => 'required|max:255',
                'dateGiven' => 'required|date',
                'note' => 'nullable|string',
            ]);

            // Find the patient by email
            $patient = Patient::where('email', $validatedData['patientEmail'])->first();
            if (!$patient) {
                return response()->json([
                    'error' => 'Can\'t find the patient email in the patients table!',
                ], 406);
            }

            // Find the nurse by email
            $nurse = Nurse::where('email', $validatedData['nurseEmail'])->first();
            if (!$nurse) {
                return response()->json([
                    'error' => 'Can\'t find the nurse email in the nurses table!',
                ], 406);
            }

            // Create a new vaccine record
            $newVaccine = Vaccine::create([
                'patient_id' => $patient->id,
                'given_by' => $nurse->id,
                'patient_name' => $patient->name,
                'nurse_name' => $nurse->name,
                'vaccine_name' => $validatedData['vaccineName'],
                'serial_number' => $validatedData['serialNumber'],
                'dose_number' => $validatedData['doseNumber'],
                'date_given' => $validatedData['dateGiven'],
                'note' => $validatedData['note'],
                'patient_email' => $validatedData['patientEmail'],
                'nurse_email' => $validatedData['nurseEmail']
            ]);

            // Return a success response with the newly created vaccine
            return response()->json([
                'newVaccine' => $newVaccine,
                'message' => 'Vaccine added successfully',
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error adding vaccine: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error adding vaccine',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-vaccines",
     *     summary="Search for vaccines",
     *     tags={"Vaccines"},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         required=true,
     *         description="Query string to search for vaccines",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vaccines retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="patient_id", type="integer"),
     *                 @OA\Property(property="given_by", type="integer"),
     *                 @OA\Property(property="patient_name", type="string"),
     *                 @OA\Property(property="nurse_name", type="string"),
     *                 @OA\Property(property="vaccine_name", type="string"),
     *                 @OA\Property(property="serial_number", type="string"),
     *                 @OA\Property(property="dose_number", type="string"),
     *                 @OA\Property(property="date_given", type="string", format="date"),
     *                 @OA\Property(property="note", type="string"),
     *                 @OA\Property(property="patient_email", type="string", format="email"),
     *                 @OA\Property(property="nurse_email", type="string", format="email")
     *             )),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching searched vaccines",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getSearchedVaccines(Request $request)
    {
        try {
            // Retrieve the search query from the request
            $searchQuery = $request->query('search_query');

            // Search for vaccines based on the query and paginate results
            $vaccines = Vaccine::where('patient_name', 'like', '%' . $searchQuery . '%')
                ->orWhere('nurse_name', 'like', '%' . $searchQuery . '%')
                ->orWhere('vaccine_name', 'like', '%' . $searchQuery . '%')
                ->select(
                    'id',
                    'patient_id',
                    'given_by',
                    'patient_name',
                    'nurse_name',
                    'vaccine_name',
                    'serial_number',
                    'dose_number',
                    'date_given',
                    'note',
                    'patient_email',
                    'nurse_email'
                )
                ->paginate();

            // Return the paginated vaccine data
            return response()->json([
                'data' => $vaccines->items(),
                'current_page' => $vaccines->currentPage(),
                'last_page' => $vaccines->lastPage(),
                'total' => $vaccines->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching vaccines: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching searched vaccines',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-vaccines",
     *     summary="Get paginated list of vaccines",
     *     tags={"Vaccines"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vaccines retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="vaccine_name", type="string"),
     *                 @OA\Property(property="serial_number", type="string"),
     *                 @OA\Property(property="dose_number", type="string"),
     *                 @OA\Property(property="date_given", type="string", format="date"),
     *                 @OA\Property(property="note", type="string"),
     *                 @OA\Property(property="nurse_name", type="string"),
     *                 @OA\Property(property="patient_name", type="string"),
     *                 @OA\Property(property="patient_email", type="string", format="email"),
     *                 @OA\Property(property="nurse_email", type="string", format="email")
     *             )),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching vaccines",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getVaccines(Request $request)
    {
        try {
            // Get the number of items per page from the request or default to 15
            $perPage = $request->query('per_page', 15);

            // Retrieve all vaccines and paginate results
            $vaccines = Vaccine::select(
                'id',
                'vaccine_name',
                'serial_number',
                'dose_number',
                'date_given',
                'note',
                'nurse_name',
                'patient_name',
                'patient_email',
                'nurse_email'
            )
                ->paginate($perPage);

            // Return the paginated vaccine data
            return response()->json([
                'data' => $vaccines->items(),
                'current_page' => $vaccines->currentPage(),
                'last_page' => $vaccines->lastPage(),
                'total' => $vaccines->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching vaccines: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching vaccines',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/delete-vaccine",
     *     summary="Delete a vaccine record",
     *     tags={"Vaccines"},
     *     @OA\Parameter(
     *         name="vaccineId",
     *         in="query",
     *         required=true,
     *         description="ID of the vaccine to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vaccine deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vaccine not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while deleting the vaccine",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function deleteVaccine(Request $request)
    {
        // Validate the incoming request data to ensure a valid vaccine ID is provided
        $validatedData = $request->validate([
            'vaccineId' => 'required|integer|exists:vaccines,id',
        ]);

        $vaccineId = $validatedData['vaccineId'];

        try {
            // Find the vaccine by ID and delete it
            $vaccine = Vaccine::findOrFail($vaccineId);
            $vaccine->delete();

            // Return a success response
            return response()->json([
                'status' => 'success',
                'message' => 'Vaccine deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return an error response if the vaccine is not found
            return response()->json([
                'status' => 'error',
                'message' => 'Vaccine not found',
            ], 404);
        } catch (\Exception $e) {
            // Return a general error response for other exceptions
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the vaccine',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/update-vaccine",
     *     summary="Update a vaccine record",
     *     tags={"Vaccines"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"vaccineId", "patientEmail", "nurseEmail", "vaccineName", "doseNumber", "dateGiven"},
     *             @OA\Property(property="vaccineId", type="integer", example=1),
     *             @OA\Property(property="patientEmail", type="string", example="patient@example.com"),
     *             @OA\Property(property="nurseEmail", type="string", example="nurse@example.com"),
     *             @OA\Property(property="vaccineName", type="string", example="COVID-19 Vaccine"),
     *             @OA\Property(property="serialNumber", type="string", example="12345"),
     *             @OA\Property(property="doseNumber", type="string", example="1"),
     *             @OA\Property(property="dateGiven", type="string", format="date", example="2024-09-21"),
     *             @OA\Property(property="note", type="string", example="No adverse reactions observed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vaccine updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=406,
     *         description="Patient or nurse not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while updating the vaccine",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function updateVaccine(Request $request)
    {
        try {
            // Validate the incoming request data
            $validatedData = $request->validate([
                'vaccineId' => 'required|integer|exists:vaccines,id',
                'patientEmail' => 'required|email',
                'nurseEmail' => 'required|email',
                'vaccineName' => 'required|string|max:255',
                'serialNumber' => 'nullable|string|max:255',
                'doseNumber' => 'required|max:255',
                'dateGiven' => 'required|date',
                'note' => 'nullable|string',
            ]);

            // Find the patient by email
            $patient = Patient::where('email', $validatedData['patientEmail'])->first();
            if (!$patient) {
                return response()->json([
                    'error' => 'Can\'t find the patient email in the patients table!',
                ], 406);
            }

            // Find the nurse by email
            $nurse = Nurse::where('email', $validatedData['nurseEmail'])->first();
            if (!$nurse) {
                return response()->json([
                    'error' => 'Can\'t find the nurse email in the nurses table!',
                ], 406);
            }

            // Find the vaccine by ID and update it with new data
            $vaccineId = $validatedData['vaccineId'];
            $vaccine = Vaccine::findOrFail($vaccineId);

            $vaccine->update([
                'patient_id' => $patient->id,
                'given_by' => $nurse->id,
                'patient_name' => $patient->name,
                'nurse_name' => $nurse->name,
                'vaccine_name' => $validatedData['vaccineName'],
                'serial_number' => $validatedData['serialNumber'],
                'dose_number' => $validatedData['doseNumber'],
                'date_given' => $validatedData['dateGiven'],
                'note' => $validatedData['note'],
            ]);

            // Return a success response with the updated vaccine data
            return response()->json([
                'message' => 'Vaccine updated successfully',
                'updatedVaccine' => $vaccine
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error updating vaccine: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error updating vaccine',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-all-vaccines",
     *     summary="Retrieve all vaccines",
     *     tags={"Vaccines"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of all vaccines",
     *         @OA\JsonContent(
     *             type="object",
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching vaccines",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getAllVaccines()
    {
        try {
            // Retrieve all vaccine records
            $vaccines = Vaccine::all();

            // Return a success response with all vaccine data
            return response()->json([
                'vaccines' => $vaccines,
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching vaccines: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching vaccines',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
