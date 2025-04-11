<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Nurse;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Department;
use App\Models\Pharmacist;
use App\Models\Laboratorist;
use App\Models\StartEndTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;


class DoctorController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/add-doctor",
     *     summary="Add a new doctor",
     *     description="Creates a new doctor record after validating the input data.",
     *     operationId="addDoctor",
     *     tags={"Doctor"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", example="doctor@example.com"),
     *             @OA\Property(property="smallDescription", type="string", example="Experienced cardiologist"),
     *             @OA\Property(property="qualification", type="string", example="MD, Cardiology"),
     *             @OA\Property(property="appointmentPrice", type="number", format="float", example=100.50),
     *             @OA\Property(property="consultationPrice", type="number", format="float", example=50.00),
     *             @OA\Property(property="department", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Doctor created successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="newDoctor", type="object", example={
     *                 "id": 1,
     *                 "name": "John Doe",
     *                 "email": "doctor@example.com",
     *                 "small_description": "Experienced cardiologist",
     *                 "qualification": "MD, Cardiology",
     *                 "appointment_price": 100.50,
     *                 "consultation_price": 50.00,
     *                 "department_id": 1,
     *                 "department_name": "Cardiology"
     *             }),
     *             @OA\Property(property="message", type="string", example="Doctor created successfully!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or user role issue.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="First change the user role from the All Users section.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=406,
     *         description="Email already used in another table.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="This email is already used in the Laboratorists table!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Email not found in users!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error adding doctor.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error adding doctor"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */

    public function addDoctor(Request $request)
    {
        try {
            // Validate the incoming request data
            $validatedData = $request->validate([
                'email' => 'required|string|email|exists:users,email',
                'smallDescription' => 'required|string',
                'qualification' => 'required|string',
                'appointmentPrice' => 'required|numeric',
                'consultationPrice' => 'required|numeric',
                'department' => 'required|integer|exists:departments,id',
            ]);

            // Extract validated data
            $email = $validatedData['email'];
            $smallDescription = $validatedData['smallDescription'];
            $qualification = $validatedData['qualification'];
            $appointmentPrice = $validatedData['appointmentPrice'];
            $consultationPrice = $validatedData['consultationPrice'];
            $department = $validatedData['department'];

            // Check if the email exists in different tables
            $existingDoctorInUserTable = User::where('email', $email)->first();
            $existingDoctorInDoctorTable = Doctor::where('email', $email)->first();
            $checkIfLaboratorist = Laboratorist::where('email', $email)->first();
            $checkIfPharmacist = Pharmacist::where('email', $email)->first();
            $checkIfPatient = Patient::where('email', $email)->first();
            $checkIfNurse = Nurse::where('email', $email)->first();

            // Return appropriate error responses if the email is found in any table
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
            } elseif ($checkIfNurse) {
                return response()->json([
                    'error' => 'This email is already used in the Nurses table!',
                ], 406);
            } elseif ($existingDoctorInDoctorTable) {
                return response()->json([
                    'error' => 'Email is already used in the Doctors table!',
                ], 406);
            }

            // Ensure the user exists and has the doctor role
            if (!$existingDoctorInUserTable) {
                return response()->json([
                    'error' => 'Email not found in users!',
                ], 404);
            } elseif (!$existingDoctorInUserTable->is_doctor) {
                return response()->json([
                    'error' => 'First change the user role from the All Users section.',
                ], 400);
            }

            // Get the user and department details
            $user = User::where('email', $email)->first();
            $departmentId = Department::where('id', $department)->first();
            $departmentName = Department::where('id', $department)->pluck('name')->first();

            // Create a new doctor record
            $newDoctor = Doctor::create([
                'user_id' => $user->id,
                'department_id' => $departmentId->id,
                'name' => $user->name,
                'email' => $email,
                'small_description' => $smallDescription,
                'qualification' => $qualification,
                'appointment_price' => $appointmentPrice,
                'consultation_price' => $consultationPrice,
                'department_id' => $department,
                'department_name' => $departmentName
            ]);

            // Create associated StartEndTime record for the new doctor
            StartEndTime::create([
                'doctor_id' => $newDoctor->id
            ]);

            // Return success response with the newly created doctor data
            return response()->json([
                'newDoctor' => $newDoctor,
                'message' => 'Doctor created successfully!',
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a failure response
            Log::error('Error adding doctor: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error adding doctor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-doctors",
     *     summary="Search for doctors",
     *     description="Fetches a list of doctors based on a search query for their name or email.",
     *     operationId="getSearchedDoctors",
     *     tags={"Doctor"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         required=true,
     *         description="The search term to filter doctors by name or email.",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of doctors matching the search criteria.",
     *         @OA\JsonContent(
     *             @OA\Property(property="doctors", type="array", @OA\Items(type="object", example={
     *                 "id": 1,
     *                 "name": "John Doe",
     *                 "email": "doctor@example.com",
     *                 "avatar": "url_to_avatar",
     *                 "appointment_price": 100.50,
     *                 "consultation_price": 50.00,
     *                 "department_id": 1,
     *                 "department_name": "Cardiology"
     *             })),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=3),
     *             @OA\Property(property="total", type="integer", example=30)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching searched doctors.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching searched doctors"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */

    public function getSearchedDoctors(Request $request)
    {
        try {
            // Get the search query from the request
            $searchQuery = $request->query('search_query');

            // Fetch doctors based on search query, including related user and department details
            $doctors = Doctor::with('user:id,avatar')
                ->with('department:id,name')
                ->where('name', 'like', '%' . $searchQuery . '%')
                ->orWhere('email', 'like', '%' . $searchQuery . '%')
                ->paginate();

            // Map the results to include only necessary fields and return the response
            return response()->json([
                'doctors' => $doctors->map(function ($doctor) {
                    return [
                        'id' => $doctor->id,
                        'name' => $doctor->name,
                        'email' => $doctor->email,
                        'avatar' => $doctor->user->avatar ?? null,
                        'appointment_price' => $doctor->appointment_price,
                        'consultation_price' => $doctor->consultation_price,
                        'department_id' => $doctor->department_id,
                        'department_name' => $doctor->department ? $doctor->department->name : null,
                    ];
                }),
                'current_page' => $doctors->currentPage(),
                'last_page' => $doctors->lastPage(),
                'total' => $doctors->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a failure response
            Log::error('Error fetching doctors: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching searched doctors',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v1/fetch-doctors",
     *     summary="Get a list of doctors",
     *     description="Fetches a paginated list of doctors with their details, including user and department information.",
     *     operationId="getDoctors",
     *     tags={"Doctor"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             example=15
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="A paginated list of doctors.",
     *         @OA\JsonContent(
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="total", type="integer", example=50),
     *             @OA\Property(property="per_page", type="integer", example=15)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching doctors.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching doctors"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */

    public function getDoctors(Request $request)
    {
        try {
            // Determine the number of items per page from the request, defaulting to 15
            $perPage = $request->query('per_page', 15);

            // Fetch all doctors with related user and department details, paginated
            $doctors = Doctor::with('user:id,avatar')
                ->with('department:id,name')
                ->paginate($perPage);

            // Map the results to include only necessary fields and return the response
            return response()->json([
                'data' => $doctors->map(function ($doctor) {
                    return [
                        'id' => $doctor->id,
                        'name' => $doctor->name,
                        'email' => $doctor->email,
                        'avatar' => $doctor->user->avatar ?? null,
                        'appointment_price' => $doctor->appointment_price,
                        'consultation_price' => $doctor->consultation_price,
                        'department_id' => $doctor->department_id,
                        'department_name' => $doctor->department ? $doctor->department->name : null,
                    ];
                }),
                'current_page' => $doctors->currentPage(),
                'last_page' => $doctors->lastPage(),
                'total' => $doctors->total(),
                'per_page' => $perPage
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a failure response
            Log::error('Error fetching doctors: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching doctors',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-departments-in-doctors",
     *     summary="Get all departments",
     *     description="Retrieves a list of all departments.",
     *     operationId="getAllDepartments",
     *     tags={"Department"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of departments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="departments",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Error fetching departments")
     * )
     */

    public function getAllDepartments()
    {
        try {
            // Fetch all departments, selecting only the 'id' and 'name' fields
            $departments = Department::select('id', 'name')->get();

            // Return the list of departments in a JSON response
            return response()->json([
                'departments' => $departments,
            ], 200);
        } catch (\Exception $e) {
            // Log any errors that occur during the fetch operation
            Log::error('Error fetching departments: ', ['error' => $e->getMessage()]);

            // Return an error response if the operation fails
            return response()->json([
                'message' => 'Error fetching departments',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/v1/delete-doctor",
     *     summary="Delete a doctor",
     *     description="Deletes a doctor record from the database.",
     *     operationId="deleteDoctor",
     *     tags={"Doctor"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="doctor_id",
     *         in="query",
     *         required=true,
     *         description="The ID of the doctor to be deleted.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Doctor deleted successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Doctor deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Doctor not found.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Doctor not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while deleting the doctor.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the doctor"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */

    public function deleteDoctor(Request $request)
    {
        // Validate the incoming request to ensure 'doctor_id' is provided and exists in the 'doctors' table
        $validatedData = $request->validate([
            'doctor_id' => 'required|integer|exists:doctors,id',
        ]);

        $doctorId = $validatedData['doctor_id'];

        try {
            // Find the doctor by ID and delete the record
            $doctor = Doctor::findOrFail($doctorId);
            $doctor->delete();

            // Return a success response if the doctor was deleted successfully
            return response()->json([
                'status' => 'success',
                'message' => 'Doctor deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return an error response if the doctor is not found
            return response()->json([
                'status' => 'error',
                'message' => 'Doctor not found',
            ], 404);
        } catch (\Exception $e) {
            // Return an error response if any other exception occurs
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the doctor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v1/fetch-all-doctors",
     *     summary="Get all doctors",
     *     description="Retrieves a list of all doctors from the database.",
     *     operationId="getAllDoctors",
     *     tags={"Doctor"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of doctors retrieved successfully.",
     *         
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching doctors.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching doctors"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */

    public function getAllDoctors()
    {
        try {
            // Fetch all doctors
            $doctors = Doctor::all();

            // Return the list of doctors in a JSON response
            return response()->json([
                'doctors' => $doctors,
            ], 200);
        } catch (\Exception $e) {
            // Log any errors that occur during the fetch operation
            Log::error('Error fetching doctors: ', ['error' => $e->getMessage()]);

            // Return an error response if the operation fails
            return response()->json([
                'message' => 'Error fetching doctors',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
