<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\DoctorTime;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Models\AppointmentNotification;
use App\Events\AppointmentNotifications;
use App\Mail\AppointmentReservasionSuccess;
use App\Models\AppointmentNotification as ModelsAppointmentNotification;
use App\Models\Patient;
use OpenApi\Annotations as OA;

class AppointmentReservation extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/v1/departments",
     *     summary="Get all departments",
     *     description="Retrieve a list of all departments.",
     *     operationId="getDepartments",
     *     tags={"Appointments Reservasion"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with the list of departments",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string", example="Departments retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error retrieving departments",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to retrieve departments"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function getDepartments()
    {
        try {
            // Fetch all departments from the database
            $departments = Department::all();

            // Return a JSON response with the list of departments
            return response()->json([
                'data' => $departments,
                'message' => 'Departments retrieved successfully',
            ], 200);
        } catch (\Exception $e) {
            // Handle exceptions by returning a JSON response with an error message
            return response()->json([
                'message' => 'Failed to retrieve departments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/appointments-fetch-doctors",
     *     summary="Get doctors by department",
     *     description="Retrieve a list of doctors based on the specified department ID.",
     *     tags={"Appointments Reservasion"},
     *     @OA\Parameter(
     *         name="departmentId",
     *         in="query",
     *         required=true,
     *         description="ID of the department to filter doctors by",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with the list of doctors",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string", example="Doctors retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error retrieving doctors",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to retrieve doctors"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function getDoctors(Request $request)
    {
        try {
            // Validate the request data to ensure 'departmentId' is required, an integer, and exists in the 'departments' table
            $validatedData = $request->validate([
                'departmentId' => 'required|integer|exists:departments,id',
            ]);

            // Fetch doctors from the specified department
            $doctors = Doctor::where('department_id', $validatedData['departmentId'])->get();

            // Return a JSON response with the list of doctors
            return response()->json([
                'data' => $doctors,
                'message' => 'Doctors retrieved successfully',
            ], 200);
        } catch (\Exception $e) {
            // Handle exceptions by returning a JSON response with an error message
            return response()->json([
                'message' => 'Failed to retrieve doctors',
                'error' => $e->getMessage(),
            ], 500);
        }
    }




    /**
     * @OA\Post(
     *     path="/api/v1/appointments-search-availability",
     *     summary="Search doctor availability",
     *     description="Search for available doctor times based on the specified date and doctor ID.",
     *     operationId="searchAvailability",
     *     tags={"Appointments Reservasion"},
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         required=true,
     *         description="Date for which to check availability, must be today or in the future",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="doctor",
     *         in="query",
     *         required=true,
     *         description="ID of the doctor to check availability for",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with available times",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Search success"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid date provided",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The date should be in the future!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Authentication required",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Authentication required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No available doctor times for the specified day",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No available doctor times for the specified day")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation error message")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error searching availability",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to search availability"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function searchAvailability(Request $request)
    {
        try {
            // Validate the request data to ensure 'date' is required, a date, and 'doctor' is required, an integer, and exists in the 'doctors' table
            $validatedData = $request->validate([
                'date' => 'required|date|after_or_equal:today',
                'doctor' => 'required|integer|exists:doctors,id',
            ]);

            // Check if the user is authenticated
            if (!auth()->check()) {
                return response()->json([
                    'message' => 'Authentication required',
                ], 401);
            }

            // Fetch doctor's available times
            $doctorTimes = DoctorTime::where('doctor_id', $validatedData['doctor'])->get();
            $parsedDate = Carbon::parse($validatedData['date']);
            $dayName = $parsedDate->format('l');

            // Check if the date is in the future
            if ($parsedDate->isFuture()) {
                // Loop through doctor's times to find availability
                foreach ($doctorTimes as $doctorTime) {
                    if ($doctorTime->day === $dayName) {
                        $availableOn = DoctorTime::where('doctor_id', $validatedData['doctor'])
                            ->where('day', $doctorTime->day)
                            ->where(function ($query) use ($validatedData) {
                                $query->whereNull('date')
                                    ->orWhereJsonDoesntContain('date', $validatedData['date']);
                            })
                            ->get();

                        // Return response based on availability
                        if ($availableOn->count() > 0) {
                            return response()->json([
                                'message' => 'Search success',
                                'data' => $availableOn, // Include available times in response
                            ], 200);
                        } else {
                            return response()->json([
                                'message' => 'No available doctor times',
                            ], 200);
                        }
                    }
                }

                // If no matching days are found
                return response()->json([
                    'message' => 'No available doctor times for the specified day',
                ], 404);
            } else {
                return response()->json([
                    'message' => 'The date should be in the future!',
                ], 400); // 400 Bad Request for invalid date
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation exceptions
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            // Handle general exceptions
            return response()->json([
                'message' => 'Failed to search availability',
                'error' => $e->getMessage(),
            ], 500);
        }
    }





    /**
     * @OA\Post(
     *     path="/api/v1/appointments-register-appointment",
     *     summary="Register a new appointment",
     *     description="Create a new appointment and initiate a payment through Stripe.",
     *     operationId="registerAppointment",
     *     tags={"Appointments Reservasion"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"day", "doctor", "startTime", "date"},
     *             @OA\Property(property="day", type="string", enum={"Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"}, example="Monday"),
     *             @OA\Property(property="doctor", type="integer", example=1),
     *             @OA\Property(property="startTime", type="string", example="09:00:00"),
     *             @OA\Property(property="date", type="string", format="date", example="2024-09-30")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Appointment registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Appointment registered successfully"),
     *             @OA\Property(property="checkout_url", type="string", example="https://checkout.stripe.com/..."),
     *             @OA\Property(property="appointment_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Doctor time slot not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Doctor time slot not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error registering appointment",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to register appointment"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function registerAppointment(Request $request)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'day' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
                'doctor' => 'required|integer|exists:doctors,id',
                'startTime' => 'required',
                'date' => 'required|date',
            ]);

            // Find the selected doctor's time slot
            $selectedTime = DoctorTime::where('doctor_id', $validatedData['doctor'])
                ->where('day', $validatedData['day'])
                ->where('start_time', $validatedData['startTime'])
                ->first();

            // If no matching time slot is found, return a 404 error
            if (!$selectedTime) {
                return response()->json([
                    'message' => 'Doctor time slot not found',
                ], 404);
            }

            // Add the selected date to the doctor's time slot
            $existingDates = $selectedTime->date ?? [];
            $existingDates[] = $validatedData['date'];
            $selectedTime->date = $existingDates;
            $selectedTime->save();

            // Prepare data for the appointment
            $price = $selectedTime->doctor->appointment_price;
            $doctorId = $validatedData['doctor'];
            $startAt = $validatedData['startTime'];
            $endAt = $selectedTime->end_time;
            $day = $validatedData['day'];

            // Create a Stripe checkout session
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));
            $checkoutSession = $stripe->checkout->sessions->create([
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'Appointment Reservation',
                        ],
                        'unit_amount' => $price * 100,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => config('app.frontend_url') . '/appointment-checkout/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => config('app.frontend_url') . '/appointment-checkout/cancel',
            ]);

            // Generate a unique order ID
            $orderId = uniqid() . mt_rand(100000, 999999);

            // Create a new appointment record
            $appointment = new Appointment();
            $appointment->order_id = $orderId;
            $appointment->payment_status = 'unpaid';
            $appointment->general_status = 'pending';
            $appointment->doctor_id = $doctorId;
            $appointment->day = $day;
            $appointment->start_time = $startAt;
            $appointment->end_time = $endAt;
            $appointment->session_id = $checkoutSession->id;
            $appointment->user_id = Auth::user()->id;
            $appointment->email = Auth::user()->email;
            $appointment->date = $validatedData['date'];
            $appointment->save();

            // Create an appointment notification
            AppointmentNotification::create([
                'doctor_id' => $appointment->doctor_id,
            ]);

            // Clear cached appointment counts
            $appointmentsCacheKey = 'count_appointments';
            if (Cache::has($appointmentsCacheKey)) {
                Cache::forget($appointmentsCacheKey);
            }

            // Clear cached doctor appointments counts
            $doctorCacheKey = "count_doctor_" . Auth::user()->id . "_appointments";
            if (Cache::has($doctorCacheKey)) {
                Cache::forget($doctorCacheKey);
            }

            // Return a successful response with the checkout URL
            return response()->json([
                'message' => 'Appointment registered successfully',
                'checkout_url' => $checkoutSession->url,
                'appointment_id' => $appointment->id
            ], 200);
        } catch (\Exception $e) {
            // Handle errors and return a JSON response with the error message
            return response()->json([
                'message' => 'Failed to register appointment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }





    /**
     * @OA\Post(
     *     path="/api/v1/appointment-success-payment",
     *     summary="Handle successful Stripe payment",
     *     description="Retrieve the Stripe session and update the appointment status upon successful payment.",
     *     operationId="paymentSuccess",
     *     tags={"Appointments Reservasion"},
     *     @OA\Parameter(
     *         name="sessionId",
     *         in="query",
     *         required=true,
     *         description="The session ID returned by Stripe after checkout.",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Payment successful."),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found or session not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Order not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error retrieving session",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Error retrieving session.")
     *         )
     *     )
     * )
     */
    public function success(Request $request)
    {
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));
        $sessionId = $request->get('sessionId');

        try {
            // Retrieve the Stripe session
            $session = $stripe->checkout->sessions->retrieve($sessionId);

            if (!$session) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session not found.'
                ], 404);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error retrieving session.'
            ], 500);
        }

        // Find the appointment associated with the session
        $checkAppointment = Appointment::where('session_id', $session->id)->first();

        if (!$checkAppointment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found.'
            ], 404);
        }

        // Update the appointment status if it’s unpaid
        if ($checkAppointment->payment_status === 'paid') {
            return response()->json([
                'status' => 'success',
                'message' => 'Payment successful.',
                'appointment' => $checkAppointment
            ]);
        }

        if ($checkAppointment->payment_status === 'unpaid') {
            $checkAppointment->payment_status = 'paid';
            $checkAppointment->general_status = 'scheduled';
            $checkAppointment->save();
        }

        // Send an email notification about the successful appointment
        $appointment = Appointment::where('user_id', Auth::user()->id)
            ->where('session_id', $session->id)
            ->first();

        // Clear cached appointment counts
        $appointmentsCacheKey = 'count_appointments';
        if (Cache::has($appointmentsCacheKey)) {
            Cache::forget($appointmentsCacheKey);
        }

        // Clear cached doctor appointments counts
        $doctorAppointmentsCacheKey = 'count_doctor_appointments';
        if (Cache::has($doctorAppointmentsCacheKey)) {
            Cache::forget($doctorAppointmentsCacheKey);
        }
        // Send Success reservasion email
        Mail::to(Auth::user()->email)->send(new AppointmentReservasionSuccess($appointment));

        // Return a response based on the appointment status
        if ($appointment) {
            return response()->json([
                'status' => 'success',
                'message' => 'Payment successful.',
                'appointment' => $appointment
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found for this user.'
            ], 404);
        }
    }
}
