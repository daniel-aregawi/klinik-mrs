<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use OpenApi\Annotations as OA;

class OfferController extends Controller
{


    /**
     * @OA\Post(
     *     path="/api/v1/add-offer",
     *     summary="Add a new offer",
     *     tags={"Offer"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Special Discount Offer"),
     *             @OA\Property(property="selectedServices", type="array", @OA\Items(type="integer", example=1)),
     *             @OA\Property(property="offerDiscountValue", type="number", format="float", example=10.00),
     *             @OA\Property(property="offerTaxRate", type="number", format="float", example=5.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Offer added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Offer added successfully"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input data",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error adding offer"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error adding offer"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function addOffer(Request $request)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'name' => 'required|string|max:255', // Validate that 'name' is required, a string, and does not exceed 255 characters
                'selectedServices' => 'required|array', // Validate that 'selectedServices' is required and must be an array
                'offerDiscountValue' => 'nullable|numeric|between:0,99999999.99', // Validate that 'offerDiscountValue' is a number between 0 and 99999999.99 if provided
                'offerTaxRate' => 'nullable|numeric|between:0,100', // Validate that 'offerTaxRate' is a number between 0 and 100 if provided
            ]);

            // Calculate the total price of selected services before discount
            $totalBeforeDiscount = 0;
            foreach ($validatedData['selectedServices'] as $serviceId) {
                $service = Service::findOrFail($serviceId); // Find the service by ID or fail if not found
                $totalBeforeDiscount += $service->price; // Sum the prices of selected services
            }

            // Calculate the total after discount and with tax
            $totalAfterDiscount = $totalBeforeDiscount - ($validatedData['offerDiscountValue'] ?? 0);
            $totalWithTax = number_format($totalAfterDiscount * (1 + (($validatedData['offerTaxRate'] ?? 0) / 100)), 2, '.', '');

            // Create a new offer
            $offer = Offer::create([
                'name' => $validatedData['name'],
                'total_before_discount' => $totalBeforeDiscount,
                'discount_value' => $validatedData['offerDiscountValue'] ?? 0,
                'total_after_discount' => $totalAfterDiscount,
                'tax_rate' => $validatedData['offerTaxRate'] ?? 0,
                'total_with_tax' => $totalWithTax,
            ]);

            // Attach selected services to the offer
            foreach ($validatedData['selectedServices'] as $service) {
                $offer->service_offer()->attach($service);
            }

            // Add services information to the offer response
            $offer['services'] = Service::whereIn('id', $validatedData['selectedServices'])->get();

            // Clear the cached offers if exists
            $cacheKey = 'our_offers';
            if (Cache::get($cacheKey)) {
                Cache::forget($cacheKey);
            }

            // Return a success response with the new offer details
            return response()->json([
                'message' => 'Offer added successfully',
                'newOffer' => $offer,
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response
            Log::error('Error adding offer: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error adding offer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/update-offer",
     *     summary="Update an existing offer",
     *     tags={"Offer"},
     *     @OA\Parameter(
     *         name="offerId",
     *         in="query",
     *         required=true,
     *         description="ID of the offer to be updated",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         required=true,
     *         description="Name of the offer",
     *         @OA\Schema(type="string", maxLength=255)
     *     ),
     *     @OA\Parameter(
     *         name="selectedServices",
     *         in="query",
     *         required=true,
     *         description="Array of selected service IDs",
     *         @OA\Schema(type="array", @OA\Items(type="integer"))
     *     ),
     *     @OA\Parameter(
     *         name="offerDiscountValue",
     *         in="query",
     *         required=false,
     *         description="Discount value for the offer",
     *         @OA\Schema(type="number", format="float", example=0.00)
     *     ),
     *     @OA\Parameter(
     *         name="offerTaxRate",
     *         in="query",
     *         required=false,
     *         description="Tax rate for the offer",
     *         @OA\Schema(type="number", format="float", example=0.00)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Offer updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Offer updated successfully"),
     *             @OA\Property(property="updatedOffer", type="object", description="Updated offer details")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error updating offer"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function updateOffer(Request $request)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'offerId' => 'required|integer|exists:offers,id', // Validate that 'offerId' is required, an integer, and exists in the 'offers' table
                'name' => 'required|string|max:255', // Validate that 'name' is required, a string, and does not exceed 255 characters
                'selectedServices' => 'required|array', // Validate that 'selectedServices' is required and must be an array
                'offerDiscountValue' => 'nullable|numeric|between:0,99999999.99', // Validate that 'offerDiscountValue' is a number between 0 and 99999999.99 if provided
                'offerTaxRate' => 'nullable|numeric|between:0,100', // Validate that 'offerTaxRate' is a number between 0 and 100 if provided
            ]);

            // Find the offer by ID or fail if not found
            $offer = Offer::findOrFail($validatedData['offerId']);

            // Calculate the total price of selected services before discount
            $totalBeforeDiscount = 0;
            foreach ($validatedData['selectedServices'] as $serviceId) {
                $service = Service::findOrFail($serviceId); // Find the service by ID or fail if not found
                $totalBeforeDiscount += $service->price; // Sum the prices of selected services
            }

            // Calculate the total after discount and with tax
            $totalAfterDiscount = $totalBeforeDiscount - ($validatedData['offerDiscountValue'] ?? 0);
            $totalWithTax = number_format($totalAfterDiscount * (1 + (($validatedData['offerTaxRate'] ?? 0) / 100)), 2, '.', '');

            // Update the offer details
            $offer->update([
                'name' => $validatedData['name'],
                'total_before_discount' => $totalBeforeDiscount,
                'discount_value' => $validatedData['offerDiscountValue'] ?? 0,
                'total_after_discount' => $totalAfterDiscount,
                'tax_rate' => $validatedData['offerTaxRate'] ?? 0,
                'total_with_tax' => $totalWithTax,
            ]);

            // Clear the cached offers if exists
            $cacheKey = 'our_offers';
            if (Cache::get($cacheKey)) {
                Cache::forget($cacheKey);
            }

            // Sync the related services (attach or detach as needed)
            $offer->service_offer()->sync($validatedData['selectedServices']);

            // Fetch the updated services for the offer
            $offer->services = Service::whereIn('id', $validatedData['selectedServices'])->get();

            // Return a success response with the updated offer details
            return response()->json([
                'message' => 'Offer updated successfully',
                'updatedOffer' => $offer,
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response
            Log::error('Error updating offer: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error updating offer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-offers",
     *     summary="Retrieve searched offers",
     *     tags={"Offer"},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         required=true,
     *         description="Search query to filter offers by name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of searched offers",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Special Discount Offer"),
     *                 @OA\Property(property="total_before_discount", type="number", format="float", example=100.00),
     *                 @OA\Property(property="discount_value", type="number", format="float", example=10.00),
     *                 @OA\Property(property="total_after_discount", type="number", format="float", example=90.00),
     *                 @OA\Property(property="tax_rate", type="number", format="float", example=5.00),
     *                 @OA\Property(property="total_with_tax", type="number", format="float", example=94.50),
     *                 @OA\Property(property="service_offer", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Consultation"),
     *                     @OA\Property(property="price", type="number", format="float", example=50.00)
     *                 ))
     *             )),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="total", type="integer", example=100)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching searched offers"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function getSearchedOffers(Request $request)
    {
        try {
            // Get the search query parameter
            $searchQuery = $request->query('search_query');

            // Retrieve offers matching the search query
            $offers = Offer::where('name', 'like', '%' . $searchQuery . '%')
                ->with(['service_offer:id,title,price']) // Eager load related services with specified fields
                ->select(
                    'id',
                    'name',
                    'total_before_discount',
                    'discount_value',
                    'total_after_discount',
                    'tax_rate',
                    'total_with_tax'
                )
                ->paginate(2); // Paginate results

            // Return a success response with the searched offers
            return response()->json([
                'data' => $offers->items(),
                'current_page' => $offers->currentPage(),
                'last_page' => $offers->lastPage(),
                'total' => $offers->total(),
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response
            Log::error('Error fetching offers: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching searched offers',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/v1/fetch-offers",
     *     summary="Retrieve paginated offers",
     *     tags={"Offer"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of offers",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Special Discount Offer"),
     *                 @OA\Property(property="total_before_discount", type="number", format="float", example=100.00),
     *                 @OA\Property(property="discount_value", type="number", format="float", example=10.00),
     *                 @OA\Property(property="total_after_discount", type="number", format="float", example=90.00),
     *                 @OA\Property(property="tax_rate", type="number", format="float", example=5.00),
     *                 @OA\Property(property="total_with_tax", type="number", format="float", example=94.50),
     *                 @OA\Property(property="service_offer", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Consultation"),
     *                     @OA\Property(property="price", type="number", format="float", example=50.00)
     *                 ))
     *             )),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="total", type="integer", example=100)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching offers"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function getOffers(Request $request)
    {
        try {
            // Get the number of items per page from the request, default to 15
            $perPage = $request->query('per_page', 15);

            // Retrieve paginated offers with related services, selecting specific fields
            $offers = Offer::with(['service_offer:id,title,price']) // Eager load related services and select specific fields
                ->select(
                    'id',
                    'name',
                    'total_before_discount',
                    'discount_value',
                    'total_after_discount',
                    'tax_rate',
                    'total_with_tax'
                )
                ->paginate($perPage); // Paginate results based on the 'per_page' value

            // Return a success response with paginated offer data
            return response()->json([
                'data' => $offers->items(), // The current page of offers
                'current_page' => $offers->currentPage(), // Current page number
                'last_page' => $offers->lastPage(), // Last page number
                'total' => $offers->total(), // Total number of offers
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response
            Log::error('Error fetching offers: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching offers',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Post(
     *     path="/api/v1/delete-offer",
     *     summary="Delete an offer",
     *     tags={"Offer"},
     *     @OA\Parameter(
     *         name="offerId",
     *         in="query",
     *         required=true,
     *         description="ID of the offer to be deleted",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Offer deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Offer deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Offer not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Offer not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the offer"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function deleteOffer(Request $request)
    {
        // Validate the request to ensure 'offerId' is provided and exists in the database
        $validatedData = $request->validate([
            'offerId' => 'required|integer|exists:offers,id', // 'offerId' must be an integer and exist in 'offers' table
        ]);

        $offerId = $validatedData['offerId']; // Extract the 'offerId' from validated data
        $cacheKey = 'our_offers'; // Cache key for offers
        if (Cache::get($cacheKey)) { // Check if the cache exists
            Cache::forget($cacheKey); // Forget the cache
        }

        try {
            // Find the offer by ID or fail if not found
            $offer = Offer::findOrFail($offerId);
            $offer->delete(); // Delete the offer

            // Return a success response
            return response()->json([
                'status' => 'success',
                'message' => 'Offer deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return a not found response if the offer does not exist
            return response()->json([
                'status' => 'error',
                'message' => 'Offer not found',
            ], 404);
        } catch (\Exception $e) {
            // Return a generic error response and log the error
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the offer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/fetch-services-in-offers",
     *     summary="Retrieve all active services",
     *     tags={"Offer"},
     *     @OA\Response(
     *         response=200,
     *         description="List of services retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="services", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="title", type="string")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error fetching services"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function getServices()
    {
        try {
            // Retrieve all services with status 1 and select specific fields
            $services = Service::select('id', 'title')
                ->where('status', 1) // Filter services by status
                ->get(); // Get all matching services

            // Return a success response with the list of services
            return response()->json([
                'services' => $services,
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a generic error response
            Log::error('Error fetching services: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching services',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
