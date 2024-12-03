<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;
use Illuminate\Container\Attributes\Auth;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route::middleware(['auth'])->group(function(){
//     Route::view('/dashboard');

//     Route::middleware(['admin'])->group(function(){

//     }); ([])
// });



Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


// Route::apiResource('admins', AdminController::class);
// Route::apiResource('students', StudentController::class);


Route::middleware(['auth:sanctum'])->group(function () {
    Route::middleware(['Registrar'])->group(function () {

        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

        //Home Routes
        Route::get('/getInquiries', [AuthController::class, 'getInquiries']);

        // Enrollment Routes

        Route::get('/enrollments', [AuthController::class, 'enrollments']);
        Route::get('/enrollmentinfo/{eid}', [AuthController::class, 'enrollmentinfo']);
        Route::post('/approval/{eid}', [AuthController::class, 'approval']);
        Route::delete('/deleteEnrollment/{eid}', [AuthController::class, 'deleteEnrollment']);

        //Classes Routes

        Route::get('/getClasses', [AuthController::class, 'getClasses']);

        //Classes Routes

        Route::get('/getSections', [AuthController::class, 'getSections']);
        Route::get('/getSubjects', [AuthController::class, 'getSubjects']);

        //Roster Routes

        Route::post('/createRoster', [AuthController::class, 'createRoster']);
        Route::get('/getRosters', [AuthController::class, 'getRosters']);
        Route::get('/getFilteredRosters', [AuthController::class, 'getFilteredRosters']);

        //Rostering Routes

        // Route::get('/getClassInfo/{cid}', [AuthController::class, 'getClassInfo']);
        Route::get('/getClassInfo', [AuthController::class, 'getClassInfo']);
        // Route::get('/getRosterInfo/{cid}', [AuthController::class, 'getRosterInfo']);
        Route::get('/getRosterInfo', [AuthController::class, 'getRosterInfo']);
        Route::post('/addStudent', [AuthController::class, 'addStudent']);
        // Route::delete('/removeStudent/{rid}', [AuthController::class, 'removeStudent']);
        Route::delete('/removeStudent', [AuthController::class, 'removeStudent']);
        Route::get('/getEnrolees/{lvl}', [AuthController::class, 'getEnrolees']);

        Route::get('/getClass', [AuthController::class, 'getClass']);

        //Grades Routes

        Route::get('/getClassGrades', [AuthController::class, 'getClassGrades']);
        Route::get('/allenrollments', [AuthController::class, 'allenrollments']);
        Route::get('/getSubjectRosters', [AuthController::class, 'getSubjectRosters']);
        Route::get('/getGrades/{lrn}/{syr}', [AuthController::class, 'getGrades']);
        Route::post('/permit', [AuthController::class, 'permit']);
        Route::post('/decline', [AuthController::class, 'decline']);

        //Message Routes

        Route::get('/getMessages', [AuthController::class, 'getMessages']);
        Route::get('/message', [AuthController::class, 'message']);
        Route::get('/getConvo/{sid}', [AuthController::class, 'getConvo']);
        Route::get('/displaymsg', [AuthController::class, 'displaymsg']);
        Route::post('/sendMessage', [AuthController::class, 'sendMessage']);
        Route::get('/getStudentParents', [AuthController::class, 'getStudentParents']);
        Route::get('/getrecepeints', [AuthController::class, 'getrecepeints']);
        Route::post('/composemessage', [AuthController::class, 'composenewmessage']);

        // Account 
        Route::put('/update-password', [AuthController::class, 'updatePass']);
        Route::post('/upload-image', [AuthController::class, 'uploadImage']);
        Route::get('assets/adminPic/{filename}', function ($filename) {
            $path = public_path('assets/adminPic/' . $filename);
            
            if (file_exists($path)) {
                return response()->file($path);
            }
        
            abort(404);
        });
    });
});





// Enrollment/Students Routes

Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/enrollmentLogin', [AuthController::class, 'enrollmentLogin']);
Route::get('/getStudentEnrollment', [AuthController::class, 'getStudentEnrollment']);
Route::get('/getStudentPayment', [AuthController::class, 'getStudentPayment']);

Route::post('/personalDetails', [AuthController::class, 'personalDetails']);
Route::post('/enrollmentDetails', [AuthController::class, 'enrollmentDetails']);
Route::post('/upload-payment', [AuthController::class, 'uploadPayment']);