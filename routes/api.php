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
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::apiResource('admins', AdminController::class);
Route::apiResource('students', StudentController::class);

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

//Message Routes

Route::get('/getMessages', [AuthController::class, 'getMessages']);
Route::get('/message', [AuthController::class, 'message']);
Route::get('/getConvo/{sid}', [AuthController::class, 'getConvo']);
Route::get('/displaymsg', [AuthController::class, 'displaymsg']);
Route::post('/sendMessage', [AuthController::class, 'sendMessage']);

// Enrollment/Students Routes

Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/enrollmentLogin', [AuthController::class, 'enrollmentLogin']);
Route::post('/enrollmentDetails', [AuthController::class, 'enrollmentDetails']);