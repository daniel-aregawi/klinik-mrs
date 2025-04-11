<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class DoctorAppointmentController extends Controller
{


    /**
     * @OA\Get(
     *     path="/api/v1/fetch-doctor-appointments",
     *     summary="Get a paginated list of appointments for the authenticated doctor",
     *     tags={"Doctor"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of appointments to display per page",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of appointments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="total", type="integer", example=50),
     *             @OA\Property(property="per_page", type="integer", example=15)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching appointments"),
     *             @OA\Property(property="error", type="string", example="Error details here")
     *         )
     *     )
     * )
     */

    public function getDoctorAppointments(Request $request)
    {
        try {
            // Get the number of appointments to display per page from the request, default to 15 if not provided
            $perPage = $request->query('per_page', 15);

            // Retrieve appointments for the authenticated doctor
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
                ->where('doctor_id', auth()->user()->doctor->id) // Filter by authenticated doctor's ID
                ->paginate($perPage); // Paginate results

            // Transform appointment collection to include additional doctor and patient details
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
                    'doctor_name' => $appointment->doctor->name, // Additional doctor details
                    'doctor_email' => $appointment->doctor->email,
                    'patient_name' => $appointment->user->name, // Additional patient details
                    'patient_email' => $appointment->user->email
                ];
            });

            // Return paginated list of appointments with metadata
            return response()->json([
                'data' => $appointments->items(),
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage(),
                'total' => $appointments->total(),
                'per_page' => $perPage
            ], 200);
        } catch (\Exception $e) {
            // Log error and return error response
            Log::error('Error fetching appointments: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-doctor-appointments",
     *     summary="Get a paginated list of doctor appointments based on a search query",
     *     tags={"Doctor"},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         required=false,
     *         description="Search query to filter appointments by doctor or patient names",
     *         @OA\Schema(type="string", example="John Doe")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of searched appointments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total", type="integer", example=10),
     *             @OA\Property(property="per_page", type="integer", example=15),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching searched appointments"),
     *             @OA\Property(property="error", type="string", example="Error details here")
     *         )
     *     )
     * )
     */

    public function getSearchedDoctorAppointments(Request $request)
    {
        try {
            // Get the search query from the request
            $searchQuery = $request->query('search_query');

            // Retrieve appointments for the authenticated doctor with search functionality
            $appointments = Appointment::where(function ($query) use ($searchQuery) {
                // Search in doctor and patient names
                $query->whereHas('doctor', function ($q) use ($searchQuery) {
                    $q->where('name', 'like', '%' . $searchQuery . '%');
                })
                    ->orWhereHas('user', function ($q) use ($searchQuery) {
                        $q->where('name', 'like', '%' . $searchQuery . '%');
                    });
            })
                ->where('doctor_id', auth()->user()->doctor->id) // Filter by authenticated doctor's ID
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
                ->paginate(); // Paginate results

            // Transform appointment collection to include additional doctor and patient details
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
                    'doctor_name' => $appointment->doctor->name, // Additional doctor details
                    'doctor_email' => $appointment->doctor->email,
                    'patient_name' => $appointment->user->name, // Additional patient details
                    'patient_email' => $appointment->user->email
                ];
            });

            // Return paginated list of searched appointments with metadata
            return response()->json([
                'appointments' => $appointments,
                'total' => $appointments->count(),
                'per_page' => $appointments->perPage(),
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage()
            ], 200);
        } catch (\Exception $e) {
            // Log error and return error response
            Log::error('Error fetching searched appointments: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching searched appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/v1/fetch-doctor-appointments-by-status",
     *     summary="Get a paginated list of doctor appointments based on their payment or general status",
     *     tags={"Doctor"},
     *     @OA\Parameter(
     *         name="paymentStatus",
     *         in="query",
     *         required=true,
     *         description="Filter appointments by payment or general status (paid, unpaid, scheduled, completed, canceled)",
     *         @OA\Schema(type="string", example="paid", enum={"paid", "unpaid", "scheduled", "completed", "canceled"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of appointments by status",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total", type="integer", example=10),
     *             @OA\Property(property="per_page", type="integer", example=15),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching appointments by status"),
     *             @OA\Property(property="error", type="string", example="Error details here")
     *         )
     *     )
     * )
     */

    public function getDoctorAppointmentsByStatus(Request $request)
    {
        try {
            // Get the appointment status from the request
            $status = $request->query('paymentStatus');

            // Filter appointments based on the provided status
            if ($status === 'paid') {
                $appointments = Appointment::where('payment_status', 'paid')
                    ->where('doctor_id', auth()->user()->doctor->id)->paginate();
            } elseif ($status === 'unpaid') {
                $appointments = Appointment::where('payment_status', 'unpaid')
                    ->where('doctor_id', auth()->user()->doctor->id)->paginate();
            } elseif ($status === 'scheduled') {
                $appointments = Appointment::where('general_status', 'scheduled')
                    ->where('doctor_id', auth()->user()->doctor->id)->paginate();
            } elseif ($status === 'completed') {
                $appointments = Appointment::where('general_status', 'completed')
                    ->where('doctor_id', auth()->user()->doctor->id)->paginate();
            } elseif ($status === 'canceled') {
                $appointments = Appointment::where('general_status', 'canceled')
                    ->where('doctor_id', auth()->user()->doctor->id)->paginate();
            }

            // Transform appointment collection to include additional doctor and patient details
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
                    'doctor_name' => $appointment->doctor->name, // Additional doctor details
                    'doctor_email' => $appointment->doctor->email,
                    'patient_name' => $appointment->user->name, // Additional patient details
                    'patient_email' => $appointment->user->email
                ];
            });

            // Return paginated list of appointments by status with metadata
            return response()->json([
                'appointments' => $appointments->items(),
                'total' => $appointments->total(),
                'per_page' => $appointments->perPage(),
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage()
            ], 200);
        } catch (\Exception $e) {
            // Log error and return error response
            Log::error('Error fetching appointments by status: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching appointments by status',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Post(
     *     path="/api/v1/update-doctor-appointment",
     *     summary="Update the status of a specific appointment for the authenticated doctor",
     *     tags={"Doctor"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="appointmentId", type="integer", example=1),
     *             @OA\Property(property="paymentStatus", type="string", enum={"paid", "unpaid"}, example="paid"),
     *             @OA\Property(property="generalStatus", type="string", enum={"completed", "pending", "canceled"}, example="completed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Appointment updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Appointment updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Appointment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error updating appointment"),
     *             @OA\Property(property="error", type="string", example="Appointment not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error updating appointment"),
     *             @OA\Property(property="error", type="string", example="Error details here")
     *         )
     *     )
     * )
     */

    public function updateDoctorAppointment(Request $request)
    {
        try {
            // Validate the incoming request data
            $validatedData = $request->validate([
                'appointmentId' => 'required|integer|exists:appointments,id', // Ensure the appointment ID exists in the database
                'paymentStatus' => 'required|string|max:255|in:paid,unpaid', // Validate payment status
                'generalStatus' => 'required|string|max:255|in:completed,pending,canceled', // Validate general status
            ]);

            // Extract validated data
            $appointmentId = $validatedData['appointmentId'];
            $paymentStatus = $validatedData['paymentStatus'];
            $generalStatus = $validatedData['generalStatus'];

            // Find the appointment by ID or fail
            $appointment = Appointment::findOrFail($appointmentId);

            // Update the appointment with new status values
            $appointment->update([
                'payment_status' => $paymentStatus,
                'general_status' => $generalStatus
            ]);

            // Clear cached counts related to appointments
            $userId = auth()->user()->id;
            $cacheKeys = [
                'count_appointments',
                "count_doctor_{$userId}_appointments",
                "count_patient_{$userId}_appointments"
            ];

            foreach ($cacheKeys as $key) {
                Cache::forget($key); // Remove cache entries
            }

            // Return a success response
            return response()->json([
                'message' => 'Appointment updated successfully',
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a failure response
            Log::error('Error updating appointment: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error updating appointment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }




    /**
     * @OA\Post(
     *     path="/api/v1/delete-doctor-appointment",
     *     summary="Delete a specific appointment for the authenticated doctor",
     *     tags={"Doctor"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="appointmentId", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Appointment deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Appointment deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Appointment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Appointment not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the appointment"),
     *             @OA\Property(property="error", type="string", example="Error details here")
     *         )
     *     )
     * )
     */

    public function deleteDoctorAppointment(Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'appointmentId' => 'required|integer|exists:appointments,id', // Ensure the appointment ID exists
        ]);

        $appointmentId = $validatedData['appointmentId'];

        try {
            // Find and delete the appointment by ID
            $appointment = Appointment::findOrFail($appointmentId);
            $appointment->delete();

            // Clear cached counts related to appointments
            $userId = auth()->user()->id;
            $cacheKeys = [
                'count_appointments',
                "count_doctor_{$userId}_appointments",
                "count_patient_{$userId}_appointments"
            ];

            foreach ($cacheKeys as $key) {
                Cache::forget($key); // Remove cache entries
            }

            // Return a success response
            return response()->json([
                'status' => 'success',
                'message' => 'Appointment deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return a not found response if the appointment does not exist
            return response()->json([
                'status' => 'error',
                'message' => 'Appointment not found',
            ], 404);
        } catch (\Exception $e) {
            // Return a failure response and log the error
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the appointment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/v1/fetch-all-doctor-appointments",
     *     summary="Retrieve all appointments for the authenticated doctor",
     *     tags={"Doctor"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of all appointments",
     *         
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching appointments"),
     *             @OA\Property(property="error", type="string", example="Error details here")
     *         )
     *     )
     * )
     */

    public function getAllDoctorAppointments(Request $request)
    {
        try {
            // Retrieve all appointments for the authenticated doctor
            $appointments = Appointment::where('doctor_id', auth()->user()->doctor->id)->get();

            // Return the appointments in a success response
            return response()->json([
                'appointments' => $appointments,
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a failure response
            Log::error('Error fetching appointments: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/v1/count-doctor-appointments",
     *     summary="Count appointments for the authenticated doctor",
     *     tags={"Doctor"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of appointment counts",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="allAppointments", type="integer", example=10),
     *             @OA\Property(property="pendingAppointments", type="integer", example=3),
     *             @OA\Property(property="completedAppointments", type="integer", example=5),
     *             @OA\Property(property="canceledAppointments", type="integer", example=1),
     *             @OA\Property(property="scheduledAppointments", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching and counting appointments"),
     *             @OA\Property(property="error", type="string", example="Error details here")
     *         )
     *     )
     * )
     */

    public function countDoctorAppointments()
    {
        try {
            // Get the ID of the currently authenticated user
            $userId = auth()->user()->id;

            // Define a unique cache key for storing appointment counts
            $cacheKey = "count_doctor_{$userId}_appointments";

            // Check if the cache contains the appointment counts
            if (Cache::has($cacheKey)) {
                // Retrieve the count from the cache
                $count = Cache::get($cacheKey);
            } else {
                // Calculate appointment counts and store them in the cache
                $count = Cache::rememberForever($cacheKey, function () {
                    // Count all appointments for the authenticated doctor
                    $allAppointments = Appointment::where('doctor_id', auth()->user()->doctor->id)->count();

                    // Count appointments with different general statuses
                    $pendingAppointments = Appointment::where('general_status', 'pending')
                        ->where('doctor_id', auth()->user()->doctor->id)->count();
                    $completedAppointments = Appointment::where('general_status', 'completed')
                        ->where('doctor_id', auth()->user()->doctor->id)->count();
                    $canceledAppointments = Appointment::where('general_status', 'canceled')
                        ->where('doctor_id', auth()->user()->doctor->id)->count();
                    $scheduledAppointments = Appointment::where('general_status', 'scheduled')
                        ->where('doctor_id', auth()->user()->doctor->id)->count();

                    // Return the counts as an associative array
                    return [
                        'allAppointments' => $allAppointments,
                        'pendingAppointments' => $pendingAppointments,
                        'completedAppointments' => $completedAppointments,
                        'canceledAppointments' => $canceledAppointments,
                        'scheduledAppointments' => $scheduledAppointments,
                    ];
                });
            }

            // Return the counts in a success response
            return response()->json($count, 200);
        } catch (\Exception $e) {
            // Log the error and return a failure response if something goes wrong
            Log::error('Error fetching and counting appointments: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching and counting appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
