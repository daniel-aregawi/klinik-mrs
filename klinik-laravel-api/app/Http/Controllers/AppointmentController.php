<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\AppointmentNotification;
use App\Models\Order;
use App\Models\Patient;
use App\Models\StartEndTime;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class AppointmentController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/v1/fetch-appointments",
     *     summary="Get paginated list of appointments",
     *     description="Fetches a paginated list of appointments with doctor and patient details.",
     *     operationId="getAppointments",
     *     tags={"Appointments"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of appointments to retrieve per page",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             default=15
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully fetched appointments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
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
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="total", type="integer", example=50),
     *             @OA\Property(property="per_page", type="integer", example=15)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching appointments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching appointments"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function getAppointments(Request $request)
    {
        try {
            // Get the 'per_page' query parameter or default to 15 if not provided
            $perPage = $request->query('per_page', 15);

            // Fetch a paginated list of appointments, selecting specific fields
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
            )->paginate($perPage);

            // Transform the appointments collection to include doctor and patient details
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

            // Return the transformed appointments data with pagination info
            return response()->json([
                'data' => $appointments->items(),
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage(),
                'total' => $appointments->total(),
                'per_page' => $perPage
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error message
            Log::error('Error fetching appointments: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-appointments",
     *     summary="Search appointments by doctor or patient name",
     *     description="Fetch a paginated list of appointments filtered by doctor or patient name.",
     *     tags={"Appointments"},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         description="Search term to filter appointments by doctor or patient name",
     *         required=true,
     *         @OA\Schema(type="string", example="John Doe")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="appointments", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="payment_status", type="string", example="paid"),
     *                 @OA\Property(property="doctor_id", type="integer", example=2),
     *                 @OA\Property(property="start_time", type="string", format="time", example="09:00:00"),
     *                 @OA\Property(property="end_time", type="string", format="time", example="09:30:00"),
     *                 @OA\Property(property="date", type="string", format="date", example="2024-09-20"),
     *                 @OA\Property(property="day", type="string", example="Monday"),
     *                 @OA\Property(property="description", type="string", example="Routine check-up"),
     *                 @OA\Property(property="order_id", type="integer", example=123),
     *                 @OA\Property(property="session_id", type="integer", example=321),
     *                 @OA\Property(property="user_id", type="integer", example=5),
     *                 @OA\Property(property="general_status", type="string", example="confirmed"),
     *                 @OA\Property(property="completed", type="boolean", example=false),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-09-01 08:00:00"),
     *                 @OA\Property(property="doctor_name", type="string", example="Dr. John Doe"),
     *                 @OA\Property(property="doctor_email", type="string", example="doctor@example.com"),
     *                 @OA\Property(property="patient_name", type="string", example="Jane Doe"),
     *                 @OA\Property(property="patient_email", type="string", example="patient@example.com")
     *             )),
     *             @OA\Property(property="total", type="integer", example=10),
     *             @OA\Property(property="per_page", type="integer", example=15),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching searched appointments",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching searched appointments"),
     *             @OA\Property(property="error", type="string", example="Internal server error message")
     *         )
     *     )
     * )
     */
    public function getSearchedAppointments(Request $request)
    {
        try {
            // Get the 'search_query' parameter from the request
            $searchQuery = $request->query('search_query');

            // Search appointments by doctor's or patient's name
            $appointments = Appointment::whereHas('doctor', function ($query) use ($searchQuery) {
                $query->where('name', 'like', '%' . $searchQuery . '%');
            })
                ->orWhereHas('user', function ($query) use ($searchQuery) {
                    $query->where('name', 'like', '%' . $searchQuery . '%');
                })
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
                )->paginate();

            // Transform each appointment to include doctor and patient details
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

            // Return the paginated and transformed appointments
            return response()->json([
                'appointments' => $appointments,
                'total' => $appointments->count(),
                'per_page' => $appointments->perPage(),
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage()
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error message
            Log::error('Error fetching searched appointments: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching searched appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/v1/fetch-appointments-by-status",
     *     summary="Get appointments filtered by payment or general status",
     *     description="Fetches a paginated list of appointments filtered by payment status ('paid', 'unpaid') or general status ('scheduled', 'completed', 'canceled').",
     *     operationId="getAppointmentsByStatus",
     *     tags={"Appointments"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="paymentStatus",
     *         in="query",
     *         description="Filter appointments by payment status ('paid', 'unpaid') or general status ('scheduled', 'completed', 'canceled')",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             enum={"paid", "unpaid", "scheduled", "completed", "canceled"},
     *             example="paid"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully fetched filtered appointments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="appointments", type="array",
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
     *             @OA\Property(property="total", type="integer", example=50),
     *             @OA\Property(property="per_page", type="integer", example=15),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching appointments by status",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching appointments by status"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function getAppointmentsByStatus(Request $request)
    {
        try {
            // Get the 'paymentStatus' query parameter from the request
            $status = $request->query('paymentStatus');

            // Determine which appointments to fetch based on the status
            if ($status === 'paid') {
                $appointments = Appointment::where('payment_status', 'paid')->paginate();
            } elseif ($status === 'unpaid') {
                $appointments = Appointment::where('payment_status', 'unpaid')->paginate();
            } elseif ($status === 'scheduled') {
                $appointments = Appointment::where('general_status', 'scheduled')->paginate();
            } elseif ($status === 'completed') {
                $appointments = Appointment::where('general_status', 'completed')->paginate();
            } elseif ($status === 'canceled') {
                $appointments = Appointment::where('general_status', 'canceled')->paginate();
            }

            // Transform the appointments collection to include doctor and patient details
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

            // Return the filtered and transformed appointments with pagination info
            return response()->json([
                'appointments' => $appointments->items(),
                'total' => $appointments->total(),
                'per_page' => $appointments->perPage(),
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage()
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error message
            Log::error('Error fetching appointments by status: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching appointments by status',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/appointments/update",
     *     summary="Update an appointment",
     *     description="Updates the payment and general status of an appointment.",
     *     operationId="updateAppointment",
     *     tags={"Appointments"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"appointmentId", "paymentStatus", "generalStatus"},
     *             @OA\Property(property="appointmentId", type="integer", example=1, description="ID of the appointment to update"),
     *             @OA\Property(property="paymentStatus", type="string", example="paid", description="Payment status of the appointment, either 'paid' or 'unpaid'"),
     *             @OA\Property(property="generalStatus", type="string", example="completed", description="General status of the appointment, can be 'completed', 'pending', or 'canceled'")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Appointment updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Appointment updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Appointment not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error updating appointment"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while updating the appointment",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error updating appointment"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function updateAppointment(Request $request)
    {
        try {
            // Validate the incoming request data
            $validatedData = $request->validate([
                'appointmentId' => 'required|integer|exists:appointments,id', // Ensure appointmentId exists in the appointments table
                'paymentStatus' => 'required|string|max:255|in:paid,unpaid', // Validate paymentStatus is either 'paid' or 'unpaid'
                'generalStatus' => 'required|string|max:255|in:completed,pending,canceled', // Validate generalStatus is one of the specified values
            ]);

            // Extract validated data
            $appointmentId = $validatedData['appointmentId'];
            $paymentStatus = $validatedData['paymentStatus'];
            $generalStatus = $validatedData['generalStatus'];

            // Find the appointment or fail if not found
            $appointment = Appointment::findOrFail($appointmentId);

            // Update the appointment with new statuses
            $appointment->update([
                'payment_status' => $paymentStatus,
                'general_status' => $generalStatus
            ]);

            // Invalidate relevant cache entries to reflect updated data
            $userId = auth()->user()->id;
            // Optionally, uncomment the line below if you have specific cache keys for the doctor's appointments
            // $doctorCacheKey = "count_doctor_{$userId}_appointments";
            // Cache::forget($doctorCacheKey);

            $cacheKeys = [
                'count_appointments', // General cache key for appointments count
                "count_doctor_{$userId}_appointments", // Doctor-specific appointments count cache key
                "count_patient_{$userId}_appointments" // Patient-specific appointments count cache key
            ];

            // Clear the cache keys
            foreach ($cacheKeys as $key) {
                if (Cache::has($key)) {
                    Cache::forget($key);
                }
            }

            // Return a success response
            return response()->json([
                'message' => 'Appointment updated successfully',
            ], 200);
        } catch (\Exception $e) {
            // Log the error message
            Log::error('Error updating appointment: ', ['error' => $e->getMessage()]);

            // Return an error response
            return response()->json([
                'message' => 'Error updating appointment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/v1/appointments/delete",
     *     summary="Delete an appointment",
     *     description="Deletes an appointment by its ID.",
     *     operationId="deleteAppointment",
     *     tags={"Appointments"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"appointmentId"},
     *             @OA\Property(property="appointmentId", type="integer", example=1, description="ID of the appointment to delete")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Appointment deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Appointment deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Appointment not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Appointment not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while deleting the appointment",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the appointment"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function deleteAppointment(Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'appointmentId' => 'required|integer|exists:appointments,id', // Ensure appointmentId exists in the appointments table
        ]);

        $appointmentId = $validatedData['appointmentId'];

        try {
            // Find the appointment or fail if not found
            $appointment = Appointment::findOrFail($appointmentId);

            // Delete the appointment
            $appointment->delete();

            // Invalidate relevant cache entries to reflect updated data
            $userId = auth()->user()->id;
            // Optionally, uncomment the line below if you have specific cache keys for the doctor's appointments
            // $doctorCacheKey = "count_doctor_{$userId}_appointments";
            // Cache::forget($doctorCacheKey);

            $cacheKeys = [
                'count_appointments', // General cache key for appointments count
                "count_doctor_{$userId}_appointments", // Doctor-specific appointments count cache key
                "count_patient_{$userId}_appointments" // Patient-specific appointments count cache key
            ];

            // Clear the cache keys
            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }

            // Return a success response
            return response()->json([
                'status' => 'success',
                'message' => 'Appointment deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return an error response if appointment not found
            return response()->json([
                'status' => 'error',
                'message' => 'Appointment not found',
            ], 404);
        } catch (\Exception $e) {
            // Return a general error response
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the appointment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/v1/fetch-all-appointments",
     *     summary="Get all appointments",
     *     description="Fetches a list of all appointments from the database.",
     *     operationId="getAllAppointments",
     *     tags={"Appointments"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully fetched all appointments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="appointments", type="array",
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
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching appointments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching appointments"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function getAllAppointments(Request $request)
    {
        try {
            // Fetch all appointments
            $appointments = Appointment::all();

            // Return the appointments in the response
            return response()->json([
                'appointments' => $appointments,
            ], 200);
        } catch (\Exception $e) {
            // Log the error message
            Log::error('Error fetching appointments: ', ['error' => $e->getMessage()]);

            // Return an error response
            return response()->json([
                'message' => 'Error fetching appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/count-appointments",
     *     summary="Get counts of appointments by status",
     *     description="Fetches the total count of all appointments as well as counts of appointments by their statuses such as pending, completed, canceled, and scheduled.",
     *     operationId="countAppointments",
     *     tags={"Appointments"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully fetched appointment counts",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="allAppointments", type="integer", example=100),
     *             @OA\Property(property="pendingAppointments", type="integer", example=20),
     *             @OA\Property(property="completedAppointments", type="integer", example=60),
     *             @OA\Property(property="canceledAppointments", type="integer", example=10),
     *             @OA\Property(property="scheduledAppointments", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching and counting appointments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching and counting appointments"),
     *             @OA\Property(property="error", type="string", example="Detailed error message here")
     *         )
     *     )
     * )
     */

    public function countAppointments()
    {
        try {
            // Define the cache key for storing appointment counts
            $cacheKey = 'count_appointments';

            // Check if the cache already has the count data
            if (Cache::has($cacheKey)) {
                // Retrieve the count data from the cache
                $count = Cache::get($cacheKey);
            } else {
                // If not in cache, compute and cache the count data
                $count = Cache::rememberForever($cacheKey, function () {
                    return [
                        'allAppointments' => Appointment::count(), // Total number of appointments
                        'pendingAppointments' => Appointment::where('general_status', 'pending')->count(), // Count of pending appointments
                        'completedAppointments' => Appointment::where('general_status', 'completed')->count(), // Count of completed appointments
                        'canceledAppointments' => Appointment::where('general_status', 'canceled')->count(), // Count of canceled appointments
                        'scheduledAppointments' => Appointment::where('general_status', 'scheduled')->count(), // Count of scheduled appointments
                    ];
                });
            }

            // Return the count data as a JSON response
            return response()->json($count, 200);
        } catch (\Exception $e) {
            // Log the error message if an exception occurs
            Log::error('Error fetching and counting appointments: ', ['error' => $e->getMessage()]);

            // Return an error response
            return response()->json([
                'message' => 'Error fetching and counting appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/read-appointment-notifications",
     *     summary="Mark all unread notifications as read for the authenticated doctor",
     *     tags={"Doctor"},
     *     @OA\Response(
     *         response=200,
     *         description="Notifications marked as read",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Notifications marked as read."),
     *             @OA\Property(property="updated", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No unread notifications found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="info"),
     *             @OA\Property(property="message", type="string", example="No unread notifications found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="An error occurred while updating notifications.")
     *         )
     *     )
     * )
     */

    public function setNotificationsToRead()
    {
        try {
            // Update notifications for the authenticated doctor's ID where 'read_at' is null
            $updatedRows = AppointmentNotification::where('doctor_id', auth()->user()->doctor->id)
                ->whereNull('read_at')
                ->update(['read_at' => Carbon::now()]);

            // Check if any rows were updated and return a success message
            if ($updatedRows > 0) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Notifications marked as read.',
                    'updated' => $updatedRows,
                ], 200);
            } else {
                // If no rows were updated, return an info message
                return response()->json([
                    'status' => 'info',
                    'message' => 'No unread notifications found.',
                ], 404);
            }
        } catch (\Throwable $th) {
            // Log the error message if an exception occurs
            Log::error('Failed to mark notifications as read: ' . $th->getMessage());

            // Return an error response
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating notifications.',
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/appointments-events",
     *     summary="Get scheduled events for the authenticated doctor",
     *     tags={"Appointments"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="events", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="start", type="string", format="date-time", example="2024-09-20T09:00:00"),
     *                     @OA\Property(property="end", type="string", format="date-time", example="2024-09-20T10:00:00"),
     *                     @OA\Property(property="date", type="string", format="date", example="2024-09-20")
     *                 )
     *             ),
     *             @OA\Property(property="start_time", type="string", format="time", example="09:00:00"),
     *             @OA\Property(property="end_time", type="string", format="time", example="17:00:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching events",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error fetching events"),
     *             @OA\Property(property="error", type="string", example="Error details here")
     *         )
     *     )
     * )
     */
    public function getEvents()
    {
        try {
            // Fetch all scheduled appointments for the authenticated doctor
            $appointments = Appointment::where('doctor_id', auth()->user()->doctor->id)
                ->where('general_status', 'scheduled')->get();

            // Map appointments to the required event format
            $events = $appointments->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'start' => $appointment->start_time,
                    'end' => $appointment->end_time,
                    'date' => $appointment->date,
                ];
            });

            // Fetch the doctor's start and end times
            $doctorTime = StartEndTime::where('doctor_id', auth()->user()->doctor->id)->first();

            // Return the events and doctor's time as a JSON response
            return response()->json([
                'success' => true,
                'events' => $events,
                'start_time' => $doctorTime->start_time,
                'end_time' => $doctorTime->end_time
            ], 200);
        } catch (\Exception $e) {
            // Log the error message if an exception occurs
            Log::error('Error fetching events: ', ['error' => $e->getMessage()]);

            // Return an error response
            return response()->json([
                'success' => false,
                'message' => 'Error fetching events',
                'error' => $e->getMessage(),
            ], 500);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/v1/doctor-appointments-for-charts",
     *     summary="Get appointment statistics for the authenticated doctor",
     *     tags={"Appointments"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with appointment statistics",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="appointments", type="object"),
     *             @OA\Property(property="generalProfit", type="number", format="float", example=1500.00),
     *             @OA\Property(property="patientsByMonths", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching appointments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error fetching appointments"),
     *             @OA\Property(property="error", type="string", example="Error details here")
     *         )
     *     )
     * )
     */
    public function getAppointmentsForDoctorCharts()
    {
        try {
            // Initialize the array for monthly appointment statistics
            $months = [
                'January' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'February' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'March' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'April' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'May' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'June' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'July' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'August' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'September' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'October' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'November' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'December' => ['totalProfit' => 0, 'appointmentCount' => 0],
            ];

            // Fetch appointments for the authenticated doctor with statuses 'scheduled' or 'completed'
            $appointments = Appointment::where('doctor_id', auth()->user()->doctor->id)
                ->where('general_status', 'scheduled')
                ->orWhere('general_status', 'completed')
                ->get();

            $generalProfit = 0;
            // Calculate total profit and appointment count for each month
            foreach ($appointments as $app) {
                $month = Carbon::parse($app->date)->format('F');
                if (array_key_exists($month, $months)) {
                    $months[$month]['totalProfit'] += $app->doctor->appointment_price;
                    $months[$month]['appointmentCount'] += 1;
                }
            }

            // Calculate the total profit across all months
            foreach ($months as $month => $data) {
                $generalProfit += $data['totalProfit'];
            }

            // Fetch all patients
            $patients = Patient::all();
            $currentDate = Carbon::now();
            $patientsMonths = [];

            // Populate the array with the last 6 months
            for ($i = 0; $i < 6; $i++) {
                $month = $currentDate->copy()->subMonths($i)->format('F');
                $patientsMonths[$month] = [];
            }

            // Group patients by the month they were created
            foreach ($patients as $patient) {
                if ($patient->created_at) {
                    $createdMonth = new \DateTime($patient->created_at);
                    $monthName = $createdMonth->format('F');
                    if (array_key_exists($monthName, $patientsMonths)) {
                        $patientsMonths[$monthName][] = $patient;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'appointments' => $months,
                'generalProfit' => $generalProfit,
                'patientsByMonths' => $patientsMonths
            ], 200);
        } catch (\Exception $e) {
            // Log the error message if an exception occurs
            Log::error('Error fetching appointments: ', ['error' => $e->getMessage()]);

            // Return an error response
            return response()->json([
                'success' => false,
                'message' => 'Error fetching appointments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/doctor-upcoming-appointments",
     *     summary="Get upcoming appointments and top paying patients for the authenticated doctor",
     *     tags={"Appointments"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with upcoming appointments and top paying patients",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="upcomingAppointments", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="date", type="string", format="date", example="2024-09-30"),
     *                     @OA\Property(property="start_time", type="string", example="09:00"),
     *                     @OA\Property(property="end_time", type="string", example="09:30"),
     *                     @OA\Property(property="general_status", type="string", example="scheduled")
     *                 )
     *             ),
     *             @OA\Property(property="bestPayingPatients", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="userId", type="integer", example=1),
     *                     @OA\Property(property="user", type="object",
     *                         @OA\Property(property="name", type="string", example="John Doe"),
     *                         @OA\Property(property="email", type="string", example="john@example.com")
     *                     ),
     *                     @OA\Property(property="totalPaying", type="string", example="250.00")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching upcoming appointments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An error occurred while fetching upcoming appointments."),
     *             @OA\Property(property="error", type="string", example="Error details here")
     *         )
     *     )
     * )
     */

    public function getUpcomingAppointmentsAndTopPayingPatientsForDoctorCharts()
    {
        try {
            // Get the current date for filtering upcoming appointments
            $currentDate = Carbon::now()->format('Y-m-d');

            // Fetch the top 5 upcoming appointments for the authenticated doctor
            $upcomingAppointments = Appointment::where('doctor_id', auth()->user()->doctor->id)
                ->where('date', '>', $currentDate)
                ->where('general_status', 'scheduled')
                ->orderBy('date', 'desc')
                ->take(5)
                ->get();

            $patients = [];
            // Fetch all appointments excluding 'pending' status
            $allAppointments = Appointment::whereNot('general_status', 'pending')->get();

            // Calculate the total payment for each patient
            foreach ($allAppointments as $app) {
                $user = $app->user;
                $userId = $app->user->id;
                if (!isset($patients[$userId])) {
                    $patients[$userId] = [
                        'userId' => $userId,
                        'user' => $user,
                        'totalPaying' => 0.00
                    ];
                }
                $doctorPrice = floatval($app->doctor->appointment_price);
                $patients[$userId]['totalPaying'] += $doctorPrice;
            }

            // Format and sort patients by total payment in descending order
            foreach ($patients as &$patient) {
                $patient['totalPaying'] = number_format($patient['totalPaying'], 2, '.', '');
            }
            usort($patients, function ($a, $b) {
                return $b['totalPaying'] <=> $a['totalPaying']; // Sort in descending order
            });
            $patients = array_slice($patients, 0, 10);

            return response()->json([
                'success' => true,
                'upcomingAppointments' => $upcomingAppointments,
                'bestPayingPatients' => $patients
            ], 200);
        } catch (\Exception $e) {
            // Return an error response if an exception occurs
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching upcoming appointments.',
                'error' => $e->getMessage()
            ], 500);
        }
    }





    /**
     * @OA\Get(
     *     path="/api/v1/admin-appointments-for-charts",
     *     summary="Get appointment and order statistics for admin charts",
     *     tags={"Admin"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with appointment and order statistics",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="appointments", type="object",
     *                 @OA\Property(property="January", type="object",
     *                     @OA\Property(property="totalProfit", type="number", format="float", example=1000.00),
     *                     @OA\Property(property="appointmentCount", type="integer", example=10)
     *                 ),
     *                 @OA\Property(property="February", type="object",
     *                     @OA\Property(property="totalProfit", type="number", format="float", example=1200.00),
     *                     @OA\Property(property="appointmentCount", type="integer", example=12)
     *                 )
     *             ),
     *             @OA\Property(property="appointmentsGeneralProfit", type="number", format="float", example=22000.00),
     *             @OA\Property(property="topPayingPatients", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="userId", type="integer", example=1),
     *                     @OA\Property(property="user", type="object",
     *                         @OA\Property(property="name", type="string", example="Jane Doe"),
     *                         @OA\Property(property="email", type="string", example="jane@example.com")
     *                     ),
     *                     @OA\Property(property="totalPaying", type="string", example="500.00")
     *                 )
     *             ),
     *             @OA\Property(property="orders", type="object",
     *                 @OA\Property(property="January", type="object",
     *                     @OA\Property(property="orders", type="array",
     *                         @OA\Items(type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="total_price", type="number", format="float", example=200.00)
     *                         )
     *                     ),
     *                     @OA\Property(property="totalProfit", type="number", format="float", example=2000.00)
     *                 ),
     *                 @OA\Property(property="February", type="object",
     *                     @OA\Property(property="orders", type="array",
     *                         @OA\Items(type="object",
     *                             @OA\Property(property="id", type="integer", example=2),
     *                             @OA\Property(property="total_price", type="number", format="float", example=250.00)
     *                         )
     *                     ),
     *                     @OA\Property(property="totalProfit", type="number", format="float", example=2200.00)
     *                 )
     *             ),
     *             @OA\Property(property="ordersGeneralProfit", type="number", format="float", example=25000.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching appointments and orders stats",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error fetching appointments and orders stats"),
     *             @OA\Property(property="error", type="string", example="Error details here")
     *         )
     *     )
     * )
     */

    public function getAppointmentsAndOrdersStatsForAdminCharts()
    {
        try {
            // Initialize the array for monthly appointment statistics
            $appointmentsByMonths = [
                'January' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'February' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'March' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'April' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'May' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'June' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'July' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'August' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'September' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'October' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'November' => ['totalProfit' => 0, 'appointmentCount' => 0],
                'December' => ['totalProfit' => 0, 'appointmentCount' => 0],
            ];

            // Fetch appointments with statuses 'scheduled' or 'completed'
            $appointments = Appointment::where('general_status', 'scheduled')
                ->orWhere('general_status', 'completed')
                ->get();

            $appointmentsGeneralProfit = 0;
            // Calculate total profit and appointment count for each month
            foreach ($appointments as $app) {
                $month = Carbon::parse($app->date)->format('F');
                if (array_key_exists($month, $appointmentsByMonths)) {
                    $appointmentsByMonths[$month]['totalProfit'] += $app->doctor->appointment_price;
                    $appointmentsByMonths[$month]['appointmentCount'] += 1;
                }
            }

            // Calculate the total profit across all months
            foreach ($appointmentsByMonths as $month => $data) {
                $appointmentsGeneralProfit += $data['totalProfit'];
            }

            // Top paying patients section
            $patients = [];
            $allAppointments = Appointment::whereNot('general_status', 'pending')->get();
            foreach ($allAppointments as $app) {
                $user = $app->user;
                $userId = $app->user->id;
                if (!isset($patients[$userId])) {
                    $patients[$userId] = [
                        'userId' => $userId,
                        'user' => $user,
                        'totalPaying' => 0.00
                    ];
                }
                $doctorPrice = floatval($app->doctor->appointment_price);
                $patients[$userId]['totalPaying'] += $doctorPrice;
            }

            // Format and sort patients by total payment in descending order
            foreach ($patients as &$patient) {
                $patient['totalPaying'] = number_format($patient['totalPaying'], 2, '.', '');
            }
            usort($patients, function ($a, $b) {
                return $b['totalPaying'] <=> $a['totalPaying']; // Sort in descending order
            });
            $patients = array_slice($patients, 0, 10);

            // Initialize the array for monthly order statistics
            $ordersByMonths = [
                'January' => ['orders' => [], 'totalProfit' => 0],
                'February' => ['orders' => [], 'totalProfit' => 0],
                'March' => ['orders' => [], 'totalProfit' => 0],
                'April' => ['orders' => [], 'totalProfit' => 0],
                'May' => ['orders' => [], 'totalProfit' => 0],
                'June' => ['orders' => [], 'totalProfit' => 0],
                'July' => ['orders' => [], 'totalProfit' => 0],
                'August' => ['orders' => [], 'totalProfit' => 0],
                'September' => ['orders' => [], 'totalProfit' => 0],
                'October' => ['orders' => [], 'totalProfit' => 0],
                'November' => ['orders' => [], 'totalProfit' => 0],
                'December' => ['orders' => [], 'totalProfit' => 0],
            ];

            // Fetch orders with statuses 'pending' or 'completed'
            $orders = Order::where('status', 'paid')
                ->get();

            $ordersGeneralProfit = 0;
            // Calculate total profit and orders count for each month
            foreach ($orders as $order) {
                $month = Carbon::parse($order->created_at)->format('F');
                if (array_key_exists($month, $ordersByMonths)) {
                    $ordersByMonths[$month]['orders'][] = $order;
                    $ordersByMonths[$month]['totalProfit'] += $order->total_price;
                }
            }

            // Calculate the total profit across all months
            foreach ($ordersByMonths as $month => $data) {
                $ordersGeneralProfit += $data['totalProfit'];
            }

            return response()->json([
                'success' => true,
                'appointments' => $appointmentsByMonths,
                'appointmentsGeneralProfit' => $appointmentsGeneralProfit,
                'topPayingPatients' => $patients,
                'orders' => $ordersByMonths,
                'ordersGeneralProfit' => $ordersGeneralProfit
            ], 200);
        } catch (\Exception $e) {
            // Log the error message if an exception occurs
            Log::error('Error fetching appointments and orders stats: ', ['error' => $e->getMessage()]);

            // Return an error response
            return response()->json([
                'success' => false,
                'message' => 'Error fetching appointments and orders stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
