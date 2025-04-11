<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\DeathReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class DeathReportController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/v1/add-death-report",
     *     summary="Add a new death report",
     *     tags={"Death Reports"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"deathDetails", "deathDate", "patientEmail", "doctorEmail"},
     *             @OA\Property(property="deathDetails", type="string"),
     *             @OA\Property(property="deathDate", type="string", format="date"),
     *             @OA\Property(property="patientEmail", type="string"),
     *             @OA\Property(property="doctorEmail", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully added the death report",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="newDeathReport", type="object",
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
     *         description="Error adding death report",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function addDeathReport(Request $request)
    {
        try {
            // Validate incoming request data
            $validatedData = $request->validate([
                'deathDetails' => 'required|string',
                'deathDate' => 'required|date',
                'patientEmail' => 'required|email|max:255',
                'doctorEmail' => 'required|email|max:255',
            ]);

            // Find the patient by email
            $patient = Patient::where('email', $validatedData['patientEmail'])->first();

            // Check if the patient exists, return error if not found
            if (!$patient) {
                return response()->json([
                    'error' => 'Can\'t find the patient email in the patients table!',
                ], 406);
            }

            // Find the doctor by email
            $doctor = Doctor::where('email', $validatedData['doctorEmail'])->first();

            // Check if the doctor exists, return error if not found
            if (!$doctor) {
                return response()->json([
                    'error' => 'Can\'t find the doctor email in the doctors table!',
                ], 406);
            }

            // Create a new death report with validated data and related patient and doctor info
            $newDeathReport = DeathReport::create([
                'details' => $validatedData['deathDetails'],
                'date' => $validatedData['deathDate'],
                'patient_name' => $patient->name,
                'doctor_name' => $doctor->name,
                'patient_email' => $validatedData['patientEmail'],
                'doctor_email' => $validatedData['doctorEmail'],
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id
            ]);

            // Return a success response with the created death report
            return response()->json([
                'newDeathReport' => $newDeathReport,
                'message' => 'Death report added successfully',
            ], 200);
        } catch (\Exception $e) {
            // Log error and return a generic error response
            Log::error('Error adding death report: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error adding death report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-death-reports",
     *     summary="Search for death reports",
     *     tags={"Death Reports"},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         required=true,
     *         description="Search term to filter death reports",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved searched death reports",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="details", type="string"),
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="patient_name", type="string"),
     *                 @OA\Property(property="patient_email", type="string"),
     *                 @OA\Property(property="doctor_name", type="string"),
     *                 @OA\Property(property="doctor_email", type="string")
     *             )),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching searched death reports",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getSearchedDeathReports(Request $request)
    {
        try {
            // Get search query from request
            $searchQuery = $request->query('search_query');

            // Fetch death reports that match the search query in any relevant field
            $deathReports = DeathReport::where('patient_name', 'like', '%' . $searchQuery . '%')
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
                    'doctor_email'
                )
                ->paginate();

            // Return paginated search results
            return response()->json([
                'data' => $deathReports->items(),
                'current_page' => $deathReports->currentPage(),
                'last_page' => $deathReports->lastPage(),
                'total' => $deathReports->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log error and return a generic error response
            Log::error('Error fetching death reports: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching searched death reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-death-reports",
     *     summary="Retrieve paginated death reports",
     *     tags={"Death Reports"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of results per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved death reports",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="details", type="string"),
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="patient_name", type="string"),
     *                 @OA\Property(property="patient_email", type="string"),
     *                 @OA\Property(property="doctor_name", type="string"),
     *                 @OA\Property(property="doctor_email", type="string")
     *             )),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching death reports",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getDeathReports(Request $request)
    {
        try {
            // Get the number of results per page from the request, default to 15 if not provided
            $perPage = $request->query('per_page', 15);

            // Fetch paginated death reports
            $deathReports = DeathReport::select(
                'id',
                'details',
                'date',
                'patient_name',
                'patient_email',
                'doctor_name',
                'doctor_email'
            )
                ->paginate($perPage);

            // Return paginated death reports
            return response()->json([
                'data' => $deathReports->items(),
                'current_page' => $deathReports->currentPage(),
                'last_page' => $deathReports->lastPage(),
                'total' => $deathReports->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log error and return a generic error response
            Log::error('Error fetching death reports: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching death reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/v1/delete-death-report",
     *     summary="Delete a death report",
     *     tags={"Death Reports"},
     *     @OA\Parameter(
     *         name="deathReportId",
     *         in="query",
     *         required=true,
     *         description="ID of the death report to be deleted",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully deleted the death report",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Death report deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Death report not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Death report not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while deleting the death report",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function deleteDeathReport(Request $request)
    {
        // Validate the request data to ensure 'deathReportId' is present and valid
        $validatedData = $request->validate([
            'deathReportId' => 'required|integer|exists:death_reports,id',
        ]);

        $deathReportId = $validatedData['deathReportId'];

        try {
            // Find the death report by ID or fail if not found
            $deathReport = DeathReport::findOrFail($deathReportId);
            // Delete the death report from the database
            $deathReport->delete();

            // Return a success response
            return response()->json([
                'status' => 'success',
                'message' => 'Death report deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return an error response if the death report is not found
            return response()->json([
                'status' => 'error',
                'message' => 'Death report not found',
            ], 404);
        } catch (\Exception $e) {
            // Return a generic error response for other exceptions
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the death report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/update-death-report",
     *     summary="Update a death report",
     *     tags={"Death Reports"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"deathReportId", "deathDetails", "date", "patientEmail", "doctorEmail"},
     *             @OA\Property(property="deathReportId", type="integer", description="ID of the death report to update"),
     *             @OA\Property(property="deathDetails", type="string", description="Details of the death report"),
     *             @OA\Property(property="date", type="string", format="date", description="Date of death"),
     *             @OA\Property(property="patientEmail", type="string", format="email", description="Email of the patient"),
     *             @OA\Property(property="doctorEmail", type="string", format="email", description="Email of the doctor"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Death report updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="updatedDeathReport", type="object", 
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="details", type="string"),
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="patient_name", type="string"),
     *                 @OA\Property(property="patient_email", type="string"),
     *                 @OA\Property(property="doctor_name", type="string"),
     *                 @OA\Property(property="doctor_email", type="string"),
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
     *         description="Error occurred while updating death report",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function updateDeathReport(Request $request)
    {
        try {
            // Validate incoming request data
            $validatedData = $request->validate([
                'deathReportId' => 'required|integer|exists:death_reports,id',
                'deathDetails' => 'required|string',
                'date' => 'required|date',
                'patientEmail' => 'required|email|max:255',
                'doctorEmail' => 'required|email|max:255',
            ]);

            $deathReportId = $validatedData['deathReportId'];
            // Find the death report by ID or fail if not found
            $deathReport = DeathReport::findOrFail($deathReportId);

            // Find the patient and doctor by their email addresses
            $patient = Patient::where('email', $validatedData['patientEmail'])->first();
            $doctor = Doctor::where('email', $validatedData['doctorEmail'])->first();

            // Check if the patient exists, return error if not found
            if (!$patient) {
                return response()->json([
                    'error' => 'Can\'t find the patient email in the patients table!',
                ], 406);
            }

            // Check if the doctor exists, return error if not found
            if (!$doctor) {
                return response()->json([
                    'error' => 'Can\'t find the doctor email in the doctors table!',
                ], 406);
            }

            // Update the death report with validated data
            $deathReport->update([
                'date' => $validatedData['date'],
                'patient_name' => $patient->name,
                'patient_email' => $validatedData['patientEmail'],
                'doctor_name' => $doctor->name,
                'doctor_email' => $validatedData['doctorEmail'],
                'details' => $validatedData['deathDetails'],
            ]);

            // Return a success response with the updated death report
            return response()->json([
                'message' => 'Death report updated successfully',
                'updatedDeathReport' => $deathReport
            ], 200);
        } catch (\Exception $e) {
            // Log error and return a generic error response
            Log::error('Error updating death report: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error updating death report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v1/fetch-all-death-reports",
     *     summary="Retrieve all death reports",
     *     tags={"Death Reports"},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved all death reports",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="deathReports", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="details", type="string"),
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="patient_name", type="string"),
     *                 @OA\Property(property="patient_email", type="string"),
     *                 @OA\Property(property="doctor_name", type="string"),
     *                 @OA\Property(property="doctor_email", type="string"),
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching death reports",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getAllDeathReports()
    {
        try {
            // Retrieve all death reports from the database
            $deathReports = DeathReport::all();

            // Return the death reports in a success response
            return response()->json([
                'deathReports' => $deathReports,
            ], 200);
        } catch (\Exception $e) {
            // Log error and return a generic error response
            Log::error('Error fetching death reports: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching death reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
