<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class UpdateUserInfo extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/v1/update-password",
     *     summary="Update user password",
     *     description="Allows an authenticated user to update their password.",
     *     operationId="updatePassword",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"currentPassword", "newPassword", "confirmPassword"},
     *             @OA\Property(property="currentPassword", type="string", description="The current password of the user."),
     *             @OA\Property(property="newPassword", type="string", description="The new password of the user."),
     *             @OA\Property(property="confirmPassword", type="string", description="Confirmation of the new password.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request, validation errors or password mismatch",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Current password is incorrect")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function updatePassword(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'currentPassword' => 'required|string',
            'newPassword' => 'required|string|min:8',
            'confirmPassword' => 'required|string|min:8',
        ]);

        // Return validation errors if validation fails
        if ($validator->fails()) {
            $firstErrorMessage = $validator->errors()->first();
            return response()->json(['error' => $firstErrorMessage], 400);
        }

        // Retrieve the authenticated user
        $user = User::where('id', auth()->user()->id)->first();

        // Check if the current password matches the stored password
        if (Hash::check($request->currentPassword, $user->password)) {
            // Check if the new password and confirmation password match
            if ($request->newPassword == $request->confirmPassword) {
                // Update the user's password
                $user->password = Hash::make($request->newPassword);
                $user->save();
                return response()->json(['message' => 'Password updated successfully'], 200);
            }
            // Return an error if the new password and confirmation password do not match
            return response()->json(['error' => 'New Password and Password Confirmation doesn\'t match!'], 400);
        } else {
            // Return an error if the current password is incorrect
            return response()->json(['error' => 'Current password is incorrect'], 400);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/update-name",
     *     summary="Update user name",
     *     description="Allows an authenticated user to update their name.",
     *     operationId="updateName",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", description="The new name of the user", maxLength=255)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Name updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Name updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request, validation errors or failed update",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to update name")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function updateName(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        // Return validation errors if validation fails
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        // Retrieve the authenticated user
        $user = User::where('id', auth()->user()->id)->first();

        // Return an error if the user is not found
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        try {
            // Update the user's name
            $user->update([
                'name' => $request->name
            ]);
            // Invalidate the cache if it exists
            $cacheKey = "userData_{$user->id}";
            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
            }
            return response()->json(['message' => 'Name updated successfully'], 200);
        } catch (\Throwable $th) {
            // Log any errors and return an error response
            Log::error('Failed to update name: ', ['error' => $th->getMessage()]);
            return response()->json(['error' => 'Failed to update name'], 400);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/update-email",
     *     summary="Update user email",
     *     description="Allows an authenticated user to update their email address.",
     *     operationId="updateEmail",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", description="The new email address of the user", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Email updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request, validation errors or failed update",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to update email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function updateEmail(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
        ]);

        // Return validation errors if validation fails
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        // Retrieve the authenticated user
        $user = auth()->user();

        // Return an error if the user is not found
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        try {
            // Update the user's email
            $user->email = $request->email;
            $user->save();
            // Invalidate the cache if it exists
            $cacheKey = "userData_{$user->id}";
            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
            }
            return response()->json(['message' => 'Email updated successfully'], 200);
        } catch (\Throwable $th) {
            // Log any errors and return an error response
            Log::error('Failed to update email: ', ['error' => $th->getMessage()]);
            return response()->json(['error' => 'Failed to update email'], 400);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/update-social-links",
     *     summary="Update user's social links",
     *     description="Allows an authenticated user to update their social media links (website, Facebook, Twitter, Instagram).",
     *     operationId="updateSocialLinks",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="website", type="string", nullable=true, maxLength=255, example="https://example.com"),
     *             @OA\Property(property="facebook", type="string", nullable=true, maxLength=255, example="https://facebook.com/user"),
     *             @OA\Property(property="twitter", type="string", nullable=true, maxLength=255, example="https://twitter.com/user"),
     *             @OA\Property(property="instagram", type="string", nullable=true, maxLength=255, example="https://instagram.com/user")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Social links updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Social links updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request, validation errors or failed update",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to update social links")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function updateSocialLinks(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'website' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
            'twitter' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
        ]);

        // Return validation errors if validation fails
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        // Retrieve the authenticated user
        $user = auth()->user();

        // Return an error if the user is not found
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        try {
            // Update social links if they are present in the request
            if ($request->has('website')) {
                $user->website = $request->website;
            }
            if ($request->has('facebook')) {
                $user->facebook = $request->facebook;
            }
            if ($request->has('twitter')) {
                $user->twitter = $request->twitter;
            }
            if ($request->has('instagram')) {
                $user->instagram = $request->instagram;
            }

            // Save the updated user details
            $user->save();
            // Invalidate the cache if it exists
            $cacheKey = "userData_{$user->id}";
            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
            }
            return response()->json(['message' => 'Social links updated successfully'], 200);
        } catch (\Throwable $th) {
            // Log any errors and return an error response
            Log::error('Failed to update social links: ', ['error' => $th->getMessage()]);
            return response()->json(['error' => 'Failed to update social links'], 400);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/update-image",
     *     summary="Update user's profile image",
     *     description="Allows an authenticated user to update their profile image.",
     *     operationId="updateImage",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"image"},
     *                 @OA\Property(property="image", type="string", format="binary", description="Profile image file")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Image uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="string", example="Image uploaded successfully"),
     *             @OA\Property(property="path", type="string", example="/storage/images/avatar.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request or validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid image format")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function updateImage(Request $request)
    {
        // Validate the image file
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Retrieve the authenticated user
        $user = auth()->user();

        // Delete the old image if it exists
        if ($user->avatar) {
            $userImage = 'images/' . basename($user->avatar);
            if (Storage::disk('public')->exists($userImage)) {
                Storage::disk('public')->delete($userImage);
            }
        }

        // Return validation errors if validation fails
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        // Store the new image and update the user's avatar path
        $path = $request->file('image')->store('images', 'public');
        $user->avatar = $path;
        $user->save();
        // Invalidate the cache if it exists
        $cacheKey = "userData_{$user->id}";
        if (Cache::has($cacheKey)) {
            Cache::forget($cacheKey);
        }
        return response()->json(['success' => 'Image uploaded successfully', 'path' => Storage::url($path)], 200);
    }
}
