<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Message;
use App\Models\Enrollment;
use App\Models\Student;
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
            'mname' => 'required|max: 255',
            'role' => 'required|max: 255',
            'address' => 'required|max: 255',
            'email' => 'required|email|unique:admins',
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

        $admin = Admin::where('email', $request->email)->first();
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

  
    // public function studentLogin(Request $request){
    //     $request->validate([
    //         'email' => 'required|email|exists:students',
    //         'password' => 'required'
    //     ]);

    //     $student = DB::table('enrollments')
    //         ->leftJoin('students', 'enrollments.LRN', '=', 'students.LRN')
    //         ->where('enrollments.regapproval_date', '=', '0000-00-00')
    //         ->select('students.*', 'enrollments.*')
    //         ->first();
    //         if(!$student || !Hash::check($request->password,$student->password)){
    //             return [
    //                 'message' => 'The provided credentials are incorrect'
    //             ];
    //         }
        
    //     $token = $student->createToken($student->fname);

    //     return [
    //         'student' => $student,
    //         'token' => $token->plainTextToken
    //     ];
    // }

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

    public function getInquiries(){
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
            ->joinSub($latestMessages, 'latest_messages', function ($join) {
                $join->on('messages.message_sender', '=', 'latest_messages.message_sender')
                     ->on('messages.created_at', '=', 'latest_messages.max_created_at');
            })
            ->whereNotIn('messages.message_sender', function ($query) {
                $query->select('admin_id')->from('admins');
            })
            // ->join('admins as sender_admin', 'messages.message_sender', '=', 'sender_admin.admin_id')
            // ->join('students as reciever', 'messages.message_reciever', '=', 'reciever.LRN')
            ->select('messages.*', 
                    DB::raw('CASE 
                    WHEN messages.message_sender IN (SELECT LRN FROM students) THEN CONCAT(students.fname, " ", LEFT(students.mname, 1), ". ", students.lname)
                    WHEN messages.message_sender IN (SELECT guardian_id FROM parent_guardians) THEN CONCAT(parent_guardians.fname, " ", LEFT(parent_guardians.mname, 1), ". ", parent_guardians.lname)
                    END as sender_name'),
                    DB::raw('CONCAT(admins.fname, " ",LEFT(admins.mname, 1), ". ", admins.lname)as admin_name'))
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
                        ->where('enrollments.school_year', '=', $schoolYear)
                        ->where('enrollments.date_register', '!=', null)
                        ->select('enrollments.*', 'students.*', DB::raw('CONCAT(students.fname, " ",LEFT(students.mname, 1), ". ", students.lname)as full_name'))
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

    public function getSections(){
        $klases = DB::table('sections')
            ->select('sections.*')
            ->get();

        return $klases;
    }


    //Roster Functions

    // public function createRoster($cid) {
    //     try {
    //         $data = DB::table('rosters')
    //           ->insertGetId([
    //             'class_id' => $cid,
    //             'LRN' => null,
    //           ]);
          
    //         return $data;
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }

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

    public function getRosterInfo(Request $request) {
        $classIds = explode(',', $request->input('classIds')); // Split the comma-separated string into an array
    
        $data = DB::table('rosters')
            ->join('students', 'rosters.LRN', '=', 'students.LRN')
            ->join('classes', 'rosters.class_id', '=', 'classes.class_id')
            ->join('sections', 'classes.section_id', '=', 'sections.section_id')
            ->leftJoin('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
            ->whereIn('rosters.class_id', $classIds) // Use whereIn to filter by multiple class IDs
            ->select('rosters.*', 'students.*', 'classes.*', 'subjects.*', 'sections.*', DB::raw('CONCAT(students.fname, " ", LEFT(students.mname, 1), ". ", students.lname) as student_name'))
            ->get();
    
        return response()->json($data);
    }


    // public function getRosterInfo($cid){
    //     $data = DB::table('rosters')
    //         ->join('students', 'rosters.LRN', '=', 'students.LRN')
    //         ->join('classes', 'rosters.class_id', '=', 'classes.class_id')
    //         ->join('sections', 'classes.section_id', '=', 'sections.section_id')
    //         ->leftJoin('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
    //         // ->where('students.LRN', '=' ,  $lrn)
    //         ->where('rosters.class_id', '=' ,  $cid)
    //         // ->select('students.*', 'enrollments.*')
    //         ->select('rosters.*', 'students.*', 'classes.*', 'subjects.*', 'sections.*',DB::raw('CONCAT(students.fname, " ",LEFT(students.mname, 1), ". ", students.lname)as student_name'))
    //         ->get();

    // // return response()->json($data);
    //     return $data;
    // }

    

    //Rostering Functions

    // public function getClassInfo($cid){
    //     $data = DB::table('classes')
    //         ->join('sections', 'classes.section_id', '=', 'sections.section_id')
    //         ->leftJoin('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
    //         // ->where('students.LRN', '=' ,  $lrn)
    //         ->where('classes.class_id', '=' ,  $cid)
    //         // ->select('students.*', 'enrollments.*')
    //         ->select('classes.*', 'sections.*')
    //         ->get();

    // // return response()->json($data);
    //     return $data;
    // }

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
    //     $cid = $request->input('cid');
    //     $lrn = $request->input('lrn');
    //     $data = DB::table('rosters')
    //       ->insertGetId([
    //         'class_id' => $cid,
    //         'LRN' => $lrn
    //       ]);
      
    //     return $data;
    //   }

    public function addStudent(Request $request) {
        $classIds = $request->input('cid'); // Expecting an array of class IDs
        $lrn = $request->input('lrn');
    
        // Prepare data for insertion
        $data = [];
        foreach ($classIds as $cid) {
            $data[] = [
                'class_id' => $cid,
                'LRN' => $lrn
            ];
        }
    
        // Insert multiple records into the rosters table
        DB::table('rosters')->insert($data);
    
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
        $deletedRows = DB::table('rosters')->where('LRN', $lrn)->delete();
    
        // Log the number of deleted rows
        Log::info('Number of deleted rows: ' . $deletedRows);
    
        return response()->json([
            'success' => true,
            'message' => 'Student removed from all classes successfully',
        ]);
    }

    // public function removeStudent($rid){
    //     $data = DB::table('rosters')
    //         ->where('roster_id', $rid)
    //         ->delete();

    //     return $data;
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
                    "MAX(CASE WHEN grades.term = '1st Quarter' THEN grades.grade ELSE NULL END) AS grade_Q1,
                     MAX(CASE WHEN grades.term = '1st Quarter' THEN grades.permission ELSE NULL END) AS permission_Q1,
                     MAX(CASE WHEN grades.term = '1st Quarter' THEN grades.grade_id ELSE NULL END) AS grade_id_Q1,
                     MAX(CASE WHEN grades.term = '2nd Quarter' THEN grades.grade ELSE NULL END) AS grade_Q2,
                     MAX(CASE WHEN grades.term = '2nd Quarter' THEN grades.permission ELSE NULL END) AS permission_Q2,
                     MAX(CASE WHEN grades.term = '2nd Quarter' THEN grades.grade_id ELSE NULL END) AS grade_id_Q2,
                     MAX(CASE WHEN grades.term = '3rd Quarter' THEN grades.grade ELSE NULL END) AS grade_Q3,
                     MAX(CASE WHEN grades.term = '3rd Quarter' THEN grades.permission ELSE NULL END) AS permission_Q3,
                     MAX(CASE WHEN grades.term = '3rd Quarter' THEN grades.grade_id ELSE NULL END) AS grade_id_Q3,
                     MAX(CASE WHEN grades.term = '4th Quarter' THEN grades.grade ELSE NULL END) AS grade_Q4,
                     MAX(CASE WHEN grades.term = '4th Quarter' THEN grades.permission ELSE NULL END) AS permission_Q4,
                     MAX(CASE WHEN grades.term = '4th Quarter' THEN grades.grade_id ELSE NULL END) AS grade_id_Q4,
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
                ->update(['permission' => 'Permitted']);
    
            return response()->json(['success' => 'Grade permission updated successfully']);
    }

    public function decline(Request $request){
        $gid = $request->input('gid'); // Get gid from request
        if (!$gid) {
            return response()->json(['error' => 'Grade ID is required'], 400);
        }
    
      
            DB::table('grades')
                ->where('grade_id', $gid)
                ->update(['permission' => null]);
    
            return response()->json(['success' => 'Grade permission updated successfully']);
    }

    // public function getSubjectRosters(Request $request){
    //     $gradeLevel = $request->input('gradelevel');
    //     $section = $request->input('section');
    //     $strand = $request->input('strand');
    //     $subject = $request->input('subject');
    //     $gender = $request->input('gender');
        
    //     $rosters = DB::table('rosters')
    //         ->join('students', 'grades.LRN', '=', 'students.LRN')
    //         ->join('grades', 'students.LRN', '=', 'grades.LRN')
    //         ->join('classes', 'rosters.class_id', '=', 'classes.class_id')
    //         ->join('admins', 'classes.admin_id', '=', 'admins.admin_id')
    //         ->join('sections', 'classes.section_id', '=', 'sections.section_id')
    //         ->leftJoin('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
    //         ->where('sections.grade_level', '=', $gradeLevel)
    //         ->where('sections.section_name', '=', $section)
    //         ->where('subjects.subject_name', '=', $subject)
    //         ->when($strand, function ($query, $strand) {
    //             if ($strand !== '') {
    //                 return $query->where('subjects.strand', '=', $strand);
    //             }
    //         })
    //         ->when($gender, function ($query, $gender) {
    //             return $query->where('students.gender', '=', $gender);
    //         })
    //         ->select('grades.*','rosters.*', 'classes.*', 'admins.fname as admin_name', 'subjects.*', 'students.contact_no', 'students.gender',DB::raw('CONCAT(students.fname, " ",LEFT(students.mname, 1), ". ", students.lname)as student_name'))
    //         ->get();
        
    //     return $rosters;
    // }


    // public function getGrades($lrn, $syr) {
    //     // Fetch student information
    //     $student = DB::table('students')
    //         ->where('LRN', '=', $lrn)
    //         ->first();

    //     $enrollments = DB::table('enrollments')
    //         ->where('LRN', '=', $lrn)
    //         ->first();
        
    //     // Fetch grades and join with necessary tables
    //     $grades = DB::table('grades')
    //         ->join('students', 'grades.LRN', '=', 'students.LRN')
    //         ->join('enrollments', 'students.LRN', '=', 'enrollments.LRN')
    //         ->join('classes', 'grades.class_id', '=', 'classes.class_id')
    //         ->join('sections', 'classes.section_id', '=', 'sections.section_id')
    //         ->leftJoin('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
    //         ->where('grades.LRN', '=', $lrn)
    //         ->where('enrollments.school_year', '=', $syr)
    //         ->select('sections.*', 'subjects.subject_name', 'grades.grade', 'grades.term', 'enrollments.grade_level', 'classes.*')
    //         ->get()
    //         ->groupBy('subject_name');
        
    //     $result = [];
    //     // Loop through each subject to organize grades
       
    //         foreach ($grades as $subject => $subjectGrades) {
    //             $subjectResult = [
    //                 '1st Quarter' => null,
    //                 '2nd Quarter' => null,
    //                 '3rd Quarter' => null,
    //                 '4th Quarter' => null,
    //                 'Midterm' => null,
    //                 'Final' => null
    //             ];
    //             foreach ($subjectGrades as $grade) {
    //                 $subjectResult[$grade->term] = $grade->grade;
    //             }
    //             $result[$subject] = $subjectResult;
    //         }
   
            
        
    //     // Construct student info
    //     $studentInfo = [
    //         'full_name' => trim($student->fname . ' ' . $student->lname), // Combine first and last name
    //         'grade_level' => $enrollments->grade_level,
    //         'LRN' => $lrn, // Include the LRN
    //         'school_year' => $syr // Include the school year
    //     ];
        
    //     // Return both student info and grades
    //     return [
    //         ['student' => $studentInfo],
    //         ['grades' => $result]
    //     ];
    // }
    

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
                '1st Quarter' => null,
                '2nd Quarter' => null,
                '3rd Quarter' => null,
                '4th Quarter' => null,
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

    // public function getGrades($lrn, $syr){
    //     $grades = DB::table('grades')
    //             ->join('students', 'grades.LRN', '=', 'students.LRN')
    //             ->join('enrollments', 'students.LRN', '=', 'enrollments.LRN')
    //             ->join('classes', 'grades.class_id', '=', 'classes.class_id')
    //             ->join('sections', 'classes.section_id', '=', 'sections.section_id')
    //             ->leftJoin('subjects', 'classes.subject_id', '=', 'subjects.subject_id')
    //             ->where('grades.LRN', '=', $lrn)
    //             ->where('enrollments.school_year', '=', $syr)
    //             ->select('sections.*', 'subjects.subject_name', 'grades.grade', 'grades.term')
    //             ->get();
                
                
    //         return $grades;
    // }


    // Message Functions

    public function getStudentParents() {
        // Fetch students
        $students = DB::table('students')
            ->select('students.LRN', DB::raw('CONCAT(students.fname, " ", LEFT(students.mname, 1), ". ", students.lname) as account_name'))
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
            ->select('parent_guardians.guardian_id', DB::raw('CONCAT(parent_guardians.fname, " ", LEFT(parent_guardians.mname, 1), ". ", parent_guardians.lname) as account_name'))
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
    
        // Subquery to get the latest message for each sender
        $latestMessages = DB::table('messages')
            ->select('message_sender', DB::raw('MAX(created_at) as max_created_at'))
            ->groupBy('message_sender');
    
        // Main query to get messages
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
            ->joinSub($latestMessages, 'latest_messages', function ($join) {
                $join->on('messages.message_sender', '=', 'latest_messages.message_sender')
                    ->on('messages.created_at', '=', 'latest_messages.max_created_at');
            })
            ->where('messages.message_reciever', '=', $uid) // Filter by receiver
            ->select('messages.*', 
                DB::raw('CASE 
                    WHEN messages.message_sender IN (SELECT LRN FROM students) THEN CONCAT(students.fname, " ", LEFT(students.mname, 1), ". ", students.lname)
                    WHEN messages.message_sender IN (SELECT admin_id FROM admins) THEN CONCAT(admins.fname, " ", LEFT(admins.mname, 1), ". ", admins.lname)
                    WHEN messages.message_sender IN (SELECT guardian_id FROM parent_guardians) THEN CONCAT(parent_guardians.fname, " ", LEFT(parent_guardians.mname, 1), ". ", parent_guardians.lname)
                END as sender_name'))
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
            ->select('students.LRN', DB::raw('CONCAT(students.fname, " ", LEFT(students.mname, 1), ". ", students.lname) as account_name'))
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
                ->select('parent_guardians.guardian_id', DB::raw('CONCAT(parent_guardians.fname, " ", LEFT(parent_guardians.mname, 1), ". ", parent_guardians.lname) as account_name'))
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
    
            $convo = DB::table('messages')
                ->leftJoin('students', function ($join) {
                    $join->on('messages.message_sender', '=', 'students.LRN');
                })
                ->leftJoin('admins', function ($join) {
                    $join->on('messages.message_sender', '=', 'admins.admin_id');
                })
                ->leftJoin('parent_guardians', function ($join) {
                    $join->on('messages.message_sender', '=', 'parent_guardians.guardian_id');
                })
                ->where(function ($query) use ($uid) {
                    $query->where('messages.message_sender', $uid) // Sent messages
                          ->orWhere('messages.message_reciever', $uid); // Received replies
                })     
                ->where(function ($query) use ($sid) {
                    $query->where('messages.message_sender', $sid) // Sent messages
                          ->orWhere('messages.message_reciever', $sid); // Received replies
                })        
                ->select('messages.*', 
                    DB::raw('CASE 
                        WHEN messages.message_sender IN (SELECT LRN FROM students) THEN CONCAT(students.fname, " ", LEFT(students.mname, 1), ". ", students.lname)
                        WHEN messages.message_sender IN (SELECT guardian_id FROM parent_guardians) THEN CONCAT(parent_guardians.fname, " ", LEFT(parent_guardians.mname, 1), ". ", parent_guardians.lname)
                    END as sender_name'),
                    DB::raw('CASE 
                        WHEN messages.message_sender IN (SELECT admin_id FROM admins) THEN CONCAT(admins.fname, " ", LEFT(admins.mname, 1), ". ", admins.lname)
                    END as me'))
                ->get();
        }
    
        // Return the user information and conversation or a not found message
        return response()->json([
            'user' => $user ?: ['message' => 'User  not found'],
            'conversation' => $convo,
        ]);
    }

    // public function getConvo(Request $request, $sid){
    //     $uid = $request->input('uid');

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
    //                 WHEN messages.message_sender IN (SELECT LRN FROM students) THEN CONCAT(students.fname, " ",LEFT(students.mname, 1), ". ", students.lname)
    //                 WHEN messages.message_sender IN (SELECT guardian_id FROM parent_guardians) THEN CONCAT(parent_guardians.fname, " ",LEFT(parent_guardians.mname, 1), ". ", parent_guardians.lname)
    //             END as sender_name'),
    //             DB::raw('CASE 
    //                 WHEN messages.message_sender IN (SELECT admin_id FROM admins) THEN CONCAT(admins.fname, " ",LEFT(admins.mname, 1), ". ", admins.lname)
    //             END as me'))
    //         ->get();

    //     return $convo;
    // }

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


    // Account

    public function updatePass(Request $request)
    {
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
    
        // If old password is provided, check it
        if ($request->oldPassword && !Hash::check($request->oldPassword, $user->password)) {
            return response()->json(['message' => 'Wrong password'], 401);
        }
    
        // Update user details
        if ($request->newPassword) {
            $user->password = Hash::make($request->newPassword); // Update password if provided
        }
        
        $user->fname = $request->fname;
        $user->mname = $request->mname;
        $user->lname = $request->lname;
        $user->email = $request->email;
        $user->address = $request->address;
    
        $user->save(); // Save all changes
    
        return response()->json(['message' => 'User details updated successfully']);
    }







    // Registration Student

    
    public function signup(Request $request){
        $formField = $request->validate([
            'LRN' => 'required|integer|unique:students,LRN', 
            'fname' => 'required|string|max:255', 
            'mname' => 'required|string|max:255', 
            'lname' => 'required|string|max:255', 
            'bdate' => 'required|date|max:255', 
            'email' => 'required|email|max:255|unique:students,email',
            'password' => 'required|string'
        ]);
        Student::create($formField);
        return $request;
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

    public function enrollmentDetails(Request $request){
        $formField = $request->validate([
            'LRN' => 'required|integer|unique:students,LRN', 
            'strand' => 'required|string|max:255', 
            'school_year' => 'required|string|max:255', 
            'last_attended' => 'required|string|max:255',
            'public_private' => 'required|string|max:255',  
        ]);

        Enrollment::create($formField);
        return $request;
    }


    public function EnrllmentProgress(Request $request){
        
    }

   
   
}
