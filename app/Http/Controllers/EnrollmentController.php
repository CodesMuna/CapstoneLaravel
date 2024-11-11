<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $enrollments = Enrollment::all();
        return $enrollments;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEnrollmentRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
   public function show($LRN)
    {
        $enrollment = Enrollment::where('LRN', $LRN)->first();

        if (!$enrollment) {
            return response()->json(['message' => 'Enrollment not found'], 404);
        }

        return response()->json($enrollment);
    }

    public function getEnrollmentById($LRN)
{
    $enrollment = Enrollment::where('LRN', $LRN)->first();

    if (!$enrollment) {
        return response()->json(['message' => 'Enrollment not found'], 404);
    }

    return response()->json($enrollment);
}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $LRN)
{
    // Validate incoming request data
    $formFields = $request->validate([
        'last_attended' => 'nullable|string|max:255',
        'public_private' => 'nullable|string|max:10',
        'guardian_name' => 'nullable|string|max:255',
        'contact_no' => 'nullable|string|max:15',
    ]);

    // Find enrollment by LRN
    $enrollment = Enrollment::where('LRN', $LRN)->first();

    if (!$enrollment) {
        return response()->json(['message' => 'Enrollment not found'], 404);
    }

    // Update the enrollment record
    $enrollment->update($formFields);

    return response()->json(['message' => 'Enrollment updated successfully', 'data' => $enrollment], 200);
}

    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Enrollment $enrollment)
    {
        //
    }
}
