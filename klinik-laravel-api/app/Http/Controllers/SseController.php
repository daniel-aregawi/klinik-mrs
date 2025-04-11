<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\StreamedResponse;
use OpenApi\Annotations as OA;

class SseController extends Controller
{


    /**
     * @OA\Get(
     *     path="/api/v1/sse",
     *     summary="Stream Real-Time Notifications",
     *     tags={"Notifications"},
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         required=true,
     *         description="The access token for authentication",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notifications streamed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="notifications", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="appointmentsNotifications", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Invalid or expired token",
     *     )
     * )
     */
    public function streamNotifications(Request $request)
    {
        // Retrieve the token from the query parameters
        $token = $request->query('token');

        // Find the access token from the database
        $accessToken = PersonalAccessToken::findToken($token);

        // Check if the token is valid and associated with a user
        if (!$accessToken || !$accessToken->tokenable) {
            abort(403, 'Invalid or expired token'); // Abort with 403 Forbidden if token is invalid
        }

        $user = $accessToken->tokenable; // Retrieve the user associated with the token

        // Create a StreamedResponse to handle real-time streaming of notifications
        $response = new StreamedResponse(function () use ($user) {
            // Initialize the start time for measuring elapsed time
            $startTime = microtime(true);

            while (true) {
                // Exit the loop after running for a short period (100 milliseconds)
                if ((microtime(true) - $startTime) > 0.1) { // Run for 100 milliseconds
                    break;
                }

                // Retrieve order notifications for the admin
                $orderNotifications = \App\Models\OrderNotification::where('user_id', $user->id)
                    ->get();

                // If the user is a doctor, retrieve appointment notifications for the doctor
                if ($user->doctor) {
                    $appointmentNotifications = \App\Models\AppointmentNotification::where('doctor_id', $user->doctor->id)
                        ->get();
                }

                // Prepare the data to be sent to the client
                $data = [
                    'notifications' => $orderNotifications,
                    'appointmentsNotifications' => $appointmentNotifications ?? []
                ];

                // Send the data to the client as a server-sent event
                echo 'data: ' . json_encode($data) . "\n\n";

                // Flush the output buffer and send data to the client
                ob_flush();
                flush();

                // Sleep for 50 milliseconds to balance performance and responsiveness
                usleep(50000); // 50 milliseconds
            }
        });

        // Set the headers for server-sent events
        $response->headers->set('Content-Type', 'text/event-stream'); // Specify the content type
        $response->headers->set('Cache-Control', 'no-cache'); // Disable caching
        $response->headers->set('X-Accel-Buffering', 'no'); // Disable buffering

        return $response; // Return the StreamedResponse
    }
}
