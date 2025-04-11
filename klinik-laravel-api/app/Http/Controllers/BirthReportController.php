<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\BirthReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class BirthReportController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/v1/add-birth-report",
     *     summary="Add a new birth report",
     *     tags={"Birth Reports"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="birthDetails", type="string"),
     *             @OA\Property(property="birthDate", type="string", format="date"),
     *             @OA\Property(property="patientEmail", type="string", format="email"),
     *             @OA\Property(property="doctorEmail", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Birth report added successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="newBirthReport", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="details", type="string"),
     *                 @OA\Property(property="date", type="string"),
     *                 @OA\Property(property="patient_name", type="string"),
     *                 @OA\Property(property="doctor_name", type="string"),
     *                 @OA\Property(property="patient_email", type="string"),
     *                 @OA\Property(property="doctor_email", type="string"),
     *                 @OA\Property(property="doctor_id", type="integer"),
     *                 @OA\Property(property="patient_id", type="integer")
     *             ),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=406,
     *         description="Patient or doctor not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while adding birth report",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function addBirthReport(Request $request)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'birthDetails' => 'required|string',
                'birthDate' => 'required|date',
                'patientEmail' => 'required|email|max:255',
                'doctorEmail' => 'required|email|max:255',
            ]);

            // Find the patient by email
            $patient = Patient::where('email', $validatedData['patientEmail'])->first();

            if (!$patient) {
                return response()->json([
                    'error' => 'Can\'t find the patient email in the patients table!',
                ], 406);
            }

            // Find the doctor by email
            $doctor = Doctor::where('email', $validatedData['doctorEmail'])->first();
            if (!$doctor) {
                return response()->json([
                    'error' => 'Can\'t find the doctor email in the doctors table!',
                ], 406);
            }

            // Create a new birth report
            $newBirthReport = BirthReport::create([
                'details' => $validatedData['birthDetails'],
                'date' => $validatedData['birthDate'],
                'patient_name' => $patient->name,
                'doctor_name' => $doctor->name,
                'patient_email' => $validatedData['patientEmail'],
                'doctor_email' => $validatedData['doctorEmail'],
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id
            ]);

            return response()->json([
                'newBirthReport' => $newBirthReport,
                'message' => 'Birth report added successfully',
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a JSON response with the error message
            Log::error('Error adding birth report: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error adding birth report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-birth-reports",
     *     summary="Search for birth reports by patient or doctor details",
     *     tags={"Birth Reports"},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         required=true,
     *         description="Query string to search for birth reports",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of searched birth reports",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", 
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="details", type="string"),
     *                     @OA\Property(property="date", type="string"),
     *                     @OA\Property(property="patient_name", type="string"),
     *                     @OA\Property(property="patient_email", type="string"),
     *                     @OA\Property(property="doctor_name", type="string"),
     *                     @OA\Property(property="doctor_email", type="string")
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching searched birth reports",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getSearchedBirthReports(Request $request)
    {
        try {
            $searchQuery = $request->query('search_query');
            $birthReports = BirthReport::where('patient_name', 'like', '%' . $searchQuery . '%')
                ->orWhere('patient_email', 'like', '%' . $searchQuery . '%')
                ->orWhere('doctor_name', 'like', '%' . $searchQuery . '%')
                ->orWhere('doctor_email', 'like', '%' . $searchQuery . '%')
                ->select(
                    'id',
                    'details',
                    'date',
                    'patient_name',
                    'patient_email',
                    'doctor_name',
                    'doctor_email',
                )
                ->paginate();

            return response()->json([
                'data' => $birthReports->items(),
                'current_page' => $birthReports->currentPage(),
                'last_page' => $birthReports->lastPage(),
                'total' => $birthReports->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a JSON response with the error message
            Log::error('Error fetching birth reports: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching searched birth reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-birth-reports",
     *     summary="Retrieve paginated birth reports",
     *     tags={"Birth Reports"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of birth reports",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", 
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="details", type="string"),
     *                     @OA\Property(property="date", type="string"),
     *                     @OA\Property(property="patient_name", type="string"),
     *                     @OA\Property(property="patient_email", type="string"),
     *                     @OA\Property(property="doctor_name", type="string"),
     *                     @OA\Property(property="doctor_email", type="string")
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching birth reports",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getBirthReports(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 15);

            $birthReports = BirthReport::select(
                'id',
                'details',
                'date',
                'patient_name',
                'patient_email',
                'doctor_name',
                'doctor_email',
            )
                ->paginate($perPage);

            return response()->json([
                'data' => $birthReports->items(),
                'current_page' => $birthReports->currentPage(),
                'last_page' => $birthReports->lastPage(),
                'total' => $birthReports->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a JSON response with the error message
            Log::error('Error fetching birth reports: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching birth reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/delete-birth-report",
     *     summary="Delete a birth report by ID",
     *     tags={"Birth Reports"},
     *     @OA\Parameter(
     *         name="birthReportId",
     *         in="query",
     *         required=true,
     *         description="ID of the birth report to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Birth report deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Birth report not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while deleting the birth report",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function deleteBirthReport(Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'birthReportId' => 'required|integer|exists:birth_reports,id',
        ]);

        // Extract the validated birth report ID
        $birthReportId = $validatedData['birthReportId'];

        try {
            // Find the birth report by ID and delete it
            $birthReport = BirthReport::findOrFail($birthReportId);
            $birthReport->delete();

            // Return a success response
            return response()->json([
                'status' => 'success',
                'message' => 'Birth report deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return a not found error response if the birth report does not exist
            return response()->json([
                'status' => 'error',
                'message' => 'Birth report not found',
            ], 404);
        } catch (\Exception $e) {
            // Return a generic error response for any other exceptions
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the birth report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/update-birth-report",
     *     summary="Update an existing birth report",
     *     tags={"Birth Reports"},
     *     @OA\Parameter(
     *         name="birthReportId",
     *         in="path",
     *         required=true,
     *         description="ID of the birth report to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"birthReportId", "birthDetails", "date", "patientEmail", "doctorEmail"},
     *             @OA\Property(property="birthReportId", type="integer"),
     *             @OA\Property(property="birthDetails", type="string"),
     *             @OA\Property(property="date", type="string", format="date"),
     *             @OA\Property(property="patientEmail", type="string"),
     *             @OA\Property(property="doctorEmail", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully updated the birth report",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="updatedBirthReport", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="details", type="string"),
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="patient_name", type="string"),
     *                 @OA\Property(property="patient_email", type="string"),
     *                 @OA\Property(property="doctor_name", type="string"),
     *                 @OA\Property(property="doctor_email", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=406,
     *         description="Error finding patient or doctor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error updating birth report",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function updateBirthReport(Request $request)
    {
        try {
            // Validate the incoming request data
            $validatedData = $request->validate([
                'birthReportId' => 'required|integer|exists:birth_reports,id',
                'birthDetails' => 'required|string',
                'date' => 'required|date',
                'patientEmail' => 'required|email|max:255',
                'doctorEmail' => 'required|email|max:255',
            ]);

            // Extract the validated data
            $birthReportId = $validatedData['birthReportId'];
            $birthReport = BirthReport::findOrFail($birthReportId);

            // Find the patient and doctor by email
            $patient = Patient::where('email', $validatedData['patientEmail'])->first();
            $doctor = Doctor::where('email', $validatedData['doctorEmail'])->first();

            if (!$patient) {
                return response()->json([
                    'error' => 'Can\'t find the patient email in the patients table!',
                ], 406);
            }

            if (!$doctor) {
                return response()->json([
                    'error' => 'Can\'t find the doctor email in the doctors table!',
                ], 406);
            }

            // Update the birth report with the validated data
            $birthReport->update([
                'date' => $validatedData['date'],
                'patient_name' => $patient->name,
                'patient_email' => $validatedData['patientEmail'],
                'doctor_name' => $doctor->name,
                'doctor_email' => $validatedData['doctorEmail'],
                'details' => $validatedData['birthDetails'],
            ]);

            // Return a success response with the updated birth report
            return response()->json([
                'message' => 'Birth report updated successfully',
                'updatedBirthReport' => $birthReport
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a JSON response with the error message
            Log::error('Error updating birth report: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error updating birth report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-all-birth-reports",
     *     summary="Retrieve all birth reports",
     *     tags={"Birth Reports"},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved all birth reports",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="birthReports", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="details", type="string"),
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="patient_name", type="string"),
     *                 @OA\Property(property="patient_email", type="string"),
     *                 @OA\Property(property="doctor_name", type="string"),
     *                 @OA\Property(property="doctor_email", type="string")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching birth reports",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getAllBirthReports()
    {
        try {
            // Retrieve all birth reports
            $birthReports = BirthReport::all();

            // Return a success response with all birth reports
            return response()->json([
                'birthReports' => $birthReports,
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a JSON response with the error message
            Log::error('Error fetching birth reports: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching birth reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
