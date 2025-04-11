<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class OrderController extends Controller
{


    /**
     * @OA\Get(
     *     path="/api/v1/fetch-orders",
     *     summary="Retrieve paginated orders",
     *     tags={"Order"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of results per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of orders retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="total_price", type="number", format="float"),
     *                 @OA\Property(property="session_id", type="string"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="order_id", type="string"),
     *                 @OA\Property(property="full_name", type="string"),
     *                 @OA\Property(property="gender", type="string"),
     *                 @OA\Property(property="national_card_id", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="per_page", type="integer", example=15)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching orders"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function getOrders(Request $request)
    {
        try {
            // Get the number of results per page from the request, defaulting to 15
            $perPage = $request->query('per_page', 15);

            // Retrieve orders with pagination, selecting specific fields
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
            )->paginate($perPage);

            // Return a success response with paginated orders data
            return response()->json([
                'data' => $orders->items(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
                'per_page' => $perPage
            ], 200);
        } catch (\Exception $e) {
            // Log the error details for debugging
            Log::error('Error fetching orders: ', ['error' => $e->getMessage()]);

            // Return a 500 error response with a message indicating failure
            return response()->json([
                'message' => 'Error fetching orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-orders",
     *     summary="Retrieve searched orders based on search query",
     *     tags={"Order"},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         description="Search query to filter orders by national card ID or full name",
     *         required=true,
     *         @OA\Schema(type="string", example="John Doe")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Searched orders retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="orders", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="total_price", type="number", format="float"),
     *                 @OA\Property(property="session_id", type="string"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="order_id", type="string"),
     *                 @OA\Property(property="full_name", type="string"),
     *                 @OA\Property(property="gender", type="string"),
     *                 @OA\Property(property="national_card_id", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="total", type="integer", description="Total number of orders"),
     *             @OA\Property(property="per_page", type="integer", description="Number of orders per page"),
     *             @OA\Property(property="current_page", type="integer", description="Current page"),
     *             @OA\Property(property="last_page", type="integer", description="Last page number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching searched orders"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function getSearchedOrders(Request $request)
    {
        try {
            // Get the search query from the request
            $searchQuery = $request->query('search_query');

            // Retrieve orders that match the search query in national card ID or full name, with pagination
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
                ->paginate();

            // Return a success response with paginated orders data
            return response()->json([
                'orders' => $orders,
                'total' => $orders->count(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage()
            ], 200);
        } catch (\Exception $e) {
            // Log the error details for debugging
            Log::error('Error fetching searched orders: ', ['error' => $e->getMessage()]);

            // Return a 500 error response with a message indicating failure
            return response()->json([
                'message' => 'Error fetching searched orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-orders-by-status",
     *     summary="Retrieve orders based on their status",
     *     tags={"Order"},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Status of the orders to filter by (paid/unpaid)",
     *         required=true,
     *         @OA\Schema(type="string", enum={"paid", "unpaid"}, example="paid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Orders retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="orders", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="total_price", type="number", format="float"),
     *                 @OA\Property(property="session_id", type="string"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="order_id", type="string"),
     *                 @OA\Property(property="full_name", type="string"),
     *                 @OA\Property(property="gender", type="string"),
     *                 @OA\Property(property="national_card_id", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="total", type="integer", description="Total number of orders"),
     *             @OA\Property(property="per_page", type="integer", description="Number of orders per page"),
     *             @OA\Property(property="current_page", type="integer", description="Current page"),
     *             @OA\Property(property="last_page", type="integer", description="Last page number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid status provided",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid status provided")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching orders by status"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function getOrdersByStatus(Request $request)
    {
        try {
            // Get the order status from the request
            $status = $request->query('status');

            // Retrieve orders based on the status with pagination
            if ($status === 'paid') {
                $orders = Order::where('status', 'paid')->paginate();
            } elseif ($status === 'unpaid') {
                $orders = Order::where('status', 'unpaid')->paginate();
            } else {
                // Handle case where status is invalid or not provided
                return response()->json([
                    'message' => 'Invalid status provided',
                ], 400);
            }

            // Return a success response with paginated orders data
            return response()->json([
                'orders' => $orders,
                'total' => $orders->count(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage()
            ], 200);
        } catch (\Exception $e) {
            // Log the error details for debugging
            Log::error('Error fetching orders by status: ', ['error' => $e->getMessage()]);

            // Return a 500 error response with a message indicating failure
            return response()->json([
                'message' => 'Error fetching orders by status',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Post(
     *     path="/api/v1/update-order",
     *     summary="Update an order's status",
     *     tags={"Order"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="orderId", type="integer", description="The ID of the order to update", example=123),
     *             @OA\Property(property="status", type="string", description="The new status of the order", example="paid")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error updating order",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error updating order"),
     *             @OA\Property(property="error", type="string", example="Error message here")
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
    public function updateOrder(Request $request)
    {
        try {
            // Validate incoming request data
            $validatedData = $request->validate([
                'orderId' => 'required|integer|exists:orders,id',
                'status' => 'required|string|max:255|in:paid,unpaid',
            ]);

            // Extract validated data
            $orderId = $validatedData['orderId'];
            $status = $validatedData['status'];

            // Find the order by ID or fail if not found
            $order = Order::findOrFail($orderId);

            // Update the status of the order
            $order->update([
                'status' => $status
            ]);

            // Get the authenticated user's ID
            $userId = auth()->user()->id;
            $cacheKey = "count_orders_patient_{$userId}";

            // Clear the relevant cache if it exists
            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
            }

            // Return a success response
            return response()->json([
                'message' => 'Order updated successfully',
            ], 200);
        } catch (\Exception $e) {
            // Log the error details for debugging
            Log::error('Error updating order: ', ['error' => $e->getMessage()]);

            // Return a 500 error response with a message indicating failure
            return response()->json([
                'message' => 'Error updating order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }




    /**
     * @OA\Post(
     *     path="/api/v1/delete-order",
     *     summary="Delete an order by ID",
     *     tags={"Order"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="orderId", type="integer", description="The ID of the order to delete", example=123)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Order deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Order not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while deleting the order",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the order"),
     *             @OA\Property(property="error", type="string", example="Error message here")
     *         )
     *     ),
     *     @OA\SecurityScheme(
     *         securityScheme="BearerAuth",
     *         type="http",
     *         scheme="bearer",
     *         bearerFormat="JWT"
     *     )
     * )
     */
    public function deleteOrder(Request $request)
    {
        // Validate incoming request data
        $validatedData = $request->validate([
            'orderId' => 'required|integer|exists:orders,id',
        ]);

        // Extract validated data
        $orderId = $validatedData['orderId'];

        try {
            // Find the order by ID or fail if not found
            $order = Order::findOrFail($orderId);

            // Delete the order
            $order->delete();

            // Get the authenticated user's ID
            $userId = auth()->user()->id;
            $cacheKey = "count_orders_patient_{$userId}";

            // Clear the relevant cache if it exists
            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
            }

            // Return a success response
            return response()->json([
                'status' => 'success',
                'message' => 'Order deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return a 404 error response if the order is not found
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found',
            ], 404);
        } catch (\Exception $e) {
            // Return a 500 error response for any other exceptions
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-all-orders",
     *     summary="Retrieve all orders",
     *     tags={"Order"},
     *     @OA\Response(
     *         response=200,
     *         description="Orders retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="orders", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="total_price", type="number", format="float"),
     *                 @OA\Property(property="session_id", type="string"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="order_id", type="string"),
     *                 @OA\Property(property="full_name", type="string"),
     *                 @OA\Property(property="gender", type="string"),
     *                 @OA\Property(property="national_card_id", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching orders"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function getAllOrders(Request $request)
    {
        try {
            // Retrieve all orders
            $orders = Order::all();

            // Return a success response with all orders
            return response()->json([
                'orders' => $orders,
            ], 200);
        } catch (\Exception $e) {
            // Log the error details for debugging
            Log::error('Error fetching orders: ', ['error' => $e->getMessage()]);

            // Return a 500 error response with a message indicating failure
            return response()->json([
                'message' => 'Error fetching orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/count-orders",
     *     summary="Get the count of all, paid, and unpaid orders",
     *     tags={"Order"},
     *     @OA\Response(
     *         response=200,
     *         description="Order counts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="allOrders", type="integer", example=150),
     *             @OA\Property(property="paidOrders", type="integer", example=90),
     *             @OA\Property(property="unpaidOrders", type="integer", example=60)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching and counting orders"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function countOrders()
    {
        try {
            // Define the cache key for storing order counts
            $cacheKey = 'count_orders';

            // Check if the cache already has the order count data
            if (Cache::has($cacheKey)) {
                // Retrieve the order count data from the cache
                $count = Cache::get($cacheKey);
            } else {
                // If the cache does not have the data, calculate the counts and store them in the cache
                $count = Cache::rememberForever($cacheKey, function () {
                    return [
                        // Count all orders
                        'allOrders' => Order::count(),
                        // Count paid orders
                        'paidOrders' => Order::where('status', 'paid')->count(),
                        // Count unpaid orders
                        'unpaidOrders' => Order::where('status', 'unpaid')->count(),
                    ];
                });
            }

            // Return a success response with the order counts
            return response()->json($count, 200);
        } catch (\Exception $e) {
            // Log the error details for debugging
            Log::error('Error fetching and counting orders: ', ['error' => $e->getMessage()]);

            // Return a 500 error response with a message indicating failure
            return response()->json([
                'message' => 'Error fetching and counting orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
