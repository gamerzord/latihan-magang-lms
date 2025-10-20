<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Enrollment;

class EnrollmentController extends Controller
{
    public function index() {
        $enrollments = Enrollment::with(['course', 'student'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(
            [
                'enrollments' => $enrollments,
            ],
            200,
        );
    }

    public function store(Request $request) {
        $data = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'student_id' => 'required|exists:users,id',
        ]);

        $enrollment = Enrollment::create($data);

        return response()->json(
            [
                'message' => 'Student Enrolled Succesfully',
                'enrollment' => $enrollment,
            ],
            201,
        );
    }

    public function show($id) {
        $enrollment = Enrollment::with(['course', 'student'])
            ->find($id);

        if(!$enrollment) {
            return response()->json(
                [
                    'message' => 'Enrollment Not Found',
                ],
                404,
            );
        }

        return response()->json(
            [
                'enrollment' => $enrollment,
            ],
            200,
        );
    }

    public function update(Request $request, $id) {
        $enrollment = Enrollment::with(['course', 'student'])
            ->find($id);
        
        if(!$enrollment) {
            return response()->json(
                [
                    'message' => 'Enrollment Not Found',
                ],
                404,
            );
        }

        $data = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'student_id' => 'required|exists:users,id',
        ]);

        $enrollment->update($data);

        return response()->json(
            [
                'message' => 'Enrollment Data Updated Successfully',
                'enrollment' => $enrollment,
            ],
            200,
        );
    }

    public function destroy($id) {
        $enrollment = Enrollment::with(['course', 'student'])
            ->find($id);
        
        if(!$enrollment) {
            return response()->json(
                [
                    'message' => 'Enrollment Not Found',
                ],
                404,
            );
        }

        if($enrollment->course()->exists() && $enrollment->student()->exists()) {
            return response()->json(
                [
                    'message' => 'Cannot Delete Enrollment if both Students and the Course Still Exist',
                ].
                422,
            );
        }

        $enrollment->delete();

        return response()->json(
            [
                'message' => 'Enrollment Deleted Succesfully',
            ],
            200,
        );
    }
}
