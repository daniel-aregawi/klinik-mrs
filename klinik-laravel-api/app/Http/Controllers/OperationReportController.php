<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Models\OperationReport;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;


class OperationReportController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/v1/add-operation-report",
     *     summary="Add an operation report",
     *     description="Creates a new operation report for a patient.",
     *     operationId="addOperationReport",
     *     tags={"Operation Reports"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"operationDetails", "operationDate", "patientEmail", "doctorEmail", "departmentId"},
     *             @OA\Property(property="operationDetails", type="string", example="Appendectomy"),
     *             @OA\Property(property="operationDate", type="string", format="date", example="2024-09-20"),
     *             @OA\Property(property="patientEmail", type="string", example="patient@example.com"),
     *             @OA\Property(property="doctorEmail", type="string", example="doctor@example.com"),
     *             @OA\Property(property="departmentId", type="integer", example=1),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operation report added successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="newOperationReport", type="object"),
     *             @OA\Property(property="message", type="string", example="Operation report added successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=406,
     *         description="Patient or doctor not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Can't find the patient email in the patients table!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error adding operation report",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error adding operation report"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function addOperationReport(Request $request)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'operationDetails' => 'required|string',
                'operationDate' => 'required|date',
                'patientEmail' => 'required|email|max:255',
                'doctorEmail' => 'required|email|max:255',
                'departmentId' => 'required|integer|exists:departments,id',
            ]);

            // Find the patient by email or return an error if not found
            $patient = Patient::where('email', $validatedData['patientEmail'])->first();
            if (!$patient) {
                return response()->json([
                    'error' => 'Can\'t find the patient email in the patients table!',
                ], 406);
            }

            // Find the doctor by email or return an error if not found
            $doctor = Doctor::where('email', $validatedData['doctorEmail'])->first();
            if (!$doctor) {
                return response()->json([
                    'error' => 'Can\'t find the doctor email in the doctors table!',
                ], 406);
            }

            // Create a new operation report record
            $newOperationReport = OperationReport::create([
                'operation_details' => $validatedData['operationDetails'],
                'date' => $validatedData['operationDate'],
                'patient_name' => $patient->name,
                'doctor_name' => $doctor->name,
                'patient_email' => $validatedData['patientEmail'],
                'doctor_email' => $validatedData['doctorEmail'],
                'department_id' => $validatedData['departmentId'],
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id
            ]);

            // Retrieve the department name and add it to the report
            $newOperationReport['department_name'] = Department::where('id', $validatedData['departmentId'])
                ->select('name')
                ->first()
                ->name;

            // Return a success response with the new report data
            return response()->json([
                'newOperationReport' => $newOperationReport,
                'message' => 'Operation report added successfully',
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response
            Log::error('Error adding operation report: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error adding operation report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-operation-reports",
     *     summary="Get searched operation reports",
     *     tags={"Operation Reports"},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Search query for patient or doctor names or emails"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of operation reports",
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching operation reports",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getSearchedOperationReports(Request $request)
    {
        try {
            // Get the search query parameter
            $searchQuery = $request->query('search_query');

            // Retrieve operation reports that match the search query
            $operationReports = OperationReport::where('patient_name', 'like', '%' . $searchQuery . '%')
                ->orWhere('patient_email', 'like', '%' . $searchQuery . '%')
                ->orWhere('doctor_name', 'like', '%' . $searchQuery . '%')
                ->orWhere('doctor_email', 'like', '%' . $searchQuery . '%')
                ->select(
                    'id',
                    'operation_details',
                    'date',
                    'patient_name',
                    'patient_email',
                    'doctor_name',
                    'doctor_email',
                    'department_id'
                )
                ->with('department:id,name') // Eager load the department name
                ->paginate(); // Paginate the results

            // Transform each report to include the department name
            $operationReports->getCollection()->transform(function ($report) {
                $report->department_name = $report->department->name;
                return $report;
            });

            // Return a success response with paginated report data
            return response()->json([
                'data' => $operationReports->items(), // Current page of reports
                'current_page' => $operationReports->currentPage(), // Current page number
                'last_page' => $operationReports->lastPage(), // Last page number
                'total' => $operationReports->total(), // Total number of reports
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response
            Log::error('Error fetching operation reports: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching searched operation reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-operation-reports",
     *     summary="Get paginated operation reports",
     *     tags={"Operation Reports"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", default=15),
     *         description="Number of items per page"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of operation reports",
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching operation reports",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getOperationReports(Request $request)
    {
        try {
            // Get the number of items per page from the request, default to 15
            $perPage = $request->query('per_page', 15);

            // Retrieve paginated operation reports with related department name
            $operationReports = OperationReport::select(
                'id',
                'operation_details',
                'date',
                'patient_name',
                'patient_email',
                'doctor_name',
                'doctor_email',
                'department_id'
            )
                ->with('department:id,name') // Eager load the department name
                ->paginate($perPage); // Paginate results based on 'per_page'

            // Transform each report to include the department name
            $operationReports->getCollection()->transform(function ($report) {
                $report->department_name = $report->department->name;
                return $report;
            });

            // Return a success response with paginated report data
            return response()->json([
                'data' => $operationReports->items(), // Current page of reports
                'current_page' => $operationReports->currentPage(), // Current page number
                'last_page' => $operationReports->lastPage(), // Last page number
                'total' => $operationReports->total(), // Total number of reports
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response
            Log::error('Error fetching operation reports: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching operation reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/delete-operation-report",
     *     summary="Delete an operation report",
     *     tags={"Operation Reports"},
     *     @OA\Parameter(
     *         name="operationReportId",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID of the operation report to delete"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful deletion of the operation report",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Operation report not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while deleting the operation report",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function deleteOperationReport(Request $request)
    {
        // Validate the request to ensure operationReportId is provided and exists
        $validatedData = $request->validate([
            'operationReportId' => 'required|integer|exists:operation_reports,id',
        ]);

        $operationReportId = $validatedData['operationReportId'];

        try {
            // Find the operation report by ID or throw an exception if not found
            $operationReport = OperationReport::findOrFail($operationReportId);
            // Delete the found operation report
            $operationReport->delete();

            // Return a success response
            return response()->json([
                'status' => 'success',
                'message' => 'Operation report deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return a 404 response if the operation report is not found
            return response()->json([
                'status' => 'error',
                'message' => 'Operation report not found',
            ], 404);
        } catch (\Exception $e) {
            // Return a 500 response for any other errors
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the operation report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/update-operation-report",
     *     summary="Update an existing operation report",
     *     tags={"Operation Reports"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"operationReportId", "date", "patient_email", "doctor_email", "department_name", "operation_details", "department_id"},
     *             @OA\Property(property="operationReportId", type="integer"),
     *             @OA\Property(property="date", type="string", format="date"),
     *             @OA\Property(property="patient_email", type="string"),
     *             @OA\Property(property="doctor_email", type="string"),
     *             @OA\Property(property="department_name", type="string"),
     *             @OA\Property(property="operation_details", type="string"),
     *             @OA\Property(property="department_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operation report updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="updatedOperationReport", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=406,
     *         description="Error when patient or doctor email is not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while updating operation report",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function updateOperationReport(Request $request)
    {
        try {
            // Validate the request data for updating an operation report
            $validatedData = $request->validate([
                'operationReportId' => 'required|integer|exists:operation_reports,id',
                'date' => 'required|date',
                'patient_email' => 'required|email|max:255',
                'doctor_email' => 'required|email|max:255',
                'department_name' => 'required|string|max:255',
                'operation_details' => 'required|string',
                'department_id' => 'required|integer|exists:departments,id',
            ]);

            $operationReportId = $validatedData['operationReportId'];
            // Find the operation report by ID or throw an exception if not found
            $operationReport = OperationReport::findOrFail($operationReportId);

            // Find the related patient, doctor, and department
            $patient = Patient::where('email', $validatedData['patient_email'])->first();
            $doctor = Doctor::where('email', $validatedData['doctor_email'])->first();
            $department = Department::where('id', $validatedData['department_id'])->first();

            // Return an error if the patient or doctor is not found
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

            // Update the operation report with validated data
            $operationReport->update([
                'date' => $validatedData['date'],
                'patient_name' => $patient->name,
                'patient_email' => $validatedData['patient_email'],
                'doctor_name' => $doctor->name,
                'doctor_email' => $validatedData['doctor_email'],
                'department_id' => $department->id,
                'operation_details' => $validatedData['operation_details'],
            ]);

            // Add the department name to the updated report
            $operationReport['department_name'] = $department->name;

            // Return a success response with the updated operation report
            return response()->json([
                'message' => 'Operation report updated successfully',
                'updatedOperationReport' => $operationReport
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a 500 response for any exceptions
            Log::error('Error updating operation report: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error updating operation report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-departments-in-operation-reports",
     *     summary="Retrieve all departments",
     *     tags={"Operation Reports"},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved departments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="departments", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching departments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getAllDepartments()
    {
        try {
            // Retrieve all departments with their IDs and names
            $departments = Department::select('id', 'name')->get();

            // Return a success response with the list of departments
            return response()->json([
                'departments' => $departments,
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a 500 response for any exceptions
            Log::error('Error fetching departments: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching departments',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-all-operation-reports",
     *     summary="Retrieve all operation reports",
     *     tags={"Operation Reports"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of operation reports",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operationReports", type="array", @OA\Items(type="object")),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching operation reports",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getAllOperationReports()
    {
        try {
            // Retrieve all operation reports from the database
            $operationReports = OperationReport::all();

            // Return a success response with the list of operation reports
            return response()->json([
                'operationReports' => $operationReports,
            ], 200);
        } catch (\Exception $e) {
            // Log the error details for debugging
            Log::error('Error fetching operation reports: ', ['error' => $e->getMessage()]);

            // Return a 500 error response with a message indicating failure
            return response()->json([
                'message' => 'Error fetching operation reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
