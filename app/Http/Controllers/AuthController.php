<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Message;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    //Register, Login,  Logout

    public function register(Request $request){
        
        $formField = $request->validate([
            'fname' => 'required|max: 255',
            'lname' => 'required|max: 255',
            'mname' => 'nullable|max: 255',
            'role' => 'required|max: 255',
            'address' => 'required|max: 255',
            'email' => 'required|email',
            'password' => 'required|confirmed'
        ]);

        $register = Admin::create($formField);
        return $register;
    }

    public function login(Request $request){
        $request->validate([
            'email' => 'required|email|exists:admins',
            'password' => 'required'
        ]);

        $admin = Admin::where('email', $request->email)
            // ->where('role', '=', 'Registrar')
            ->first();
        if(!$admin || !Hash::check($request->password,$admin->password)){
            return [
                'message' => 'The provided credentials are incorrect'
            ];
        }

        $token = $admin->createToken($admin->fname);
        $role = $admin->role;

        return [
            'admin' => $admin,
            'role' => $role,
            'token' => $token->plainTextToken
        ];
    }

    public function logout(Request $request){
        $request->user()->tokens()->delete();
        return [
            'message' => 'You are logged out'
        ];
    }

    //Home Functions

    public function message(){
        $latestMessages = $data = DB::table('messages')
        ->leftJoin('students', 'messages.message_sender', '=', 'students.LRN')
        ->leftJoin('admins', 'messages.message_reciever', '=', 'admins.admin_id')
        ->select('messages.*', 
                 DB::raw('CONCAT(students.fname, " ",LEFT(students.mname, 1), ". ", students.lname) as student_name'),
                 DB::raw('CONCAT(admins.fname, " ",LEFT(admins.mname, 1), ". ", admins.lname) as admin_name'))
        ->get();

        return $latestMessages;
    }

    public function getInquiries(Request $request){
        $uid = $request->input('uid');

        $latestMessages = DB::table('messages')
        ->select('message_sender', DB::raw('MAX(created_at) as max_created_at'))
        ->groupBy('message_sender');

        $data = DB::table('messages')
            ->leftJoin('students', function ($join) {
                $join->on('messages.message_sender', '=', 'students.LRN');
            })
            ->leftJoin('parent_guardians', function ($join) {
                $join->on('messages.message_sender', '=', 'parent_guardians.guardian_id');
            })
            ->leftJoin('admins', 'messages.message_reciever', '=', 'admins.admin_id')
            // ->joinSub($latestMessages, 'latest_messages', function ($join) {
            //     $join->on('messages.message_sender', '=', 'latest_messages.message_sender')
            //          ->on('messages.created_at', '=', 'latest_messages.max_created_at');
            // })
            ->whereNotIn('messages.message_sender', function ($query) {
                $query->select('admin_id')->from('admins');
            })
            ->where('messages.message_reciever', '=', $uid)
            // ->join('admins as sender_admin', 'messages.message_sender', '=', 'sender_admin.admin_id')
            // ->join('students as reciever', 'messages.message_reciever', '=', 'reciever.LRN')
            ->select('messages.*', 
                    DB::raw('CASE 
                    WHEN messages.message_sender IN (SELECT LRN FROM students) THEN 
                    CONCAT(students.fname, " ", 
                        CASE 
                            WHEN students.mname IS NOT NULL THEN CONCAT(LEFT(students.mname, 1), ". ") 
                            ELSE "" 
                        END, 
                    students.lname)
                WHEN messages.message_sender IN (SELECT guardian_id FROM parent_guardians) THEN 
                    CONCAT(parent_guardians.fname, " ", 
                        CASE 
                            WHEN parent_guardians.mname IS NOT NULL THEN CONCAT(LEFT(parent_guardians.mname, 1), ". ") 
                            ELSE "" 
                        END, 
                    parent_guardians.lname)
            END as sender_name'),
                    DB::raw('CONCAT(admins.fname, " ",COALESCE(LEFT(admins.mname, 1),""), ". ", admins.lname)as admin_name'))
            ->havingRaw('sender_name IS NOT NULL')
            ->orderBy('messages.created_at', 'desc')
            ->get();
    
        return $data;
    }

    //Enrollment Functions

    public function enrollments(){
        $currentYear = now()->year;
        $nextYear = $currentYear + 1;
        $schoolYear = $currentYear . '-' . $nextYear;
        // $enrollments  = Enrollment::with('student')->get();
        $enrollments = DB::table('enrollments')
                        ->leftJoin('students', 'enrollments.LRN', '=', 'students.LRN')
                        ->where('enrollments.school_year', '=', '2024-2025')
                        // ->where('enrollments.regapproval_date', '!=', null)
                        ->select('enrollments.*', 'students.*', 
                            DB::raw('CONCAT(students.fname, " ",LEFT(students.mname, 1), ". ", students.lname)as full_name'))
                        ->get();
                        
        return $enrollments;
    }

    public function enrollmentinfo($eid){
        $data = DB::table('students')
            ->join('enrollments', 'students.LRN', '=', 'enrollments.LRN')
            ->leftJoin('payments', 'students.LRN', '=', 'payments.LRN')
            // ->where('students.LRN', '=' ,  $lrn)
            ->where('enrollments.enrol_id', '=' ,  $eid)
            // ->select('students.*', 'enrollments.*')
            ->select('students.*', 'enrollments.*', 'payments.*')
            ->first();
    
        return response()->json($data);
    }

    public function approval(Request $request, $eid){
        DB::table('enrollments')
            ->where('enrol_id', $eid)
            ->update([
                'regapproval_date' => now()
            ]);
        return response()->json(['message' => 'Payment approved successfully']);
    }

    public function deleteEnrollment($eid){
        DB::table('enrollments')
            ->where('enrol_id', $eid)
            ->delete();

        return response()->json(['message' => 'Enrollment deleted successfully']);
    }

    //Class Functions

    public function getClasses(){
        $klases = DB::table('classes')
            ->join('sections', 'classes.section_id', '=', 'sections.section_id')
            ->leftJoin('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
            ->select('classes.*', 'subjects.*', 'sections.*')
            ->get();

        return $klases;
    }

    //Section Functions

    public function getSections(Request $request) {
        $gradeLevel = $request->query('gradeLevel'); // Get the grade level from the query parameters
        $strand = $request->query('strand'); // Get the strand from the query parameters
    
        $query = DB::table('sections')->select('sections.*');
    
        if ($gradeLevel) {
            $query->where('grade_level', $gradeLevel);
        }
    
        if ($strand) {
            $query->where('strand', $strand); // Assuming 'strand' is a column in your sections table
        }
    
        $sections = $query->get();
    
        return response()->json($sections);
    }

    public function getSubjects(Request $request) {
        $gradeLevel = $request->query('gradeLevel'); // Get the grade level from the query parameters
        $strand = $request->query('strand'); // Get the strand from the query parameters

        $query = DB::table('subjects')->select('subjects.*');
    
        if ($gradeLevel) {
            $query->where('grade_level', $gradeLevel);
        }
    
        if ($strand) {
            $query->where('strand', $strand); // Assuming 'strand' is a column in your sections table
        }

        $subjects = $query->get();
    
        return response()->json($subjects);
    }

    

    //Roster Functions

    public function createRoster(Request $request) {
        try {
            $classIds = $request->input('cid'); // Expecting an array of class IDs
    
            // Prepare data for insertion
            $data = [];
            foreach ($classIds as $cid) {
                $data[] = [
                    'class_id' => $cid,
                    'LRN' => null, // Assuming you want to set LRN to null for new rosters
                ];
            }
    
            // Insert multiple records into the rosters table
            DB::table('rosters')->insert($data);
    
            return response()->json([
                'success' => true,
                'message' => 'Rosters created successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getRosters(){
        $rosters = DB::table('rosters')
            ->join('students', 'rosters.LRN', '=', 'students.LRN')
            ->join('classes', 'rosters.class_id', '=', 'classes.class_id')
            ->join('admins', 'classes.admin_id', '=', 'admins.admin_id')
            ->join('sections', 'classes.section_id', '=', 'sections.section_id')
            ->leftJoin('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
            ->select('rosters.*', 'classes.*', 'admins.*', 'subjects.*', 'sections.*','students.contact_no', DB::raw('CONCAT(students.fname, " ",LEFT(students.mname, 1), ". ", students.lname)as student_name'))
            ->get();

        return $rosters;
    }

    public function getFilteredRosters(Request $request){
        $gradeLevel = $request->input('gradelevel');
        $section = $request->input('section');
        $strand = $request->input('strand');
        $gender = $request->input('gender');
        
        $rosters = DB::table('rosters')
            ->join('students', 'rosters.LRN', '=', 'students.LRN')
            ->join('classes', 'rosters.class_id', '=', 'classes.class_id')
            ->join('admins', 'classes.admin_id', '=', 'admins.admin_id')
            ->join('sections', 'classes.section_id', '=', 'sections.section_id')
            ->leftJoin('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
            ->where('sections.grade_level', '=', $gradeLevel)
            ->where('sections.section_name', '=', $section)
            ->when($strand, function ($query, $strand) {
                if ($strand !== '-') {
                    return $query->where('subjects.strand', '=', $strand);
                }
            })
            ->when($gender, function ($query, $gender) {
                return $query->where('students.gender', '=', $gender);
            })
            ->select('rosters.*', 'classes.*', 'admins.*', 'subjects.*', 'students.contact_no', 'students.gender',
                DB::raw('CONCAT(students.fname, " ",LEFT(students.mname, 1), ". ", students.lname)as student_name'))
            ->get();
        
        return $rosters;
    }

    public function getRosterInfo(Request $request) {
        $classIds = explode(',', $request->input('classIds')); // Split the comma-separated string into an array
    
        $data = DB::table('rosters')
            ->join('students', 'rosters.LRN', '=', 'students.LRN')
            ->join('classes', 'rosters.class_id', '=', 'classes.class_id')
            ->join('sections', 'classes.section_id', '=', 'sections.section_id')
            ->leftJoin('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
            ->whereIn('rosters.class_id', $classIds) // Use whereIn to filter by multiple class IDs
            ->select('rosters.*', 'students.*', 'classes.*', 'subjects.*', 'sections.*', 
                DB::raw('CONCAT(students.fname, " ", LEFT(students.mname, 1), ". ", students.lname) as student_name'))
            ->get();
    
        return response()->json($data);
    }

    //Rostering Functions

    public function getClassInfo(Request $request) {
        // Validate the input
        $request->validate([
            'classIds' => 'required|string', // Ensure classIds is provided and is a string
        ]);
    
        $classIds = explode(',', $request->input('classIds')); // Split the string into an array
    
        // Fetch class information
        try {
            $data = DB::table('classes')
                ->join('sections', 'classes.section_id', '=', 'sections.section_id')
                ->leftJoin('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
                ->whereIn('classes.class_id', $classIds) // Use whereIn for multiple IDs
                ->select('classes.*', 'sections.*', 'subjects.subject_name') // Include subject name if needed
                ->get();
    
            return response()->json($data); // Return data as JSON
        } catch (\Exception $e) {
            // Log the error and return a response
            Log::error('Error fetching class info: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch class information.'], 500);
        }
    }

    public function getEnrolees($lvl){
        $currentYear = now()->year;
        $nextYear = $currentYear + 1;
        $schoolYear = $currentYear . '-' . $nextYear;
        
        $data = DB::table('enrollments')
            ->join('students', 'enrollments.LRN', '=', 'students.LRN')
            ->leftJoin('rosters', 'students.LRN', '=', 'rosters.LRN')
            ->where('enrollments.grade_level', '=', $lvl)
            ->where('enrollments.school_year', '=', $schoolYear)
            ->where('enrollments.regapproval_date', '!=', '0000-00-00')
            ->whereNull('rosters.LRN')
            ->select('students.*','enrollments.*', DB::raw('CONCAT(students.fname, " ",LEFT(students.mname, 1), ". ", students.lname)as student_name'))
            ->get();
        
        return response()->json($data);
    }

    // public function addStudent(Request $request) {
    //     $classIds = $request->input('cid'); // Expecting an array of class IDs
    //     $lrn = $request->input('lrn');
    
    //     // Get student's grade level
    //     $student = DB::table('students')
    //         ->leftJoin('enrollments', 'students.LRN', '=', 'enrollments.LRN')
    //         ->where('enrollments.LRN', '=', $lrn) // Ensure you filter by LRN
    //         ->select('enrollments.grade_level')
    //         ->first();
    
    //     // Check if student exists and retrieve grade level
    //     if (!$student) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Student not found.'
    //         ], 404);
    //     }
    
    //     $lvl = $student->grade_level;
    
    //     // Prepare data for insertion into rosters
    //     $datarsoter = [];
    //     foreach ($classIds as $cid) {
    //         $datarsoter[] = [
    //             'class_id' => $cid,
    //             'LRN' => $lrn
    //         ];
    //     }
    
    //     // Insert multiple records into the rosters table
    //     DB::table('rosters')->insert($datarsoter);
    
    //     // Prepare data for insertion into grades
    //     $datagrades = [];
        
    //     // Determine grading terms based on grade level
    //     if ($lvl >= 7 && $lvl <= 10) {
    //         $gradingTerms = ['First Quarter', 'Second Quarter', 'Third Quarter', 'Fourth Quarter'];
    //     } elseif ($lvl == 11 || $lvl == 12) {
    //         $gradingTerms = ['Midterm', 'Final'];
    //     } else {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Invalid grade level.'
    //         ], 400);
    //     }
    
    //     foreach ($classIds as $cid) {
    //         foreach ($gradingTerms as $term) {
    //             $datagrades[] = [
    //                 'class_id' => $cid,
    //                 'LRN' => $lrn,
    //                 'term' => $term, // Include the term
    //                 'permission' => 'none' // Set initial permission if needed
    //             ];
    //         }
    //     }
    
    //     // Insert multiple records into the grades table
    //     DB::table('grades')->insert($datagrades);
    
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Student added to classes successfully',
    //     ]);
    // }

    public function addStudent(Request $request) {
        $classIds = $request->input('cid'); // Expecting an array of class IDs
        $lrn = $request->input('lrn');
    
        // Get student's grade level
        $student = DB::table('students')
            ->leftJoin('enrollments', 'students.LRN', '=', 'enrollments.LRN')
            ->where('enrollments.LRN', '=', $lrn) // Ensure you filter by LRN
            ->select('enrollments.grade_level')
            ->first();
    
        // Check if student exists and retrieve grade level
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found.'
            ], 404);
        }
    
        $lvl = $student->grade_level;
    
        // Prepare data for insertion into rosters
        $datarsoter = [];
        foreach ($classIds as $cid) {
            $datarsoter[] = [
                'class_id' => $cid,
                'LRN' => $lrn
            ];
        }
    
        // Insert multiple records into the rosters table
        DB::table('rosters')->insert($datarsoter);
    
        // Check if grades already exist for this student
        $existingGrades = DB::table('grades')
            ->where('LRN', '=', $lrn)
            ->exists(); // Check for existence
    
        if ($existingGrades) {
            return response()->json([
                'success' => true,
                'message' => 'Student added to roster successfully, but grades already exist. No new grades added.'
            ]);
        }
    
        // Prepare data for insertion into grades
        $datagrades = [];
        
        // Determine grading terms based on grade level
        if ($lvl >= 7 && $lvl <= 10) {
            $gradingTerms = ['First Quarter', 'Second Quarter', 'Third Quarter', 'Fourth Quarter'];
        } elseif ($lvl == 11 || $lvl == 12) {
            $gradingTerms = ['Midterm', 'Final'];
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid grade level.'
            ], 400);
        }
    
        foreach ($classIds as $cid) {
            foreach ($gradingTerms as $term) {
                $datagrades[] = [
                    'class_id' => $cid,
                    'LRN' => $lrn,
                    'term' => $term, // Include the term
                    'permission' => 'none' // Set initial permission if needed
                ];
            }
        }
    
        // Insert multiple records into the grades table only if no existing grades were found
        DB::table('grades')->insert($datagrades);
    
        return response()->json([
            'success' => true,
            'message' => 'Student added to classes successfully',
        ]);
    }

    public function removeStudent(Request $request) {
        $lrn = $request->input('lrn'); // Expecting the LRN of the student to be removed
    
        // Log the received data
        Log::info('Removing student with LRN: ' . $lrn);
    
        // Remove the student from the roster based on LRN
        DB::table('rosters')->where('LRN', $lrn)->delete();
    
        // Check if there are any grades with non-null values for this student
        $grades = DB::table('grades')
            ->where('LRN', $lrn)
            ->whereNotNull('grade') // Ensure we only consider rows where grade is not null
            ->get();
    
        if ($grades->isEmpty()) {
            // If no grades are found with a non-null value, delete all grades for this student
            DB::table('grades')->where('LRN', $lrn)->delete();
            Log::info('All grades deleted for LRN: ' . $lrn);
        } else {
            // Log that some grades were not deleted due to having non-null values
            Log::info('Grades not deleted for LRN: ' . $lrn . ' because they have non-null values.');
        }
    
        return response()->json([
            'success' => true,
            'message' => 'Student removed from all classes successfully',
        ]);
    }

    // public function removeStudent(Request $request) {
    //     $lrn = $request->input('lrn'); // Expecting the LRN of the student to be removed
    
    //     // Log the received data
    //     Log::info('Removing student with LRN: ' . $lrn);
    
    //     // Remove the student from the roster based on LRN
    //     $deletedRows = DB::table('rosters')->where('LRN', $lrn)->delete();
        
    //     $deletedRows = DB::table('grades')->where('LRN', $lrn)->delete();
    
    //     // Log the number of deleted rows
    //     Log::info('Number of deleted rows: ' . $deletedRows);
    
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Student removed from all classes successfully',
    //     ]);
    // }

    public function getClass(){
        $data = DB::table('classes')
            ->leftJoin('sections', 'classes.section_id', '=', 'sections.section_id')
            ->leftJoin('admins', 'classes.admin_id', '=', 'admins.admin_id')
            ->leftJoin('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
            ->select('classes.*', 'sections.*', 'admins.*', 'subjects.*')
            ->get();

        return $data;
    }


    //Grade Functions

    public function allenrollments(){
        // $enrollments  = Enrollment::with('student')->get();
        $enrollments = DB::table('enrollments')
                        ->leftJoin('students', 'enrollments.LRN', '=', 'students.LRN')
                        ->select('enrollments.*', 'students.*', DB::raw('CONCAT(students.fname, " ",LEFT(students.mname, 1), ". ", students.lname)as full_name'))
                        ->get();
                        
        return $enrollments;
    }

    public function getClassGrades(Request $request) {
        $gradeLevel = $request->input('gradelevel');
        $section = $request->input('section');
        $strand = $request->input('strand');
        $subject = $request->input('subject');
        $gender = $request->input('gender');
    
        // Log the parameters for debugging
        Log::info('Parameters received:', [
            'gradeLevel' => $gradeLevel,
            'section' => $section,
            'strand' => $strand,
            'subject' => $subject,
            'gender' => $gender,
        ]);
    
        $grades = DB::table('rosters')
            ->join('students', 'rosters.LRN', '=', 'students.LRN')
            ->join('enrollments', 'students.LRN', '=', 'enrollments.LRN')
            ->join('classes', 'rosters.class_id', '=', 'classes.class_id')
            ->join('admins', 'classes.admin_id', '=', 'admins.admin_id')
            ->join('sections', 'classes.section_id', '=', 'sections.section_id')
            ->leftJoin('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
            ->leftJoin('grades', function($join) {
                $join->on('rosters.LRN', '=', 'grades.LRN')
                     ->on('rosters.class_id', '=', 'grades.class_id'); // Ensure correct join on class_id
            })
            ->where('sections.grade_level', '=', $gradeLevel)
            ->where('sections.section_name', '=', $section)
            ->where('subjects.subject_name', '=', $subject) // Filter by subject name
            ->when($strand, function ($query, $strand) {
                return $query->where('subjects.strand', '=', $strand);
            })
            ->when($gender, function ($query, $gender) {
                return $query->where('students.gender', '=', $gender);
            })
            ->select(
                'students.LRN',
                'students.gender',
                DB::raw('CONCAT(students.fname, " ", LEFT(students.mname, 1), ". ", students.lname) AS student_name'),
                'students.contact_no',
                'subjects.subject_id',
                'subjects.subject_name',
                'subjects.strand',
                'subjects.grade_level',
                'enrollments.school_year',
                DB::raw(
                    "MAX(CASE WHEN grades.term = 'First Quarter' THEN grades.grade ELSE NULL END) AS grade_Q1,
                     MAX(CASE WHEN grades.term = 'First Quarter' THEN grades.permission ELSE NULL END) AS permission_Q1,
                     MAX(CASE WHEN grades.term = 'First Quarter' THEN grades.grade_id ELSE NULL END) AS grade_id_Q1,
                     MAX(CASE WHEN grades.term = 'Second Quarter' THEN grades.grade ELSE NULL END) AS grade_Q2,
                     MAX(CASE WHEN grades.term = 'Second Quarter' THEN grades.permission ELSE NULL END) AS permission_Q2,
                     MAX(CASE WHEN grades.term = 'Second Quarter' THEN grades.grade_id ELSE NULL END) AS grade_id_Q2,
                     MAX(CASE WHEN grades.term = 'Third Quarter' THEN grades.grade ELSE NULL END) AS grade_Q3,
                     MAX(CASE WHEN grades.term = 'Third Quarter' THEN grades.permission ELSE NULL END) AS permission_Q3,
                     MAX(CASE WHEN grades.term = 'Third Quarter' THEN grades.grade_id ELSE NULL END) AS grade_id_Q3,
                     MAX(CASE WHEN grades.term = 'Fourth Quarter' THEN grades.grade ELSE NULL END) AS grade_Q4,
                     MAX(CASE WHEN grades.term = 'Fourth Quarter' THEN grades.permission ELSE NULL END) AS permission_Q4,
                     MAX(CASE WHEN grades.term = 'Fourth Quarter' THEN grades.grade_id ELSE NULL END) AS grade_id_Q4,
                     MAX(CASE WHEN grades.term = 'Midterm' THEN grades.grade ELSE NULL END) AS midterm,
                     MAX(CASE WHEN grades.term = 'Midterm' THEN grades.permission ELSE NULL END) AS permission_midterm,
                     MAX(CASE WHEN grades.term = 'Midterm' THEN grades.grade_id ELSE NULL END) AS grade_id_midterm,
                     MAX(CASE WHEN grades.term = 'Final' THEN grades.grade ELSE NULL END) AS final,
                     MAX(CASE WHEN grades.term = 'Final' THEN grades.permission ELSE NULL END) AS permission_final,
                     MAX(CASE WHEN grades.term = 'Final' THEN grades.grade_id ELSE NULL END) AS grade_id_final"
                )
            )
            ->groupBy('students.LRN', 'student_name', 'students.gender', 'students.contact_no', 'subjects.subject_id', 'subjects.subject_name', 'subjects.strand', 'enrollments.school_year', 'subjects.grade_level',)
            ->orderByRaw("CASE WHEN students.gender = 'MALE' THEN 0 ELSE 1 END")
            ->orderBy('students.lname')
            ->get();
    
        return response()->json($grades);
    }

    public function getSubjectRosters(Request $request){
        $gradeLevel = $request->input('gradelevel');
        $section = $request->input('section');
        $strand = $request->input('strand');
        $subject = $request->input('subject');
        $gender = $request->input('gender');
        
        $rosters = DB::table('rosters')
            ->join('students', 'rosters.LRN', '=', 'students.LRN')
            ->join('classes', 'rosters.class_id', '=', 'classes.class_id')
            ->join('admins', 'classes.admin_id', '=', 'admins.admin_id')
            ->join('sections', 'classes.section_id', '=', 'sections.section_id')
            ->leftJoin('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
            ->where('sections.grade_level', '=', $gradeLevel)
            ->where('sections.section_name', '=', $section)
            ->where('subjects.subject_name', '=', $subject)
            ->when($strand, function ($query, $strand) {
                if ($strand !== '') {
                    return $query->where('subjects.strand', '=', $strand);
                }
            })
            ->when($gender, function ($query, $gender) {
                return $query->where('students.gender', '=', $gender);
            })
            ->select('rosters.*', 'classes.*', 'admins.*', 'subjects.*', 'students.contact_no', 'students.gender',DB::raw('CONCAT(students.fname, " ",LEFT(students.mname, 1), ". ", students.lname)as student_name'))
            ->get();
        
        return $rosters;
    }

    public function permit(Request $request) {
        $gid = $request->input('gid'); // Get gid from request
        if (!$gid) {
            return response()->json(['error' => 'Grade ID is required'], 400);
        }
    
      
            DB::table('grades')
                ->where('grade_id', $gid)
                ->update(['permission' => 'on']);
    
            return response()->json(['success' => 'Grade permission updated successfully']);
    } 

    public function decline(Request $request){
        $gid = $request->input('gid'); // Get gid from request
        if (!$gid) {
            return response()->json(['error' => 'Grade ID is required'], 400);
        }
    
      
            DB::table('grades')
                ->where('grade_id', $gid)
                ->update(['permission' => 'none']);
    
            return response()->json(['success' => 'Grade permission updated successfully']);
    }
    
    public function getGrades($lrn, $syr) {
        // Fetch student information
        $student = DB::table('students')
            ->where('LRN', '=', $lrn)
            ->first();
    
        $enrollments = DB::table('enrollments')
            ->where('LRN', '=', $lrn)
            ->first();
        
        // Fetch subjects with grades (if available) and join with necessary tables
        $grades = DB::table('subjects')
            ->leftJoin('classes', 'subjects.subject_id', '=', 'classes.subject_id')
            ->leftJoin('rosters', 'classes.class_id', '=', 'rosters.roster_id')
            ->leftJoin('grades', function ($join) use ($lrn) {
                $join->on('grades.class_id', '=', 'classes.class_id')
                     ->where('grades.LRN', '=', $lrn);
            })
            ->leftJoin('sections', 'classes.section_id', '=', 'sections.section_id')
            ->where('sections.grade_level', '=', $enrollments->grade_level)
            ->select('sections.*', 'subjects.subject_name', 'grades.grade', 'grades.term', 'classes.*')
            ->get()
            ->groupBy('subject_name');
        
        $result = [];
        // Loop through each subject to organize grades
        foreach ($grades as $subject => $subjectGrades) {
            $subjectResult = [
                'First Quarter' => null,
                'Second Quarter' => null,
                'Third Quarter' => null,
                'Fourth Quarter' => null,
                'Midterm' => null,
                'Final' => null
            ];
            foreach ($subjectGrades as $grade) {
                $subjectResult[$grade->term] = $grade->grade;
            }
            $result[$subject] = $subjectResult;
        }
    
        // Construct student info
        $studentInfo = [
            'full_name' => trim($student->fname . ' ' . $student->lname), // Combine first and last name
            'strand' => $enrollments->strand,
            'grade_level' => $enrollments->grade_level,
            'LRN' => $lrn, // Include the LRN
            'school_year' => $syr // Include the school year
        ];
        
        // Return both student info and grades
        return [
            ['student' => $studentInfo],
            ['grades' => $result]
        ];
    }

    public function getGradesTP(Request $request) {
        $grades = DB::table('grades')->select('term', 'permission')->get();
        return response()->json($grades);
    }

    public function enableTerm(Request $request) {  
        Log::info('Enable Term Request:', $request->all());
        
        $term = $request->input('term');
        $affectedRows = DB::table('grades')
            ->where('term', '=', $term)
            ->update(['permission' => 'on']);
        
        if ($affectedRows > 0) {
            return response()->json(['success' => 'Term is now enabled']);
        } else {
            return response()->json(['error' => 'No records found for this term']);
        }
    }

    public function disableTerm(Request $request) {  
        Log::info('Disable Term Request:', $request->all());
        
        $term = $request->input('term');
        $affectedRows = DB::table('grades')
            ->where('term', '=', $term)
            ->update(['permission' => 'none']);
        
        if ($affectedRows > 0) {
            return response()->json(['success' => 'Term is now disabled']);
        } else {
            return response()->json(['error' => 'No records found for this term']);
        }
    }

    // public function disableTerm($term) {
    //     DB::table('grades')
    //         ->where('term', $term)
    //         ->update(['permission' => 'none']);
     
    //     return response()->json(['success' => 'Term is now disabled']);
    // }


    // Message Functions

    public function getStudentParents() {
        // Fetch students
        $students = DB::table('students')
        ->leftJoin('enrollments', 'students.LRN', '=', 'enrollments.LRN')
        ->where('enrollments.regapproval_date', '!=', null)
        ->select('students.LRN', DB::raw("
                CONCAT(
                    students.fname, 
                    ' ', 
                    CASE 
                        WHEN students.mname IS NOT NULL AND students.mname != '' THEN CONCAT(LEFT(students.mname, 1), '. ')
                        ELSE ''
                    END,
                    students.lname
                ) as account_name"))
            ->get()
            ->map(function ($student) {
                return [
                    'account_id' => $student->LRN,
                    'account_name' => $student->account_name,
                    'type' => 'student',
                ];
            });
    
        // Fetch parents
        $parents = DB::table('parent_guardians')
            ->select('parent_guardians.guardian_id', 
                DB::raw("
                    CONCAT(
                        parent_guardians.fname, 
                        ' ', 
                        CASE 
                            WHEN parent_guardians.mname IS NOT NULL AND parent_guardians.mname != '' THEN CONCAT(LEFT(parent_guardians.mname, 1), '. ')
                            ELSE ''
                        END,
                        parent_guardians.lname
                    ) as account_name"))
            ->whereIn('guardian_id', function($query) {
                $query->select(DB::raw('MIN(guardian_id)')) // Get the first guardian_id for each email
                ->from('parent_guardians')
                ->groupBy('email'); // Group by email to ensure distinct entries
                })
            ->get()
            ->map(function ($parent) {
                return [
                    'account_id' => $parent->guardian_id,
                    'account_name' => $parent->account_name,
                    'type' => 'parent',
                ];
            });
    
        // Combine both collections into one
        $accounts = $students->merge($parents);
    
        return response()->json($accounts);
    }

    public function getMessages(Request $request) {
        $uid = $request->input('uid');
    
        // Main query to get messages for the entire conversation
        $msg = DB::table('messages')
            ->leftJoin('students', function ($join) {
                $join->on('messages.message_sender', '=', 'students.LRN');
            })
            ->leftJoin('admins', function ($join) {
                $join->on('messages.message_sender', '=', 'admins.admin_id');
            })
            ->leftJoin('parent_guardians', function ($join) {
                $join->on('messages.message_sender', '=', 'parent_guardians.guardian_id');
            })
            ->leftJoin('students as receiver_students', function ($join) {
                $join->on('messages.message_reciever', '=', 'receiver_students.LRN');
            })
            ->leftJoin('admins as receiver_admins', function ($join) {
                $join->on('messages.message_reciever', '=', 'receiver_admins.admin_id');
            })
            ->leftJoin('parent_guardians as receiver_guardians', function ($join) {
                $join->on('messages.message_reciever', '=', 'receiver_guardians.guardian_id');
            })
            ->where(function($query) use ($uid) {
                $query->where('messages.message_sender', '=', $uid) // Messages sent by the user
                      ->orWhere('messages.message_reciever', '=', $uid); // Messages received by the user
            })
            ->select('messages.*', 
                DB::raw('CASE 
                        WHEN messages.message_sender IN (SELECT LRN FROM students) THEN 
                            CONCAT(students.fname, 
                                IFNULL(CONCAT(" ", LEFT(students.mname, 1), "."), ""), 
                                " ", 
                                students.lname)
                        WHEN messages.message_sender IN (SELECT admin_id FROM admins) THEN 
                            CONCAT(receiver_students.fname, 
                                IFNULL(CONCAT(" ", LEFT(receiver_students.mname, 1), "."), ""), 
                                " ", 
                                receiver_students.lname)
                        WHEN messages.message_sender IN (SELECT guardian_id FROM parent_guardians) THEN 
                            CONCAT(parent_guardians.fname, 
                                IFNULL(CONCAT(" ", LEFT(parent_guardians.mname, 1), "."), ""), 
                                " ", 
                                parent_guardians.lname)
                    END as sender_name'))
            ->havingRaw('sender_name IS NOT NULL')
            ->orderBy('messages.created_at', 'desc')
            ->get();
        
        return $msg;
    }

    public function getConvo(Request $request, $sid) {
        // Initialize the response variable
        $user = null;
    
        // Check if the $sid corresponds to a student
        $student = DB::table('students')
            ->where('students.LRN', $sid)
            ->select('students.LRN', DB::raw("
            CONCAT(
                students.fname, 
                ' ', 
                CASE 
                    WHEN students.mname IS NOT NULL AND students.mname != '' THEN CONCAT(LEFT(students.mname, 1), '. ')
                    ELSE ''
                END,
                students.lname
            ) as account_name"))
            ->first(); // Use first() to get a single record
    
        if ($student) {
            // If a student is found, format the response
            $user = [
                'account_id' => $student->LRN,
                'account_name' => $student->account_name,
                'type' => 'student',
            ];
        } else {
            // If no student found, check for a parent
            $parent = DB::table('parent_guardians')
                ->where('parent_guardians.guardian_id', $sid)
                ->select('parent_guardians.guardian_id', 
                DB::raw("
                    CONCAT(
                        parent_guardians.fname, 
                        ' ', 
                        CASE 
                            WHEN parent_guardians.mname IS NOT NULL AND parent_guardians.mname != '' THEN CONCAT(LEFT(parent_guardians.mname, 1), '. ')
                            ELSE ''
                        END,
                        parent_guardians.lname
                    ) as account_name"))
                ->first(); // Use first() to get a single record
    
            if ($parent) {
                // If a parent is found, format the response
                $user = [
                    'account_id' => $parent->guardian_id,
                    'account_name' => $parent->account_name,
                    'type' => 'parent',
                ];
            }
        }
    
        // Initialize the conversation variable
        $convo = [];
    
        // If user is found, fetch the conversation
        if ($user) {
            $uid = $request->input('uid');
    
        //     $convo = DB::table('messages')
        //         ->leftJoin('students', function ($join) {
        //             $join->on('messages.message_sender', '=', 'students.LRN');
        //         })
        //         ->leftJoin('admins', function ($join) {
        //             $join->on('messages.message_sender', '=', 'admins.admin_id');
        //         })
        //         ->leftJoin('parent_guardians', function ($join) {
        //             $join->on('messages.message_sender', '=', 'parent_guardians.guardian_id');
        //         })
        //         ->where(function ($query) use ($uid) {
        //             $query->where('messages.message_sender', $uid) // Sent messages
        //                   ->orWhere('messages.message_reciever', $uid); // Received replies
        //         })     
        //         ->where(function ($query) use ($sid) {
        //             $query->where('messages.message_sender', $sid) // Sent messages
        //                   ->orWhere('messages.message_reciever', $sid); // Received replies
        //         })        
        //         ->select('messages.*', 
        //             DB::raw('CASE 
        //                 WHEN messages.message_sender IN (SELECT LRN FROM students) THEN CONCAT(students.fname, " ", LEFT(students.mname, 1), ". ", students.lname)
        //                 WHEN messages.message_sender IN (SELECT guardian_id FROM parent_guardians) THEN CONCAT(parent_guardians.fname, " ", LEFT(parent_guardians.mname, 1), ". ", parent_guardians.lname)
        //             END as sender_name'),
        //             DB::raw('CASE 
        //                 WHEN messages.message_sender IN (SELECT guardian_id FROM parent_guardians) THEN CONCAT(parent_guardians.fname, " ", LEFT(parent_guardians.mname, 1), ". ", parent_guardians.lname)
        //                 END as admin_name'),
        //             DB::raw('CASE 
        //                 WHEN messages.message_sender IN (SELECT admin_id FROM admins) THEN CONCAT(admins.fname, " ", LEFT(admins.mname, 1), ". ", admins.lname)
        //                 END as admin_name'))
        //         ->get();
        // }

        $convo = DB::table('messages')
        ->leftJoin('students', 'messages.message_sender', '=', 'students.LRN')
        ->leftJoin('admins', 'messages.message_sender', '=', 'admins.admin_id')
        ->leftJoin('parent_guardians', 'messages.message_sender', '=', 'parent_guardians.guardian_id')
        ->where(function ($query) use ($uid) {
            $query->where('messages.message_sender', $uid)
                ->orWhere('messages.message_reciever', $uid);
        })
        ->where(function ($query) use ($sid) {
            $query->where('messages.message_sender', $sid)
                ->orWhere('messages.message_reciever', $sid);
        })
        ->selectRaw("
            messages.*,
            CASE 
                WHEN messages.message_sender = ? THEN 'me' 
                ELSE NULL 
            END as me,
            CASE 
                WHEN messages.message_sender IN (SELECT LRN FROM students) THEN CONCAT(students.fname, ' ', LEFT(students.mname, 1), '. ', students.lname)
                WHEN messages.message_sender IN (SELECT guardian_id FROM parent_guardians) THEN CONCAT(parent_guardians.fname, ' ', LEFT(parent_guardians.mname, 1), '. ', parent_guardians.lname)
                ELSE NULL 
            END as sender_name
        ", [$uid])
        ->get();
     }
    
        // Return the user information and conversation or a not found message
        return response()->json([
            'user' => $user ?: ['message' => 'User  not found'],
            'conversation' => $convo,
        ]);
    }

    public function sendMessage(Request $request){
        $validator = Validator::make($request->all(), [
            'message_sender' => 'required',
            'message_reciever' => 'required',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $message = Message::create([
            'message_sender' => $request->input('message_sender'), // Ensure the key matches your database column
            'message_reciever' => $request->input('message_reciever'), // Ensure the key matches your database column
            'message' => $request->input('message'), // Ensure the key matches your database column
            'message_date' => now(),
        ]);

        return response()->json($message, 201);
    }

    public function getrecepeints(Request $request){
        // Query for students
        $students = DB::table('students')
            ->select(DB::raw('LRN AS receiver_id, CONCAT(fname, " ", lname) AS receiver_name'));

        // Query for guardians, using MAX() for non-grouped columns
        $guardians = DB::table('parent_guardians')
            ->select(DB::raw('
                MAX(guardian_id) AS receiver_id, 
                CONCAT(MAX(fname), " ", MAX(lname)) AS receiver_name
            '))
            ->groupBy('email'); // Group by email to ensure distinct records

        // Combine both queries and ensure distinct records for receiver_id
        $recipients = $students->unionAll($guardians)->distinct()->get();

        // Return the combined list of recipients as JSON
        return response()->json($recipients);
    } 

    public function composenewmessage(Request $request){
        // Validate the incoming request data
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'message_date' => 'required|date',
            'message_sender' => [
                'required',
                function ($attribute, $value, $fail) {
                    $existsInStudents = DB::table('students')->where('LRN', $value)->exists();
                    $existsInGuardians = DB::table('parent_guardians')->where('guardian_id', $value)->exists();
                    $existsInAdmins = DB::table('admins')->where('admin_id', $value)->exists();
    
                    if (!$existsInStudents && !$existsInGuardians && !$existsInAdmins) {
                        $fail("The selected $attribute is invalid.");
                    }
                },
            ],
            'message_reciever' => [
                'required',
                function ($attribute, $value, $fail) {
                    $existsInStudents = DB::table('students')->where('LRN', $value)->exists();
                    $existsInGuardians = DB::table('parent_guardians')->where('guardian_id', $value)->exists();
                    $existsInAdmins = DB::table('admins')->where('admin_id', $value)->exists();
    
                    if (!$existsInStudents && !$existsInGuardians && !$existsInAdmins) {
                        $fail("The selected $attribute is invalid.");
                    }
                },
            ],
        ]);
    
        try {
            // Create a new message
            $message = new Message();
            $message->message_sender = $validated['message_sender'];
            $message->message_reciever = $validated['message_reciever'];
            $message->message = $validated['message'];
            $message->message_date = $validated['message_date'];
            $message->save();
    
            // Log a success message
            Log::info('Message successfully composed', [
                'message_id' => $message->message_id,
                'sender' => $validated['message_sender'],
                'receiver' => $validated['message_reciever'],
                'message_content' => $validated['message'],
                'message_date' => $validated['message_date'],
            ]);
    
            // Return the updated list of messages
            return $this->getMessages($request);  // Call getMessages method to return updated conversation
        } catch (\Exception $e) {
            // Log any error that occurs
            Log::error('Error sending message: ' . $e->getMessage());
    
            // Return an error response
            return response()->json(['error' => 'Failed to send message'], 500);
        }
    }




    // Account

    public function updatePass(Request $request) {
        // Validate incoming request
        $request->validate([
            'admin_id' => 'required|integer|exists:admins,admin_id',
            'oldPassword' => 'nullable|string', // Make oldPassword optional
            'newPassword' => 'nullable|string|min:8|confirmed', // Allow newPassword to be optional
            'fname' => 'required|string|max:255',
            'mname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:admins,email,' . $request->admin_id . ',admin_id', // Check uniqueness for email
            'address' => 'required|string|max:255',
        ]);
    
        // Retrieve user
        $user = Admin::find($request->admin_id);
        
        // Log the attempt to update user details
        Log::info('Attempting to update user details for admin_id: ' . $request->admin_id);
    
        // If old password is provided, check it
        if ($request->oldPassword && !Hash::check($request->oldPassword, $user->password)) {
            Log::warning('Wrong password attempt for admin_id: ' . $request->admin_id);
            return response()->json(['message' => 'Wrong password'], 401);
        }
    
        // Update user details
        if ($request->newPassword) {
            $user->password = Hash::make($request->newPassword); // Update password if provided
            Log::info('Password updated for admin_id: ' . $request->admin_id);
        }
    
        // Update other user details
        $user->fname = $request->fname;
        $user->mname = $request->mname;
        $user->lname = $request->lname;
        $user->email = $request->email;
        $user->address = $request->address;
    
        $user->save(); // Save all changes
    
        // Log successful update
        Log::info('User  details updated successfully for admin_id: ' . $request->admin_id);
    
        return response()->json(['message' => 'User  details updated successfully']);
    }


    public function uploadImage(Request $request) {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'admin_id' => 'required|exists:admins,admin_id'
        ]);
    
        try {
            $admin = Admin::findOrFail($request->input('admin_id'));
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('assets/adminPic');
    
            // Ensure the directory exists
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
    
            // Delete the old image if exists
            if ($admin->admin_pic && file_exists($path = $destinationPath . '/' . $admin->admin_pic)) {
                unlink($path);
            }
    
            // Move the new image and update the admin profile
            $image->move($destinationPath, $imageName);
            $admin->update(['admin_pic' => $imageName]);
    
            return response()->json([
                'message' => 'Image uploaded successfully.',
                'image_url' => url('assets/adminPic/' . $imageName)
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Image upload failed.'], 500);
        }
    }

    // Registration Student

    
    public function signup(Request $request){
        $formField = $request->validate([
            'LRN' => 'required|integer|unique:students,LRN', 
            'fname' => 'required|string|max:255', 
            'mname' => 'nullable|string|max:255', 
            'lname' => 'required|string|max:255', 
            'bdate' => 'required|date|max:255', 
            'email' => 'required|email|max:255|unique:students,email',
            'password' => 'required|string'
        ]);
        Student::create($formField);
        return $request;
    } 

    public function personalDetails(Request $request){
        $formField = $request->validate([
            'LRN' => 'required|integer', 
            'fname' => 'required|string|max:255', 
            'mname' => 'nullable|string|max:255', 
            'lname' => 'required|string|max:255', 
            'suffix' => 'nullable|string|max:255', 
            'bdate' => 'required|date',
            'bplace' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'gender' => 'required|string|max:255',
            'contact_no' => 'required|string|max:255',
            'religion' => 'required|string|max:255',
        ]);

        $pdata = DB::table('students')
            ->where('LRN', '=', $formField['LRN'])
            ->update([
                'LRN' => $formField['LRN'],
                'fname' => $formField['fname'],
                'mname' => $formField['mname'],
                'lname' => $formField['lname'],
                'bdate' => $formField['bdate'],
                'suffix' => $formField['suffix'],
                'bplace' => $formField['bplace'],
                'address' => $formField['address'],
                'gender' => $formField['gender'],
                'contact_no' => $formField['contact_no'],
                'religion' => $formField['religion']
            ]);
       
        return $pdata;
    }

    public function enrollmentDetails(Request $request) {
        $formField = $request->validate([
            'LRN' => 'required|integer', 
            'grade_level' => 'required|string|max:255',
            'strand' => 'nullable|string|max:255', 
            'school_year' => 'required|string|max:255', 
            'last_attended' => 'required|string|max:255',
            'public_private' => 'required|string|max:255',
            'guardian_name' => 'required|string|max:255',    
        ]);
    
        // Check if the enrollment already exists
        $enrollment = Enrollment::where('LRN', $formField['LRN'])->first();
    
        // Set the date_register to now
        $formField['date_register'] = now();
    
        if ($enrollment) {
            // Update the existing enrollment record
            $enrollment->update($formField);
            return response()->json(['message' => 'Enrollment updated successfully.', 'data' => $enrollment], 200);
        } else {
            // Create a new enrollment record
            $edata = Enrollment::create($formField);
            return response()->json(['message' => 'Enrollment created successfully.', 'data' => $edata], 201);
        }
    }

    public function enrollmentLogin(Request $request) {
        $request->validate([
            'email' => 'required|email|exists:students',
            'password' => 'required'
        ]);

        $student = Student::where('email', $request->email)->first();
        if(!$student || !Hash::check($request->password,$student->password)){
            return [
                'message' => 'The provided credentials are incorrect'
            ];
        }

        $token = $student->createToken($student->fname);

        return [
            'student' => $student,
            'token' => $token->plainTextToken
        ];
    }

    public function getStudentEnrollment(Request $request){
        $sid = $request->input('sid');

        $enrollment = DB::table('enrollments')
            ->where('enrollments.LRN', '=', $sid)
            ->select('enrollments.*')
            ->first();

        $payment = DB::table('payments')
            ->where('payments.LRN', '=', $sid)
            ->select('payments.*')
            ->first();

        return[
            'eData' => $enrollment,
            'pData' => $payment
        ];
    }

    public function getStudentPayment(Request $request){
        $sid = $request->input('sid');

        $student = DB::table('students')
            ->leftJoin('payments', 'students.LRN', '=', 'payments.LRN')
            ->where('students.LRN', '=', $sid)
            ->select('payments.*')
            ->first();

        return($student);
    }

    public function uploadPayment(Request $request) {
        $formField = $request->validate([
            'LRN' => 'required|exists:students,LRN',
            'OR_number' => 'nullable|string|max:255',
            'amount_paid' => 'required|string|max:255',
            'proof_payment' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'required|string|max:255',
        ]);
    
        $payment = Payment::where('LRN', $formField['LRN'])->first();
    
        try {
            $image = $request->file('proof_payment');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('uploads/payments');
    
            // Ensure the directory exists
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
    
            // Move the new image to the destination path
            $image->move($destinationPath, $imageName);
    
            // Save only the filename in the database
            $formField['proof_payment'] = $imageName; // Store only the filename
    
            $formField['date_of_payment'] = now();
    
            if ($payment) {
                $payment->update($formField);
                return response()->json(['message' => 'Payment updated successfully.', 'data' => $payment], 200);
            } else {
                $pdata = Payment::create($formField);
                return response()->json(['message' => 'Payment created successfully.', 'data' => $pdata], 201);
            }
        } catch (\Exception $e) {
            Log::error('Payment upload failed: ' . $e->getMessage()); // Log the error message
            return response()->json(['error' => 'Payment upload failed.'], 500);
        }
    }

    public function uploadPop(Request $request) {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'lrn' => 'required|exists:students,LRN' // Assume you're associating the image with a student
        ]);
    
        try {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('uploads'); // Change to your desired directory
    
            // Ensure the directory exists
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
    
            // Move the new image to the destination path
            $image->move($destinationPath, $imageName);
    
            return response()->json([
                'message' => 'Image uploaded successfully.',
                'image_url' => url('uploads/' . $imageName) // Return the URL of the uploaded image
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Image upload failed.'], 500);
        }
    }
   
}
