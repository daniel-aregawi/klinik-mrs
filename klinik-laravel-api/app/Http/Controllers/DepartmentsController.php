<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class DepartmentsController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/v1/add-department",
     *     summary="Add a new department",
     *     tags={"Department"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "slug", "description"},
     *             @OA\Property(property="name", type="string", example="Cardiology"),
     *             @OA\Property(property="slug", type="string", example="cardiology"),
     *             @OA\Property(property="description", type="string", example="Department of Cardiology"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Department added successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="newDepartment", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while adding department",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function addDepartment(Request $request)
    {
        try {
            // Validate the incoming request data
            $validatedData = $request->validate([
                'name' => 'required|string',
                'slug' => 'required|string|unique:departments,slug', // Ensure slug is unique in the departments table
                'description' => 'required|string',
            ]);

            $name = $validatedData['name'];
            $slug = $validatedData['slug'];
            $description = $validatedData['description'];

            // Create a new department record in the database
            $newDepartment = Department::create([
                'name' => $name,
                'slug' => $slug,
                'desc' => $description,
            ]);

            // Return success response with the newly created department
            return response()->json([
                'newDepartment' => $newDepartment,
                'message' => 'Department added successfully',
            ], 200);
        } catch (\Exception $e) {
            // Log error and return a generic error response
            Log::error('Error adding department: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error adding department',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-departments",
     *     summary="Retrieve departments with pagination",
     *     tags={"Department"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items to display per page (default is 15)",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Departments retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="slug", type="string"),
     *                 @OA\Property(property="desc", type="string")
     *             )),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="per_page", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching departments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getDepartments(Request $request)
    {
        try {
            // Get the number of items per page from the query parameters (default to 15 if not provided)
            $perPage = $request->query('per_page', 15);

            // Retrieve departments with pagination
            $departments = Department::select('id', 'name', 'slug', 'desc')
                ->paginate($perPage);

            // Return departments with pagination details
            return response()->json([
                'data' => $departments->items(),
                'current_page' => $departments->currentPage(),
                'last_page' => $departments->lastPage(),
                'total' => $departments->total(),
                'per_page' => $perPage
            ], 200);
        } catch (\Exception $e) {
            // Log error and return a generic error response
            Log::error('Error fetching departments: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching departments',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-searched-departments",
     *     summary="Search for departments",
     *     tags={"Department"},
     *     @OA\Parameter(
     *         name="search_query",
     *         in="query",
     *         required=true,
     *         description="Search query to filter departments by name, slug, or description",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Departments retrieved successfully based on search query",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="departments", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="slug", type="string"),
     *                 @OA\Property(property="desc", type="string")
     *             )),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error occurred while fetching searched departments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getSearchedDepartments(Request $request)
    {
        try {
            // Get the search query from the request
            $searchQuery = $request->query('search_query');

            // Search for departments based on the query
            $departments = Department::where('name', 'like', '%' . $searchQuery . '%')
                ->orWhere('slug', 'like', '%' . $searchQuery . '%')
                ->orWhere('desc', 'like', '%' . $searchQuery . '%')
                ->select('id', 'name', 'slug', 'desc')
                ->paginate();

            // Return the search results with pagination details
            return response()->json([
                'departments' => $departments,
                'total' => $departments->count(),
                'per_page' => $departments->perPage(),
                'current_page' => $departments->currentPage(),
                'last_page' => $departments->lastPage()
            ], 200);
        } catch (\Exception $e) {
            // Log error and return a generic error response
            Log::error('Error fetching searched departments: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching searched departments',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * @OA\Post(
     *     path="/api/v1/delete-department",
     *     summary="Delete a department",
     *     tags={"Department"},
     *     @OA\Parameter(
     *         name="department_id",
     *         in="query",
     *         required=true,
     *         description="ID of the department to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Department deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Department deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Department not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Department not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while deleting the department",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function deleteDepartment(Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'department_id' => 'required|integer|exists:departments,id', // Ensure department_id is valid and exists
        ]);

        $departmentId = $validatedData['department_id'];

        try {
            // Find the department by ID and delete it
            $department = Department::findOrFail($departmentId);
            $department->delete();

            // Return success response
            return response()->json([
                'status' => 'success',
                'message' => 'Department deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Return error response if department not found
            return response()->json([
                'status' => 'error',
                'message' => 'Department not found',
            ], 404);
        } catch (\Exception $e) {
            // Return error response for other exceptions
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the department',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/v1/edit-department",
     *     summary="Edit a department",
     *     tags={"Department"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"department_id", "name", "slug", "desc"},
     *             @OA\Property(property="department_id", type="integer", example=1, description="ID of the department to edit"),
     *             @OA\Property(property="name", type="string", example="New Department Name", description="Name of the department"),
     *             @OA\Property(property="slug", type="string", example="new-department-slug", description="Unique slug for the department"),
     *             @OA\Property(property="desc", type="string", example="Description of the department", description="Description of the department")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Department updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Department updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Department not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error updating department"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while updating the department",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error updating department"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function editDepartment(Request $request)
    {
        try {
            // Validate the incoming request data
            $validatedData = $request->validate([
                'department_id' => 'required|integer|exists:departments,id', // Ensure department_id exists
                'name' => 'required|string',
                'slug' => [
                    'required',
                    'string',
                    Rule::unique('departments')->ignore($request->department_id), // Ensure slug is unique except for the current department
                ],
                'desc' => 'required|string',
            ]);

            $departmentId = $validatedData['department_id'];
            $name = $validatedData['name'];
            $slug = $validatedData['slug'];
            $description = $validatedData['desc'];

            // Find the department and update its details
            $department = Department::findOrFail($departmentId);
            $department->update([
                'name' => $name,
                'slug' => $slug,
                'desc' => $description
            ]);

            // Return success response
            return response()->json([
                'message' => 'Department updated successfully',
            ], 200);
        } catch (\Exception $e) {
            // Log error and return error response
            Log::error('Error updating department: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error updating department',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/v1/fetch-all-departments",
     *     summary="Get all departments",
     *     tags={"Department"},
     *     @OA\Response(
     *         response=200,
     *         description="A list of departments retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="departments",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1, description="ID of the department"),
     *                     @OA\Property(property="name", type="string", example="Department Name", description="Name of the department"),
     *                     @OA\Property(property="slug", type="string", example="department-name", description="Slug for the department"),
     *                     @OA\Property(property="desc", type="string", example="Description of the department", description="Description of the department")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while fetching departments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Error fetching departments"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getAllDepartments(Request $request)
    {
        try {
            // Retrieve all departments from the database
            $departments = Department::all();

            // Return the list of departments
            return response()->json([
                'departments' => $departments,
            ], 200);
        } catch (\Exception $e) {
            // Log error and return error response
            Log::error('Error fetching departments: ', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Error fetching departments',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
