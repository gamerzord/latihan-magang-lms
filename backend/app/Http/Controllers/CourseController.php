<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;

class CourseController extends Controller
{
    public function index() {
        $courses = Course::with('teacher')
            ->withCount('students')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(
            [
                'courses' => $courses,
            ],
            200,
        );
    }

    public function store(Request $request) {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'course_code' => 'required|string|max:50|unique:courses,course_code',
            'description' => 'nullable|string',
            'teacher_id' => 'required|exists:users,id',
        ]);

        $course = Course::create($data);

        return response()->json(
            [
                'message' => 'Course Created Successfully',
                'course' => $course,
            ],
            201,
        );
    }

    public function show($id) {
        $course = Course::with('teacher')
            ->withCount('students')
            ->find($id);
        
        if(!$course) {
            return response()->json(
                [
                    'message' => 'Course Not Found',
                ],
                404,
            );
        }

        return response()->json(
            [
                'course' => $course,
            ],
            200,
        );
    }

    public function update(Request $request, $id) {
        $course = Course::find($id);

        if(!$course) {
            return response()->json(
                [
                    'message' => 'Course Not Found',
                ],
                404,
            );
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'course_code' => 'required|string|max:50|unique:courses,course_code' . $id,
            'description' => 'nullable|string',
            'teacher_id' => 'required|exists:users,id',
        ]);

        $course->update($data);

        return response()->json(
            [
                'message' => 'Course Updated Successfully',
                'course' => $course,
            ],
            200,
        );
    }

    public function destroy ($id) {
        $course = Course::find($id);

        if(!$course) {
            return response()->json(
                [
                    'message' => 'Course Not Found',
                ],
                404,
            );
        }

        if($course->students()->count() > 0) {
            return response()->json(
                [
                    'message' => 'Cannot delete Course with existing enrolled students and a teacher',
                ],
                422,
            );
        }

        $course->delete();

        return response()->json(
            [
                'message' => 'Course Deleted Succesfully',
            ],
            200,
        );
    }
}
