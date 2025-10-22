<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\SubmissionController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/admin/login', [AuthController::class, 'adminLogin']);
Route::post('/users', [UserController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('users', UserController::class)->except(['store']);

    Route::apiResource('courses', CourseController::class);
    Route::apiResource('lessons', LessonController::class);
    Route::apiResource('enrollments', EnrollmentController::class);
    Route::apiResource('assignments', AssignmentController::class);
    Route::apiResource('submissions', SubmissionController::class);

    Route::get('/student/courses', [CourseController::class, 'studentCourses']);
    Route::get('/student/courses/{id}', [CourseController::class, 'showStudentCourse']);

    Route::post('/logout', [AuthController::class, 'logout']);
});

