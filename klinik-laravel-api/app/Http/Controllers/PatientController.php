<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Nurse;
use App\Models\Order;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Pharmacist;
use App\Models\Appointment;
use App\Models\Laboratorist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class PatientController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/add-patient",
     *     summary="Add a new patient",
     *     description="Creates a new patient record after validating email, gender, blood group, birth date, and other relevant fields. Ensures that the email is not already in use in various tables and that the user role is appropriate.",
     *     operationId="addPatient",
     *     tags={"Patient"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", format="email", example="example@domain.com"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female", "other"}, example="male"),
     *             @OA\Property(property="bloodGroup", type="string", enum={"A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"}, example="A+"),
     *             @OA\Property(property="birthDate", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="deathDate", type="string", format="date", nullable=true, example="2023-01-01"),
     *             @OA\Property(property="additionalNotes", type="string", nullable=true, example="Patient has a known allergy to penicillin.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Patient created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Patient created successfully!"),
     *             @OA\Property(property="newPatient", type="object",
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="example@domain.com"),
     *                 @OA\Property(property="blood_group", type="string", example="A+"),
     *                 @OA\Property(property="gender", type="string", example="male"),
     *                 @OA\Property(property="date_of_birth", type="string", format="date", example="1990-01-01"),
     *                 @OA\Property(property="date_of_death", type="string", format="date", nullable=true, example="2023-01-01"),
     *                 @OA\Property(property="notes", type="string", nullable=true, example="Patient has a known allergy to penicillin."),
     *                 @OA\Property(property="avatar", type="string", nullable=true, example="path/to/avatar.jpg")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Email not found"),
     *     @OA\Response(response=409, description="Conflict with email or user role"),
     *     @OA\Response(response=500, description="Error adding patient")
     * )
     */

    public function addPatient(Request $request)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'email' => 'required|string|email',
                'gender' => 'required|string|in:male,female,other',
                'bloodGroup' => 'required|string|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
                'birthDate' => 'required|date|before:today',
                'deathDate' => 'nullable|date|after:birthDate|before_or_equal:today',
                'additionalNotes' => 'nullable|string|max:500',
            ]);

            // Extract validated data
            $email = $validatedData['email'];
            $gender = $validatedData['gender'];
            $bloodGroup = $validatedData['bloodGroup'];
            $birthDate = $validatedData['birthDate'];
            $deathDate = $validatedData['deathDate'] ?? null;
            $additionalNotes = $validatedData['additionalNotes'] ?? null;

            // Check if the email is already used in various tables
            $existingPatient = User::where('email', $email)->first();
            $existingPatientInPatientTable = Patient::where('email', $email)->first();
            $checkIfDoctor = Doctor::where('email', $email)->first();
            $checkIfNurse = Nurse::where('email', $email)->first();
            $checkIfPharmacist = Pharmacist::where('email', $email)->first();
            $checkIfLaboratorist = Laboratorist::where('email', $email)->first();

            // Return error if the email is used in any other table
            if ($checkIfDoctor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This email is already used in Doctors table!',
                ], 409); // Conflict
            }

            if ($checkIfNurse) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This email is already used in Nurses table!',
                ], 409); // Conflict
            }

            if ($checkIfPharmacist) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This email is already used in Pharmacists table!',
                ], 409); // Conflict
            }

            if ($checkIfLaboratorist) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This email is already used in Laboratorists table!',
                ], 409); // Conflict
            }

            // Return error if the email is already used in the Patient table
            if ($existingPatientInPatientTable) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email is already used before!',
                ], 409); // Conflict
            }

            // Return error if the email is not found
            if (!$existingPatient) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email not found!',
                ], 404); // Not Found
            }

            // Return error if the user is an admin or holds any other special role
            if (
                $existingPatient->is_admin || $existingPatient->is_doctor || $existingPatient->is_nurse
                || $existingPatient->is_pharmacist || $existingPatient->is_laboratorist
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'First change the user role from the All Users section to User.',
                ], 409); // Conflict
            }

            // If all checks pass, create the new patient
            $patient = Patient::create([
                'name' => $existingPatient->name,
                'user_id' => $existingPatient->id,
                'email' => $existingPatient->email,
                'blood_group' => $bloodGroup,
                'gender' => $gender,
                'date_of_birth' => $birthDate,
                'date_of_death' => $deathDate,
                'notes' => $additionalNotes,
            ]);

            // Attach the avatar from the existing user
            $patient['avatar'] = $existingPatient->avatar;

            return response()->json([
                'status' => 'success',
                'message' => 'Patient created successfully!',
                'newPatient' => $patient
            ], 201); // Created

        } catch (\Exception $e) {
            // Log any errors that occur
            Log::error('Error adding patient: ', ['error' => $e->getMessage()]);

            // Return an error response
            return response()->json([
                'status' => 'error',
                'message' => 'Error adding patient',
                'error' => $e->getMessage(),
            ], 500); // Internal Server Error
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-patients",
     *     summary="Search for patients",
     *     description="Fetches a list of patients based on a search query. You can search by name or email.",
     *     operationId="getSearchedPatients",
     *     tags={"Patient"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         description="The search query for finding patients by name or email.",
     *         required=true,
     *         @OA\Schema(type="string", example="John")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of patients matching the search query",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                     @OA\Property(property="avatar", type="string", nullable=true, example="path/to/avatar.jpg"),
     *                     @OA\Property(property="gender", type="string", example="male"),
     *                     @OA\Property(property="bloodGroup", type="string", example="A+"),
     *                     @OA\Property(property="birthDate", type="string", format="date", example="1990-01-01"),
     *                     @OA\Property(property="deathDate", type="string", format="date", nullable=true, example="2023-01-01"),
     *                     @OA\Property(property="additionalNotes", type="string", nullable=true, example="Patient is allergic to penicillin.")
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="total", type="integer", example=100)
     *         )
     *     ),
     *     @OA\Response(response=500, description="Error fetching searched patients")
     * )
     */

    public function getSearchedPatients(Request $request)
    {
        try {
            // Get the search query from the request
            $searchQuery = $request->query('search_query');

            // Search for patients based on name or email
            $patients = Patient::with('user:id,avatar')
                ->where('name', 'like', '%' . $searchQuery . '%')
                ->orWhere('email', 'like', '%' . $searchQuery . '%')
                ->paginate();

            // Return the searched patients in JSON format
            return response()->json([
                'data' => $patients->map(function ($patient) {
                    return [
                        'id' => $patient->id,
                        'name' => $patient->name,
                        'email' => $patient->email,
                        'avatar' => $patient->user->avatar ?? null,
                        'gender' => $patient->gender,
                        'bloodGroup' => $patient->blood_group,
                        'birthDate' => $patient->date_of_birth,
                        'deathDate' => $patient->date_of_death,
                        'additionalNotes' => $patient->notes
                    ];
                }),
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
                'total' => $patients->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log any errors that occur
            Log::error('Error fetching patients: ', ['error' => $e->getMessage()]);

            // Return an error response
            return response()->json([
                'message' => 'Error fetching searched patients',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-patients",
     *     summary="Retrieve all patients",
     *     description="Fetches a list of all patients stored in the database.",
     *     operationId="getPatients",
     *     tags={"Patient"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of patients retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="patients", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", format="email", example="example@domain.com"),
     *                     @OA\Property(property="blood_group", type="string", example="A+"),
     *                     @OA\Property(property="gender", type="string", example="male"),
     *                     @OA\Property(property="date_of_birth", type="string", format="date", example="1990-01-01"),
     *                     @OA\Property(property="date_of_death", type="string", format="date", nullable=true, example="2023-01-01"),
     *                     @OA\Property(property="notes", type="string", nullable=true, example="Patient has a known allergy to penicillin."),
     *                     @OA\Property(property="avatar", type="string", nullable=true, example="path/to/avatar.jpg")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Error fetching patients")
     * )
     */
    public function getPatients(Request $request)
    {
        try {
            // Get the number of patients per page from the query, default to 15
            $perPage = $request->query('per_page', 15);

            // Retrieve patients with related user data, and paginate
            $patients = Patient::with('user:id,avatar')
                ->paginate($perPage);

            // Map the patients data to include relevant fields
            $patientData = $patients->map(function ($patient) {
                return [
                    'id' => $patient->id,
                    'name' => $patient->name,
                    'email' => $patient->email,
                    'avatar' => $patient->user->avatar ?? null,
                    'gender' => $patient->gender,
                    'bloodGroup' => $patient->blood_group,
                    'birthDate' => $patient->date_of_birth,
                    'deathDate' => $patient->date_of_death,
                    'additionalNotes' => $patient->notes
                ];
            });

            // Return the paginated patient data in JSON format
            return response()->json([
                'data' => $patientData,
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
                'total' => $patients->total(),
                'per_page' => $perPage
            ], 200);
        } catch (\Exception $e) {
            // Log any errors that occur
            Log::error('Error fetching patients: ', ['error' => $e->getMessage()]);

            // Return an error response
            return response()->json([
                'message' => 'Error fetching patients',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/delete-patient",
     *     summary="Delete a patient",
     *     description="Deletes a patient from the database by their patient ID.",
     *     operationId="deletePatient",
     *     tags={"Patient"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"patient_id"},
     *             @OA\Property(
     *                 property="patient_id",
     *                 type="integer",
     *                 description="The ID of the patient to be deleted",
     *                 example=1
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Patient deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Patient deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Patient not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Patient not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while deleting the patient",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the patient"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function deletePatient(Request $request)
    {
        // Validate the request to ensure the 'patient_id' is provided and exists in the 'patients' table
        $validatedData = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
        ]);

        $patientId = $validatedData['patient_id'];

        try {
            // Find the patient by ID and delete it
            $patient = Patient::findOrFail($patientId);
            $patient->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Patient deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Handle case where the patient was not found
            return response()->json([
                'status' => 'error',
                'message' => 'Patient not found',
            ], 404);
        } catch (\Exception $e) {
            // Handle any other exceptions
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the patient',
                'error' => $e->getMessage(),
            ], 500);
        }
    }





    /**
     * @OA\Get(
     *     path="/api/v1/fetch-all-patients",
     *     summary="Get all patients",
     *     description="Retrieves a list of all patients from the database.",
     *     operationId="getAllPatients",
     *     tags={"Patient"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of all patients",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="patients",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                     @OA\Property(property="blood_group", type="string", example="O+"),
     *                     @OA\Property(property="gender", type="string", example="male"),
     *                     @OA\Property(property="date_of_birth", type="string", format="date", example="1990-01-01"),
     *                     @OA\Property(property="date_of_death", type="string", format="date", nullable=true, example=null),
     *                     @OA\Property(property="notes", type="string", nullable=true, example="No additional notes")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching patients",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching patients"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function getAllPatients()
    {
        try {
            // Retrieve all patients from the database
            $patients = Patient::all();
            return response()->json([
                'patients' => $patients,
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching patients: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching patients',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Post(
     *     path="/api/v1/fetch-patient-appointments-and-orders",
     *     summary="Get patient's appointments and orders",
     *     description="Fetches the appointments and orders associated with a specific patient based on their patient_id.",
     *     operationId="getPatientAppointmentsAndOrders",
     *     tags={"Patient"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"patient_id"},
     *             @OA\Property(
     *                 property="patient_id",
     *                 type="integer",
     *                 description="ID of the patient",
     *                 example=1
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved patient's appointments and orders",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="appointments",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="payment_status", type="string", example="Paid"),
     *                     @OA\Property(property="doctor_id", type="integer", example=5),
     *                     @OA\Property(property="start_time", type="string", format="time", example="10:00:00"),
     *                     @OA\Property(property="end_time", type="string", format="time", example="10:30:00"),
     *                     @OA\Property(property="date", type="string", format="date", example="2024-09-10"),
     *                     @OA\Property(property="day", type="string", example="Monday"),
     *                     @OA\Property(property="description", type="string", example="Routine check-up"),
     *                     @OA\Property(property="order_id", type="integer", example=2),
     *                     @OA\Property(property="session_id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=7),
     *                     @OA\Property(property="general_status", type="string", example="Completed"),
     *                     @OA\Property(property="completed", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="datetime", example="2024-09-09 12:30:45"),
     *                     @OA\Property(property="doctor_name", type="string", example="Dr. John Smith"),
     *                     @OA\Property(property="doctor_email", type="string", example="doctor.john@example.com"),
     *                     @OA\Property(property="patient_name", type="string", example="Jane Doe"),
     *                     @OA\Property(property="patient_email", type="string", example="jane.doe@example.com")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="orders",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="order_number", type="string", example="ORD12345"),
     *                     @OA\Property(property="total", type="number", format="float", example=100.50),
     *                     @OA\Property(property="status", type="string", example="Shipped")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching patient appointments and orders",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching patient appointments and orders"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function getPatientAppointmentsAndOrders(Request $request)
    {
        try {
            // Validate the request to ensure 'patient_id' is provided and exists in 'patients' table
            $validatedData = $request->validate([
                'patient_id' => 'required|integer|exists:patients,id',
            ]);

            // Retrieve the patient based on the validated 'patient_id'
            $patient = Patient::findOrFail($validatedData['patient_id']);

            // Fetch appointments associated with the patient's user_id
            $appointments = Appointment::where('user_id', $patient->user_id)->get();

            // Fetch orders associated with the patient's user_id
            $orders = Order::where('user_id', $patient->user_id)->get();

            // Transform the appointments collection to include additional details
            $appointments->transform(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'payment_status' => $appointment->payment_status,
                    'doctor_id' => $appointment->doctor_id,
                    'start_time' => $appointment->start_time,
                    'end_time' => $appointment->end_time,
                    'date' => $appointment->date,
                    'day' => $appointment->day,
                    'description' => $appointment->description,
                    'order_id' => $appointment->order_id,
                    'session_id' => $appointment->session_id,
                    'user_id' => $appointment->user_id,
                    'general_status' => $appointment->general_status,
                    'completed' => $appointment->completed,
                    'created_at' => $appointment->created_at,
                    'doctor_name' => $appointment->doctor->name,
                    'doctor_email' => $appointment->doctor->email,
                    'patient_name' => $appointment->user->name,
                    'patient_email' => $appointment->user->email
                ];
            });

            // Return a successful JSON response with appointments and orders
            return response()->json([
                'appointments' => $appointments,
                'orders' => $orders
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching patient appointments and orders: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching patient appointments and orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/v1/patient-dashboard-data",
     *     summary="Get patient dashboard data",
     *     tags={"Patient"},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved patient dashboard data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="appointments", type="object",
     *                 @OA\Property(property="January", type="object",
     *                     @OA\Property(property="appointmentCount", type="integer")
     *                 ),
     *                 @OA\Property(property="February", type="object",
     *                     @OA\Property(property="appointmentCount", type="integer")
     *                 ),
     *                 @OA\Property(property="March", type="object",
     *                     @OA\Property(property="appointmentCount", type="integer")
     *                 ),
     *                 @OA\Property(property="April", type="object",
     *                     @OA\Property(property="appointmentCount", type="integer")
     *                 ),
     *                 @OA\Property(property="May", type="object",
     *                     @OA\Property(property="appointmentCount", type="integer")
     *                 ),
     *                 @OA\Property(property="June", type="object",
     *                     @OA\Property(property="appointmentCount", type="integer")
     *                 ),
     *                 @OA\Property(property="July", type="object",
     *                     @OA\Property(property="appointmentCount", type="integer")
     *                 ),
     *                 @OA\Property(property="August", type="object",
     *                     @OA\Property(property="appointmentCount", type="integer")
     *                 ),
     *                 @OA\Property(property="September", type="object",
     *                     @OA\Property(property="appointmentCount", type="integer")
     *                 ),
     *                 @OA\Property(property="October", type="object",
     *                     @OA\Property(property="appointmentCount", type="integer")
     *                 ),
     *                 @OA\Property(property="November", type="object",
     *                     @OA\Property(property="appointmentCount", type="integer")
     *                 ),
     *                 @OA\Property(property="December", type="object",
     *                     @OA\Property(property="appointmentCount", type="integer")
     *                 )
     *             ),
     *             @OA\Property(property="totalAppointments", type="integer"),
     *             @OA\Property(property="orders", type="object",
     *                 @OA\Property(property="January", type="object",
     *                     @OA\Property(property="orderCount", type="integer")
     *                 ),
     *                 @OA\Property(property="February", type="object",
     *                     @OA\Property(property="orderCount", type="integer")
     *                 ),
     *                 @OA\Property(property="March", type="object",
     *                     @OA\Property(property="orderCount", type="integer")
     *                 ),
     *                 @OA\Property(property="April", type="object",
     *                     @OA\Property(property="orderCount", type="integer")
     *                 ),
     *                 @OA\Property(property="May", type="object",
     *                     @OA\Property(property="orderCount", type="integer")
     *                 ),
     *                 @OA\Property(property="June", type="object",
     *                     @OA\Property(property="orderCount", type="integer")
     *                 ),
     *                 @OA\Property(property="July", type="object",
     *                     @OA\Property(property="orderCount", type="integer")
     *                 ),
     *                 @OA\Property(property="August", type="object",
     *                     @OA\Property(property="orderCount", type="integer")
     *                 ),
     *                 @OA\Property(property="September", type="object",
     *                     @OA\Property(property="orderCount", type="integer")
     *                 ),
     *                 @OA\Property(property="October", type="object",
     *                     @OA\Property(property="orderCount", type="integer")
     *                 ),
     *                 @OA\Property(property="November", type="object",
     *                     @OA\Property(property="orderCount", type="integer")
     *                 ),
     *                 @OA\Property(property="December", type="object",
     *                     @OA\Property(property="orderCount", type="integer")
     *                 )
     *             ),
     *             @OA\Property(property="totalOrders", type="integer")
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
    public function getPatientDashboardData()
    {
        try {
            // Initialize the array to hold appointment counts by month
            $appointmentsByMonths = [
                'January' => ['appointmentCount' => 0],
                'February' => ['appointmentCount' => 0],
                'March' => ['appointmentCount' => 0],
                'April' => ['appointmentCount' => 0],
                'May' => ['appointmentCount' => 0],
                'June' => ['appointmentCount' => 0],
                'July' => ['appointmentCount' => 0],
                'August' => ['appointmentCount' => 0],
                'September' => ['appointmentCount' => 0],
                'October' => ['appointmentCount' => 0],
                'November' => ['appointmentCount' => 0],
                'December' => ['appointmentCount' => 0],
            ];

            // Retrieve appointments for the authenticated user where status is either 'scheduled' or 'completed'
            $appointments = Appointment::where('user_id', auth()->user()->id)
                ->where(function ($query) {
                    $query->where('general_status', 'scheduled')
                        ->orWhere('general_status', 'completed');
                })
                ->get();

            // Count the number of appointments for each month
            foreach ($appointments as $appointment) {
                $appMonth = Carbon::parse($appointment->date)->format('F');
                if (array_key_exists($appMonth, $appointmentsByMonths)) {
                    $appointmentsByMonths[$appMonth]['appointmentCount'] += 1;
                }
            }

            // Calculate the total number of appointments
            $totalAppointments = 0;
            foreach ($appointmentsByMonths as $appointment) {
                $totalAppointments += $appointment['appointmentCount'];
            }

            // Initialize the array to hold order counts by month
            $ordersByMonths = [
                'January' => ['orderCount' => 0],
                'February' => ['orderCount' => 0],
                'March' => ['orderCount' => 0],
                'April' => ['orderCount' => 0],
                'May' => ['orderCount' => 0],
                'June' => ['orderCount' => 0],
                'July' => ['orderCount' => 0],
                'August' => ['orderCount' => 0],
                'September' => ['orderCount' => 0],
                'October' => ['orderCount' => 0],
                'November' => ['orderCount' => 0],
                'December' => ['orderCount' => 0],
            ];

            // Retrieve orders for the authenticated user where status is 'paid'
            $orders = Order::where('user_id', auth()->user()->id)
                ->where('status', 'paid')->get();

            // Count the number of orders for each month
            foreach ($orders as $order) {
                $orderMonth = Carbon::parse($order->created_at)->format('F');
                if (array_key_exists($orderMonth, $ordersByMonths)) {
                    $ordersByMonths[$orderMonth]['orderCount'] += 1;
                }
            }

            // Calculate the total number of orders
            $totalOrders = array_sum(array_column($ordersByMonths, 'orderCount'));

            // Return a successful JSON response with appointment and order data
            return response()->json([
                'appointments' => $appointmentsByMonths,
                'totalAppointments' => $totalAppointments,
                'orders' => $ordersByMonths,
                'totalOrders' => $totalOrders
            ], 200);
        } catch (\Throwable $th) {
            // Log the error and return an error response
            Log::error('Error fetching patient dashboard data: ', ['error' => $th->getMessage()]);

            return response()->json([
                'message' => 'Error fetching patient dashboard data',
                'error' => $th->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/v1/fetch-patient-appointments",
     *     summary="Get patient appointments",
     *     tags={"Patient"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items to display per page",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved patient appointments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="payment_status", type="string"),
     *                     @OA\Property(property="doctor_id", type="integer"),
     *                     @OA\Property(property="start_time", type="string"),
     *                     @OA\Property(property="end_time", type="string"),
     *                     @OA\Property(property="date", type="string"),
     *                     @OA\Property(property="day", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="order_id", type="integer"),
     *                     @OA\Property(property="session_id", type="integer"),
     *                     @OA\Property(property="user_id", type="integer"),
     *                     @OA\Property(property="general_status", type="string"),
     *                     @OA\Property(property="completed", type="boolean"),
     *                     @OA\Property(property="created_at", type="string"),
     *                     @OA\Property(property="doctor_name", type="string"),
     *                     @OA\Property(property="doctor_email", type="string"),
     *                     @OA\Property(property="patient_name", type="string"),
     *                     @OA\Property(property="patient_email", type="string")
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="per_page", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching appointments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getPatientAppointments(Request $request)
    {
        try {
            // Determine the number of items to display per page (default is 15)
            $perPage = $request->query('per_page', 15);

            // Retrieve and paginate appointments for the authenticated user
            $appointments = Appointment::select(
                'id',
                'payment_status',
                'doctor_id',
                'start_time',
                'end_time',
                'date',
                'day',
                'description',
                'order_id',
                'session_id',
                'user_id',
                'general_status',
                'completed',
                'created_at'
            )
                ->where('user_id', auth()->user()->id)
                ->paginate($perPage);

            // Transform the collection to include additional details
            $appointments->getCollection()->transform(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'payment_status' => $appointment->payment_status,
                    'doctor_id' => $appointment->doctor_id,
                    'start_time' => $appointment->start_time,
                    'end_time' => $appointment->end_time,
                    'date' => $appointment->date,
                    'day' => $appointment->day,
                    'description' => $appointment->description,
                    'order_id' => $appointment->order_id,
                    'session_id' => $appointment->session_id,
                    'user_id' => $appointment->user_id,
                    'general_status' => $appointment->general_status,
                    'completed' => $appointment->completed,
                    'created_at' => $appointment->created_at,
                    'doctor_name' => $appointment->doctor->name,
                    'doctor_email' => $appointment->doctor->email,
                    'patient_name' => $appointment->user->name,
                    'patient_email' => $appointment->user->email
                ];
            });

            // Return a successful JSON response with appointment data
            return response()->json([
                'data' => $appointments->items(),
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage(),
                'total' => $appointments->total(),
                'per_page' => $perPage
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching appointments: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-patient-appointments",
     *     summary="Search for patient appointments by doctor or patient name",
     *     tags={"Patient"},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         required=true,
     *         description="Search query for doctor or patient names",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved searched patient appointments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="appointments", type="object"),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching searched appointments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getSearchedPatientAppointments(Request $request)
    {
        try {
            // Retrieve the search query from the request
            $searchQuery = $request->query('search_query');

            // Search for appointments based on the search query in doctor or patient names
            $appointments = Appointment::where(function ($query) use ($searchQuery) {
                $query->whereHas('doctor', function ($q) use ($searchQuery) {
                    $q->where('name', 'like', '%' . $searchQuery . '%');
                })
                    ->orWhereHas('user', function ($q) use ($searchQuery) {
                        $q->where('name', 'like', '%' . $searchQuery . '%');
                    });
            })
                ->where('user_id', auth()->user()->id)
                ->select(
                    'id',
                    'payment_status',
                    'doctor_id',
                    'start_time',
                    'end_time',
                    'date',
                    'day',
                    'description',
                    'order_id',
                    'session_id',
                    'user_id',
                    'general_status',
                    'completed',
                    'created_at'
                )
                ->paginate();

            // Transform the collection to include additional details
            $appointments->getCollection()->transform(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'payment_status' => $appointment->payment_status,
                    'doctor_id' => $appointment->doctor_id,
                    'start_time' => $appointment->start_time,
                    'end_time' => $appointment->end_time,
                    'date' => $appointment->date,
                    'day' => $appointment->day,
                    'description' => $appointment->description,
                    'order_id' => $appointment->order_id,
                    'session_id' => $appointment->session_id,
                    'user_id' => $appointment->user_id,
                    'general_status' => $appointment->general_status,
                    'completed' => $appointment->completed,
                    'created_at' => $appointment->created_at,
                    'doctor_name' => $appointment->doctor->name,
                    'doctor_email' => $appointment->doctor->email,
                    'patient_name' => $appointment->user->name,
                    'patient_email' => $appointment->user->email
                ];
            });

            // Return a successful JSON response with the searched appointments
            return response()->json([
                'appointments' => $appointments,
                'total' => $appointments->count(),
                'per_page' => $appointments->perPage(),
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage()
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching searched appointments: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching searched appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-patient-appointments-by-status",
     *     summary="Retrieve patient appointments by payment or general status",
     *     tags={"Patient"},
     *     @OA\Parameter(
     *         name="paymentStatus",
     *         in="query",
     *         required=true,
     *         description="The status of the appointments to filter by (paid, unpaid, scheduled, completed, canceled)",
     *         @OA\Schema(type="string", enum={"paid", "unpaid", "scheduled", "completed", "canceled"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved appointments by status",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="appointments", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching appointments by status",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getPatientAppointmentsByStatus(Request $request)
    {
        try {
            // Retrieve the status parameter from the query string
            $status = $request->query('paymentStatus');

            // Determine the appropriate query based on the status value
            if ($status === 'paid') {
                $appointments = Appointment::where('payment_status', 'paid')
                    ->where('user_id', auth()->user()->id)->paginate();
            } elseif ($status === 'unpaid') {
                $appointments = Appointment::where('payment_status', 'unpaid')
                    ->where('user_id', auth()->user()->id)->paginate();
            } elseif ($status === 'scheduled') {
                $appointments = Appointment::where('general_status', 'scheduled')
                    ->where('user_id', auth()->user()->id)->paginate();
            } elseif ($status === 'completed') {
                $appointments = Appointment::where('general_status', 'completed')
                    ->where('user_id', auth()->user()->id)->paginate();
            } elseif ($status === 'canceled') {
                $appointments = Appointment::where('general_status', 'canceled')
                    ->where('user_id', auth()->user()->id)->paginate();
            }

            // Transform the collection to include additional details
            $appointments->getCollection()->transform(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'payment_status' => $appointment->payment_status,
                    'doctor_id' => $appointment->doctor_id,
                    'start_time' => $appointment->start_time,
                    'end_time' => $appointment->end_time,
                    'date' => $appointment->date,
                    'day' => $appointment->day,
                    'description' => $appointment->description,
                    'order_id' => $appointment->order_id,
                    'session_id' => $appointment->session_id,
                    'user_id' => $appointment->user_id,
                    'general_status' => $appointment->general_status,
                    'completed' => $appointment->completed,
                    'created_at' => $appointment->created_at,
                    'doctor_name' => $appointment->doctor->name,
                    'doctor_email' => $appointment->doctor->email,
                    'patient_name' => $appointment->user->name,
                    'patient_email' => $appointment->user->email
                ];
            });

            // Return a successful JSON response with paginated appointment data
            return response()->json([
                'appointments' => $appointments->items(),
                'total' => $appointments->total(),
                'per_page' => $appointments->perPage(),
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage()
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching appointments by status: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching appointments by status',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/count-patient-appointments",
     *     summary="Count patient appointments by status",
     *     tags={"Patient"},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved appointment counts",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="allAppointments", type="integer"),
     *             @OA\Property(property="pendingAppointments", type="integer"),
     *             @OA\Property(property="completedAppointments", type="integer"),
     *             @OA\Property(property="canceledAppointments", type="integer"),
     *             @OA\Property(property="scheduledAppointments", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching appointment counts",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function countPatientAppointments()
    {
        try {
            // Get the authenticated user's ID
            $userId = auth()->user()->id;
            $cacheKey = "count_patient_{$userId}_appointments";

            // Check if the appointment counts are cached
            if (Cache::has($cacheKey)) {
                $count = Cache::get($cacheKey);
            } else {
                // Calculate counts of different appointment statuses and cache the result
                $count = Cache::rememberForever($cacheKey, function () {
                    return [
                        'allAppointments' => Appointment::where('user_id', auth()->user()->id)->count(),
                        'pendingAppointments' => Appointment::where('general_status', 'pending')
                            ->where('user_id', auth()->user()->id)->count(),
                        'completedAppointments' => Appointment::where('general_status', 'completed')
                            ->where('user_id', auth()->user()->id)->count(),
                        'canceledAppointments' => Appointment::where('general_status', 'canceled')
                            ->where('user_id', auth()->user()->id)->count(),
                        'scheduledAppointments' => Appointment::where('general_status', 'scheduled')
                            ->where('user_id', auth()->user()->id)->count(),
                    ];
                });
            }

            // Return a successful JSON response with the appointment counts
            return response()->json($count, 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching and counting appointments: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching and counting appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/v1/fetch-patient-orders",
     *     summary="Retrieve patient orders",
     *     tags={"Patient"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items to display per page",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved paginated orders",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="total_price", type="number", format="float"),
     *                     @OA\Property(property="session_id", type="string"),
     *                     @OA\Property(property="user_id", type="integer"),
     *                     @OA\Property(property="order_id", type="string"),
     *                     @OA\Property(property="full_name", type="string"),
     *                     @OA\Property(property="gender", type="string"),
     *                     @OA\Property(property="national_card_id", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="per_page", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching orders",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getOrders(Request $request)
    {
        try {
            // Determine the number of items to display per page (default is 15)
            $perPage = $request->query('per_page', 15);

            // Retrieve and paginate orders for the authenticated user
            $orders = Order::select(
                'id',
                'status',
                'total_price',
                'session_id',
                'user_id',
                'order_id',
                'full_name',
                'gender',
                'national_card_id',
                'created_at'
            )
                ->where('user_id', auth()->user()->id)
                ->paginate($perPage);

            // Return a successful JSON response with paginated order data
            return response()->json([
                'data' => $orders->items(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
                'per_page' => $perPage
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching orders: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-patient-orders",
     *     summary="Search for patient orders",
     *     tags={"Patient"},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         required=true,
     *         description="Search query for national card ID or full name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved searched orders",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="orders", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="total_price", type="number", format="float"),
     *                     @OA\Property(property="session_id", type="string"),
     *                     @OA\Property(property="user_id", type="integer"),
     *                     @OA\Property(property="order_id", type="string"),
     *                     @OA\Property(property="full_name", type="string"),
     *                     @OA\Property(property="gender", type="string"),
     *                     @OA\Property(property="national_card_id", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching searched orders",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getSearchedOrders(Request $request)
    {
        try {
            // Retrieve the search query parameter from the request
            $searchQuery = $request->query('search_query');

            // Perform a search on orders based on national card ID or full name
            $orders = Order::where('national_card_id', 'like', '%' . $searchQuery . '%')
                ->orWhere('full_name', 'like', '%' . $searchQuery . '%')
                ->select(
                    'id',
                    'status',
                    'total_price',
                    'session_id',
                    'user_id',
                    'order_id',
                    'full_name',
                    'gender',
                    'national_card_id',
                    'created_at'
                )
                ->where('user_id', auth()->user()->id)
                ->paginate();

            // Return a successful JSON response with paginated order data and search results
            return response()->json([
                'orders' => $orders->items(),
                'total' => $orders->count(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage()
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching searched orders: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching searched orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-patient-orders-by-status",
     *     summary="Retrieve orders by payment status",
     *     tags={"Patient"},
     *     @OA\Parameter(
     *         name="paymentStatus",
     *         in="query",
     *         required=true,
     *         description="The payment status of the orders to filter",
     *         @OA\Schema(type="string", enum={"paid", "unpaid"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved orders by status",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="orders", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="total_price", type="number", format="float"),
     *                     @OA\Property(property="session_id", type="string"),
     *                     @OA\Property(property="user_id", type="integer"),
     *                     @OA\Property(property="order_id", type="string"),
     *                     @OA\Property(property="full_name", type="string"),
     *                     @OA\Property(property="gender", type="string"),
     *                     @OA\Property(property="national_card_id", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching orders by status",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getOrdersByStatus(Request $request)
    {
        try {
            // Retrieve the status parameter from the query string
            $status = $request->query('paymentStatus');

            // Determine the appropriate query based on the status value
            if ($status === 'paid') {
                $orders = Order::where('status', 'paid')
                    ->where('user_id', auth()->user()->id)
                    ->paginate();
            } elseif ($status === 'unpaid') {
                $orders = Order::where('status', 'unpaid')
                    ->where('user_id', auth()->user()->id)
                    ->paginate();
            }

            // Return a successful JSON response with paginated order data and status results
            return response()->json([
                'orders' => $orders->items(),
                'total' => $orders->count(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage()
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching orders by status: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching orders by status',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/count-patient-orders",
     *     summary="Count patient orders",
     *     tags={"Patient"},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully counted orders",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="allOrders", type="integer"),
     *             @OA\Property(property="paidOrders", type="integer"),
     *             @OA\Property(property="unpaidOrders", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while counting orders",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function countOrders()
    {
        try {
            // Get the authenticated user's ID
            $userId = auth()->user()->id;
            $cacheKey = "count_orders_patient_{$userId}";

            // Check if the order counts are cached
            if (Cache::has($cacheKey)) {
                $count = Cache::get($cacheKey);
            } else {
                // Calculate counts of different order statuses and cache the result
                $count = Cache::rememberForever($cacheKey, function () {
                    return [
                        'allOrders' => Order::where('user_id', auth()->user()->id)->count(),
                        'paidOrders' => Order::where('status', 'paid')->where('user_id', auth()->user()->id)->count(),
                        'unpaidOrders' => Order::where('status', 'unpaid')->where('user_id', auth()->user()->id)->count(),
                    ];
                });
            }

            // Return a successful JSON response with the order counts
            return response()->json($count, 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching and counting orders: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching and counting orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
