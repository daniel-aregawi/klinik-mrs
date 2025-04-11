<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

class OurOffersController extends Controller
{


    /**
     * @OA\Get(
     *     path="/api/v1/fetch-our-offers",
     *     summary="Get available offers",
     *     tags={"Offers"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with offers",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Special Offer"),
     *                     @OA\Property(property="total_before_discount", type="number", format="float", example=100.00),
     *                     @OA\Property(property="discount_value", type="number", format="float", example=10.00),
     *                     @OA\Property(property="total_after_discount", type="number", format="float", example=90.00),
     *                     @OA\Property(property="tax_rate", type="number", format="float", example=5.00),
     *                     @OA\Property(property="total_with_tax", type="number", format="float", example=94.50),
     *                     @OA\Property(property="service_offer", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="Service Title"),
     *                         @OA\Property(property="price", type="number", format="float", example=90.00)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error fetching offers",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching offers"),
     *             @OA\Property(property="error", type="string", example="Error details here")
     *         )
     *     )
     * )
     */

    public function getOffers()
    {
        try {
            // Define the cache key for storing offers
            $cacheKey = 'our_offers';

            // Check if the offers are already cached
            if (Cache::has($cacheKey)) {
                // Retrieve offers from cache
                $offers = Cache::get($cacheKey);
            } else {
                // If not cached, fetch offers from the database and cache them
                $offers = Cache::rememberForever($cacheKey, function () {
                    return Offer::with(['service_offer:id,title,price']) // Load related service offer data
                        ->select(
                            'id',
                            'name',
                            'total_before_discount',
                            'discount_value',
                            'total_after_discount',
                            'tax_rate',
                            'total_with_tax'
                        )
                        ->get(); // Get all offers from the database
                });
            }

            // Return the offers in JSON format
            return response()->json([
                'data' => $offers, // The offers data
            ], 200);
        } catch (\Exception $e) {
            // Log any errors that occur
            Log::error('Error fetching offers: ', ['error' => $e->getMessage()]);

            // Return an error response
            return response()->json([
                'message' => 'Error fetching offers', // Error message
                'error' => $e->getMessage() // Detailed error message
            ], 500);
        }
    }
}
