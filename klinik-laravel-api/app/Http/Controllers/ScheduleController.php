<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\DoctorTime;
use App\Models\StartEndTime;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class ScheduleController extends Controller
{


    /**
     * @OA\Post(
     *     path="/api/v1/doctor-schedules-set-times",
     *     summary="Set or update doctor's available times",
     *     tags={"Doctor"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="startTime", type="string", format="time", example="09:00"),
     *             @OA\Property(property="endTime", type="string", format="time", example="17:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Doctor times updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Doctor times updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="doctor_id", type="integer", example=1),
     *                 @OA\Property(property="start_time", type="string", example="09:00"),
     *                 @OA\Property(property="end_time", type="string", example="17:00"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-09-22T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-09-22T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Doctor times created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Doctor times created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="doctor_id", type="integer", example=1),
     *                 @OA\Property(property="start_time", type="string", example="09:00"),
     *                 @OA\Property(property="end_time", type="string", example="17:00"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-09-22T12:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-09-22T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Doctor not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Doctor not found in doctors table!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An error occurred"),
     *             @OA\Property(property="error", type="string", example="Error details here")
     *         )
     *     )
     * )
     */

    public function setTimes(Request $request)
    {
        // Validate the request data to ensure 'startTime' and 'endTime' are provided and in the correct format
        $validatedData = $request->validate([
            'startTime' => 'required|date_format:H:i',
            'endTime' => 'required|date_format:H:i',
        ]);

        try {
            // Check if the authenticated user is a doctor
            $isDoctorExists = Doctor::where('user_id', auth()->user()->id)->first();
            if (!$isDoctorExists) {
                return response()->json([
                    'message' => 'Doctor not found in doctors table!',
                ], 404);
            }

            // Get the doctor's ID
            $doctor = auth()->user()->doctor->id;

            // Find existing time records for the doctor
            $doctorTimes = StartEndTime::where('doctor_id', $doctor)->first();

            if ($doctorTimes) {
                // Update the existing time records
                $doctorTimes->update([
                    'doctor_id' => $doctor,
                    'start_time' => $validatedData['startTime'],
                    'end_time' => $validatedData['endTime']
                ]);

                return response()->json([
                    'message' => 'Doctor times updated successfully',
                    'data' => $doctorTimes
                ], 200);
            } else {
                // Create new time records if none exist
                $newTimes = StartEndTime::create([
                    'doctor_id' => $doctor,
                    'start_time' => $validatedData['startTime'],
                    'end_time' => $validatedData['endTime']
                ]);

                return response()->json([
                    'message' => 'Doctor times created successfully',
                    'data' => $newTimes
                ], 201);
            }
        } catch (\Throwable $th) {
            // Return an error response if an exception occurs
            return response()->json([
                'message' => 'An error occurred',
                'error' => $th->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/doctor-schedules-fetch-times",
     *     summary="Get doctor's available times",
     *     tags={"Doctor"},
     *     @OA\Response(
     *         response=200,
     *         description="Doctor's times retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="start_time", type="string", format="time", example="09:00"),
     *                 @OA\Property(property="end_time", type="string", format="time", example="17:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No time data found for this doctor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No time data found for this doctor.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching times"),
     *             @OA\Property(property="error", type="string", example="Error details here")
     *         )
     *     )
     * )
     */

    public function getTimes()
    {
        try {
            // Get the authenticated doctor's ID
            $doctorId = auth()->user()->doctor->id;

            // Retrieve the doctor's start and end times
            $times = StartEndTime::where('doctor_id', $doctorId)
                ->select('start_time', 'end_time')
                ->first();

            if (!$times) {
                // Return a response if no time data is found for the doctor
                return response()->json([
                    'message' => 'No time data found for this doctor.',
                ], 404);
            }

            // Format the times
            $startTime = \Carbon\Carbon::parse($times->start_time)->format('H:i');
            $endTime = \Carbon\Carbon::parse($times->end_time)->format('H:i');

            return response()->json([
                'data' => [
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ],
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return an error response
            Log::error('Error fetching times: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching times',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/v1/doctor-schedules-fetch-availability",
     *     summary="Get doctor's availability times",
     *     tags={"Doctor"},
     *     @OA\Response(
     *         response=200,
     *         description="Available times retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="doctor_id", type="integer", example=1),
     *                 @OA\Property(property="day", type="string", example="Monday"),
     *                 @OA\Property(property="start_time", type="string", format="time", example="09:00"),
     *                 @OA\Property(property="end_time", type="string", format="time", example="09:15")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No availability data found for this doctor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No availability data found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching availability"),
     *             @OA\Property(property="error", type="string", example="Error details here")
     *         )
     *     )
     * )
     */

    public function getAvailability()
    {
        // Get the authenticated doctor
        $doctor = auth()->user()->doctor;
        // Retrieve the doctor's start and end times
        $doctorTimes = StartEndTime::where('doctor_id', $doctor->id)->first();

        // Convert start and end times to minutes for easy comparison
        $startTime = $doctorTimes->start_time;
        list($hours, $minutes) = explode(':', $startTime);
        $startTimeInMinutes = ($hours * 60) + $minutes;
        $endTime = $doctorTimes->end_time;
        list($hours, $minutes) = explode(':', $endTime);
        $endTimeInMinutes = ($hours * 60) + $minutes;

        $timesArray = [];

        // Generate a list of times in 15-minute intervals between start and end times
        for ($currentTime = $startTimeInMinutes; $currentTime <= $endTimeInMinutes; $currentTime += 15) {
            $hours = floor($currentTime / 60);
            $minutes = $currentTime % 60;

            $formattedTime = sprintf('%02d:%02d', $hours, $minutes);
            $timesArray[] = $formattedTime;
        }

        // List of days of the week
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $availableTimes = [];

        // Check for available times for each day of the week
        foreach ($days as $day) {
            foreach ($timesArray as $time) {
                $checkAvailableTimes = DoctorTime::where([
                    'doctor_id' => $doctor->id,
                    'day' => $day,
                    'start_time' => $time
                ])->first();
                if ($checkAvailableTimes) {
                    $availableTimes[] = $checkAvailableTimes;
                }
            }
        }

        // Return the available times as a JSON response
        return response()->json($availableTimes);
    }





    /**
     * @OA\Post(
     *     path="/api/v1/doctor-schedules-toggle-availability",
     *     summary="Toggle doctor's availability for a specific day and time",
     *     tags={"Doctor"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="day", type="string", example="Monday", enum={"Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"}),
     *             @OA\Property(property="start_time", type="string", format="time", example="09:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Schedule updated or deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Schedule Updated Successfully"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="An error occurred"),
     *             @OA\Property(property="error", type="string", example="Error details here")
     *         )
     *     )
     * )
     */

    public function toggleAvailability(Request $request)
    {
        // Validate the incoming request to ensure 'day' and 'start_time' are provided and in the correct format
        $validator = Validator::make($request->all(), [
            'day' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
        ]);

        // If validation fails, return a JSON response with validation errors
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Retrieve the authenticated doctor's information
        $doctor = auth()->user()->doctor;
        $day = $request->day; // Day of the week for the schedule
        $startTime = $request->start_time; // Start time for the schedule
        $appointmentTime = 15; // Default appointment duration in minutes

        // Calculate the end time by adding the appointment duration to the start time
        $calculatedEndTime = Carbon::createFromFormat('H:i', $startTime)
            ->addMinutes($appointmentTime)
            ->format('H:i');

        // Check if a schedule already exists for the given doctor, day, and time range
        $scheduleExists = DoctorTime::where([
            'doctor_id' => $doctor->id,
            'day' => $day,
            'start_time' => $startTime,
            'end_time' => $calculatedEndTime,
        ])->exists();

        if ($scheduleExists) {
            // If the schedule exists, delete it
            DoctorTime::where([
                'doctor_id' => $doctor->id,
                'day' => $day,
                'start_time' => $startTime,
                'end_time' => $calculatedEndTime,
            ])->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Schedule Deleted Successfully',
                'data' => null
            ], 200);
        } else {
            // If the schedule does not exist, create or update it
            $schedule = DoctorTime::updateOrCreate([
                'doctor_id' => $doctor->id,
                'day' => $day,
                'start_time' => $startTime,
                'end_time' => $calculatedEndTime,
            ], ['appointment_time' => 15]);

            return response()->json([
                'status' => 'success',
                'message' => 'Schedule Updated Successfully',
                'data' => $schedule
            ], 200);
        }
    }
}
