<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class ServiceController extends Controller
{


    /**
     * @OA\Post(
     *     path="/api/v1/add-service",
     *     summary="Add a new service",
     *     tags={"Service"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"serviceTitle", "servicePrice", "serviceStatus"},
     *             @OA\Property(property="serviceTitle", type="string", example="General Checkup"),
     *             @OA\Property(property="servicePrice", type="number", format="float", example=50.00),
     *             @OA\Property(property="serviceDescription", type="string", example="A comprehensive general checkup."),
     *             @OA\Property(property="serviceStatus", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="newService", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="General Checkup"),
     *                 @OA\Property(property="price", type="number", format="float", example=50.00),
     *                 @OA\Property(property="description", type="string", example="A comprehensive general checkup."),
     *                 @OA\Property(property="status", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-09-21T12:34:56Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-09-21T12:34:56Z")
     *             ),
     *             @OA\Property(property="message", type="string", example="Service added successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error adding service",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error adding service"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function addService(Request $request)
    {
        try {
            // Validate the incoming request to ensure required fields are provided and correctly formatted
            $validatedData = $request->validate([
                'serviceTitle' => 'required|string|max:255',
                'servicePrice' => 'required|numeric|between:0,99999999.99',
                'serviceDescription' => 'nullable|string',
                'serviceStatus' => 'required|boolean',
            ]);

            // Create a new service record in the database with the validated data
            $newService = Service::create([
                'title' => $validatedData['serviceTitle'],
                'price' => $validatedData['servicePrice'],
                'description' => $validatedData['serviceDescription'],
                'status' => $validatedData['serviceStatus'],
            ]);

            // Return a JSON response with the newly created service and a success message
            return response()->json([
                'newService' => $newService,
                'message' => 'Service added successfully',
            ], 200);
        } catch (\Exception $e) {
            // Log the exception message and return a JSON response with an error message
            Log::error('Error adding service: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error adding service',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-services",
     *     summary="Search for services by title",
     *     tags={"Service"},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         required=true,
     *         description="The title to search for in services",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of services matching the search query retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="General Checkup"),
     *                     @OA\Property(property="price", type="number", format="float", example=50.00),
     *                     @OA\Property(property="description", type="string", example="A comprehensive general checkup."),
     *                     @OA\Property(property="status", type="boolean", example=true)
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=3),
     *             @OA\Property(property="total", type="integer", example=30)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching searched services",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching searched services"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function getSearchedServices(Request $request)
    {
        try {
            // Retrieve the search query parameter from the request
            $searchQuery = $request->query('search_query');

            // Query the services table for services whose title matches the search query
            $services = Service::where('title', 'like', '%' . $searchQuery . '%')
                ->select('id', 'title', 'price', 'description', 'status')
                ->paginate();

            // Return a JSON response with the paginated services and metadata
            return response()->json([
                'data' => $services->items(),
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'total' => $services->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log the exception message and return a JSON response with an error message
            Log::error('Error fetching services: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching searched services',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-services",
     *     summary="Retrieve a list of services",
     *     tags={"Service"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of services retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="General Checkup"),
     *                     @OA\Property(property="price", type="number", format="float", example=50.00),
     *                     @OA\Property(property="description", type="string", example="A comprehensive general checkup."),
     *                     @OA\Property(property="status", type="boolean", example=true)
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="total", type="integer", example=100)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching services",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching services"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function getServices(Request $request)
    {
        try {
            // Retrieve the number of items per page from the query parameters, default to 15
            $perPage = $request->query('per_page', 15);

            // Query the services table and paginate the results
            $services = Service::select('id', 'title', 'price', 'description', 'status')
                ->paginate($perPage);

            // Return a JSON response with the paginated services and metadata
            return response()->json([
                'data' => $services->items(),
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'total' => $services->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log the exception message and return a JSON response with an error message
            Log::error('Error fetching services: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching services',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/delete-service",
     *     summary="Delete a service by ID",
     *     tags={"Service"},
     *     @OA\Parameter(
     *         name="serviceId",
     *         in="query",
     *         required=true,
     *         description="The ID of the service to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Service deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Service not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error deleting the service",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the service"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function deleteService(Request $request)
    {
        // Validate the request to ensure that the service ID is provided and exists in the services table
        $validatedData = $request->validate([
            'serviceId' => 'required|integer|exists:services,id',
        ]);

        $serviceId = $validatedData['serviceId'];

        try {
            // Find the service by ID and delete it from the database
            $service = Service::findOrFail($serviceId);
            $service->delete();

            // Return a JSON response indicating the service was deleted successfully
            return response()->json([
                'status' => 'success',
                'message' => 'Service deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return a JSON response if the service is not found
            return response()->json([
                'status' => 'error',
                'message' => 'Service not found',
            ], 404);
        } catch (\Exception $e) {
            // Log the exception and return a JSON response with an error message
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the service',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/update-service",
     *     summary="Update a service",
     *     tags={"Service"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="serviceId", type="integer", example=1),
     *             @OA\Property(property="serviceTitle", type="string", example="New Service Title"),
     *             @OA\Property(property="servicePrice", type="number", format="float", example=99.99),
     *             @OA\Property(property="serviceDescription", type="string", example="Updated service description."),
     *             @OA\Property(property="serviceStatus", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service updated successfully"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input data",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error updating service"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error updating service"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error updating service"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function updateService(Request $request)
    {
        try {
            // Validate the request to ensure required fields are provided and correctly formatted
            $validatedData = $request->validate([
                'serviceId' => 'required|integer|exists:services,id',
                'serviceTitle' => 'required|string|max:255',
                'servicePrice' => 'required|numeric|between:0,99999999.99',
                'serviceDescription' => 'nullable|string',
                'serviceStatus' => 'required|boolean',
            ]);

            $serviceId = $validatedData['serviceId'];

            // Find the service by ID or fail if not found
            $service = Service::findOrFail($serviceId);

            // Update the service with the new data
            $service->update([
                'title' => $validatedData['serviceTitle'],
                'price' => $validatedData['servicePrice'],
                'description' => $validatedData['serviceDescription'],
                'status' => $validatedData['serviceStatus'],
            ]);

            // Return a JSON response with the updated service and a success message
            return response()->json([
                'message' => 'Service updated successfully',
                'updatedService' => $service,
            ], 200);
        } catch (\Exception $e) {
            // Log the exception and return a JSON response with an error message
            Log::error('Error updating service: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error updating service',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-all-services",
     *     summary="Retrieve all services",
     *     tags={"Service"},
     *     @OA\Response(
     *         response=200,
     *         description="A list of all services",
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching services",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching services"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function getAllServices()
    {
        try {
            // Retrieve all service records from the database
            $services = Service::all();

            // Return a JSON response with all services
            return response()->json([
                'services' => $services,
            ], 200);
        } catch (\Exception $e) {
            // Log the exception and return a JSON response with an error message
            Log::error('Error fetching services: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching services',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
