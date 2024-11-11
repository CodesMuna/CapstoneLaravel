<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $students = Student::all()->map(function ($student) {
            $student->profile = Storage::url($student->profile); // Ensure this returns a full URL
            return $student;
        });
    
        return response()->json($students);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){ 
        //
    }

    public function enrollment(Request $request) // Sign Up
    {
        // Validate incoming request data
        $formField = $request->validate([
            'LRN' => 'required|integer|unique:students,LRN', 
            'fname' => 'required|string|max:255', 
            'lname' => 'required|string|max:255', 
            'email' => 'required|email|max:255|unique:students,email',
            'password' => 'required|string',
            
        ]);

        DB::transaction(function () use ($formField, $request) {
            // Insert into the students table
            $student = Student::create($formField);

            // Handle enrollment data from request
            $enrollmentData = $request->validate([
                'grade_level' => 'required|string|max:50',
                'strand' => 'nullable|string|max:100',
                'school_year' => 'required|string|max:10', // Add validation for school_year
            ]);

            // Insert into the enrollments table
            DB::table('enrollments')->insert([
                'LRN' => $student->LRN,
                'grade_level' => $enrollmentData['grade_level'],
                'strand' => $enrollmentData['strand'],
                'school_year' => $enrollmentData['school_year'], // Include school_year here
                'date_register' => now(),
                'created_at' => now(),
            ]);
        });

        return response()->json(['message' => 'Student enrolled successfully'], 201);
    }
        
    public function Studentlogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:students,email', // Ensure you're checking against the correct column
            'password' => 'required|string|min:8'
        ]);

        // Fetch student along with enrollment data
        $student = Student::with('enrollment') // Eager load the enrollment relationship
            ->where('email', $request->email)
            ->first();

        if (!$student || !Hash::check($request->password, $student->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Create token for the student
        $token = $student->createToken($student->fname)->plainTextToken;

        return response()->json([
            'student' => [
                'LRN' => $student->LRN,
                'fname' => $student->fname,
                'lname' => $student->lname,
                'contact_no' => $student->contact_no,
                'email' => $student->email,
                'profile' => $student->profile,
                'grade_level' => $student->enrollment ? $student->enrollment->grade_level : null, // Include grade_level
            ],
            'token' => $token,
        ]);
    }

    public function Studentlogout(Request $request){
        $request->user()->tokens()->delete();
        return[
            'message' => 'You are logged out'
        ];
    }

    public function getStudentById($LRN)
    {
        // Use Query Builder to fetch data
        $student = DB::table('students')
            ->join('enrollments', 'students.LRN', '=', 'enrollments.LRN')
            ->select(
                'students.*', 
                'enrollments.grade_level' // Include grade_level
            )
            ->where('students.LRN', $LRN)
            ->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        return response()->json($student);
    }

    public function uploadProfile(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'profile' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        
        // Check if the user is authenticated
        // if (!auth()->check()) {
        //     return response()->json(['message' => 'Unauthorized'], 401);
        // }
        
        // $user = auth()->user(); // Get authenticated user

        // Create a filename with an underscore followed by the LRN
        // $filename = $user->name . 'profile_' . $user->LRN . '.' . $request->file('profile')->getClientOriginalExtension();

        // Store the uploaded file
        // $path = $request->file('profile')->storeAs('profiles', $filename, 'public');

        // Update the student's profile image path in the database
        // $user->profile = $path; // Set the profile path
        // $user->save(); // Save changes to the database

        // return response()->json(['message' => 'Profile updated successfully!', 'path' => $path], 200);
    }

    public function getProfileImage($lrn)
{
    $student = Student::where('LRN', $lrn)->first();

    if (!$student || !$student->profile) {
        return response()->json(['message' => 'Profile image not found'], 404);
    }

    // Manually set the base URL for the profile image
    $baseUrl = 'http://localhost:8000'; // Change this to your desired base URL
    $imagePath = $student->profile; // Assuming this is the relative path stored in the database

    // Construct the full URL
    $fullImageUrl = $baseUrl . '/storage/' . $imagePath;

    return response()->json(['image_url' => $fullImageUrl], 200);
}

    /**
     * Display the specified resource.
     */
    public function show($LRN)
    {
        // Use Query Builder to fetch data
        $student = DB::table('students')
            ->join('enrollments', 'students.LRN', '=', 'enrollments.LRN')
            ->select(
                'students.*', 
                'enrollments.grade_level'
                ) // Adjust fields as necessary
            ->where('students.LRN', $LRN)
            ->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        return response()->json($student);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student)
    {
        $formFields = $request->validate([
            'LRN' => 'required|exists:students',
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:255',
            'bdate' => 'nullable|date',
            'bplace' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:255',
            'religion' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'contact_no' => 'nullable|max:11',
        ]);
    
        $student->update($formFields);
        return response()->json($student, 200);
    }

    public function updatePassword(Request $request) {
        // Validate the incoming request
        $formFields = $request->validate([
            'LRN' => 'required|exists:students,LRN', // Ensure LRN exists in the students table
            'password' => 'required|string|min:8', // Add confirmed rule for password confirmation
        ]);
    
        // Find the student by LRN
        $student = Student::where('LRN', $formFields['LRN'])->first();
    
        // Update the password
        $student->password = bcrypt($formFields['password']);
        $student->save();
    
        // Return a response
        return response()->json(['message' => 'Password updated successfully!']);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        //
    }
}
