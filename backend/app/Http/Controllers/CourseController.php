<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;

class CourseController extends Controller
{
    public function index() {
        $user = auth()->user();
        
        if ($user->role === 'student') {
            $courses = $user->studentCourses()
                ->with('teacher')
                ->withCount(['students', 'lessons'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($course) use ($user) {
                    $course->progress = $this->calculateCourseProgress($course, $user);
                    return $course;
                });
        } else if ($user->role === 'teacher') {
            $courses = Course::where('teacher_id', $user->id)
                ->with('teacher')
                ->withCount(['students', 'lessons'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $courses = Course::with('teacher')
                ->withCount(['students', 'lessons'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return response()->json([
            'data' => $courses,
        ], 200);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'course_code' => 'required|string|max:50|unique:courses,course_code',
            'description' => 'nullable|string',
            'teacher_id' => 'required|exists:users,id',
        ]);

        $course = Course::create($data);

        return response()->json([
            'message' => 'Course Created Successfully',
            'data' => $course->loadCount(['students', 'lessons']),
        ], 201);
    }

    public function show($id) {
        $course = Course::with(['teacher', 'lessons'])
            ->withCount(['students', 'lessons'])
            ->find($id);
        
        if(!$course) {
            return response()->json([
                'message' => 'Course Not Found',
            ], 404);
        }

        $user = auth()->user();
        if ($user->role === 'student') {
            $course->progress = $this->calculateCourseProgress($course, $user);
        }

        return response()->json([
            'data' => $course,
        ], 200);
    }

    public function update(Request $request, $id) {
        $course = Course::find($id);

        if(!$course) {
            return response()->json([
                'message' => 'Course Not Found',
            ], 404);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'course_code' => 'required|string|max:50|unique:courses,course_code,' . $id,
            'description' => 'nullable|string',
            'teacher_id' => 'required|exists:users,id',
        ]);

        $course->update($data);

        return response()->json([
            'message' => 'Course Updated Successfully',
            'data' => $course->loadCount(['students', 'lessons']),
        ], 200);
    }

    public function destroy($id) {
        $course = Course::find($id);

        if(!$course) {
            return response()->json([
                'message' => 'Course Not Found',
            ], 404);
        }

        if($course->students()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete course with enrolled students',
            ], 422);
        }

        $course->delete();

        return response()->json([
            'message' => 'Course Deleted Successfully',
        ], 200);
    }
    
    public function studentCourses(Request $request)
{
    $user = $request->user();

    if ($user->role !== 'student') {
        return response()->json([
            'message' => 'Unauthorized. Only students can access this endpoint.'
        ], 403);
    }

    $courses = $user->studentCourses()
        ->with(['teacher'])
        ->withCount(['students', 'lessons'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($course) use ($user) {
            $course->progress = $this->calculateCourseProgress($course, $user);
            $course->teacher_name = $course->teacher->name ?? 'Unknown';
            return $course;
        });

    return response()->json(['data' => $courses], 200);
}

public function showStudentCourse(Request $request, $id)
{
    $user = $request->user();

    if ($user->role !== 'student') {
        return response()->json([
            'message' => 'Unauthorized. Only students can access this endpoint.'
        ], 403);
    }

    $course = $user->studentCourses()
        ->where('courses.id', $id)
        ->with(['teacher', 'lessons'])
        ->withCount(['students', 'lessons'])
        ->first();

    if (!$course) {
        return response()->json(['message' => 'Course not found'], 404);
    }

    $course->progress = $this->calculateCourseProgress($course, $user);
    $course->teacher_name = $course->teacher->name ?? 'Unknown';

    return response()->json(['data' => $course], 200);
}

    private function calculateCourseProgress($course, $user)
    {
        $totalLessons = $course->lessons_count;
        if ($totalLessons === 0) return 0;
        
        $completedLessons = $user->completedLessons()->where('course_id', $course->id)->count();
        return round(($completedLessons / $totalLessons) * 100);
    }
}