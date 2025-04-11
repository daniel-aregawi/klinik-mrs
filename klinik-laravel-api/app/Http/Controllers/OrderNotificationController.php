<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\OrderNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

class OrderNotificationController extends Controller
{


    /**
     * @OA\get(
     *     path="/api/v1/read-order-notifications",
     *     summary="Mark all notifications as read for the authenticated user",
     *     tags={"Notifications"},
     *     @OA\Response(
     *         response=200,
     *         description="Notifications marked as read or no unread notifications",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="string", example="success"),
     *                     @OA\Property(property="message", type="string", example="Notifications marked as read."),
     *                     @OA\Property(property="updated", type="integer", example=5)
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="string", example="info"),
     *                     @OA\Property(property="message", type="string", example="No unread notifications found.")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error marking notifications as read",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="An error occurred while updating notifications.")
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Bearer token for authentication"
     *     ),
     *     @OA\SecurityScheme(
     *         securityScheme="BearerAuth",
     *         type="http",
     *         scheme="bearer",
     *         bearerFormat="JWT"
     *     )
     * )
     */
    public function setNotificationsToRead()
    {
        try {
            // Get the ID of the currently authenticated user
            $userId = Auth::id();

            // Update the 'read_at' field for unread notifications belonging to the user
            $updatedRows = OrderNotification::where('user_id', $userId)
                ->whereNull('read_at') // Only target notifications that haven't been read yet
                ->update(['read_at' => Carbon::now()]); // Set the 'read_at' field to the current timestamp

            // If one or more rows were updated, return a success response
            if ($updatedRows > 0) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Notifications marked as read.',
                    'updated' => $updatedRows, // Number of notifications updated
                ], 200);
            } else {
                // If no rows were updated, return an informational response indicating no unread notifications
                return response()->json([
                    'status' => 'info',
                    'message' => 'No unread notifications found.',
                ], 200);
            }
        } catch (\Throwable $th) {
            // Log any exceptions that occur
            Log::error('Failed to mark notifications as read: ' . $th->getMessage());

            // Return an error response if something goes wrong
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating notifications.',
            ], 500);
        }
    }
}
