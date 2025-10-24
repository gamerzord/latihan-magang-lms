<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Submission;

class CourseController extends Controller
{
public function index() {
    $user = auth()->user();
    
    if ($user->role === 'student') {
        $courses = Course::with('teacher')
            ->withCount(['students', 'lessons'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($course){
            $course->teacher_name = $course->teacher->name ?? 'Unknown';
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
            ->get()
            ->map(function ($course){
            $course->teacher_name = $course->teacher->name ?? 'Unknown';
            return $course;
        }); 
    }

    return response()->json([
        'courses' => $courses,
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
            'course' => $course->loadCount(['students', 'lessons']),
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


        return response()->json([
            'course' => $course,
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
            'course' => $course->loadCount(['students', 'lessons']),
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
        ->map(function ($course){
            $course->teacher_name = $course->teacher->name ?? 'Unknown';
            return $course;
        }); 

    return response()->json(['courses' => $courses], 200);
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
        ->with(['teacher', 'lessons', 'assignments'])
        ->withCount(['students', 'lessons', 'assignments'])
        ->first();

    if (!$course) {
        return response()->json(['message' => 'Course not found'], 404);
    }

    $course->teacher_name = $course->teacher->name ?? 'Unknown';

    return response()->json(['course' => $course], 200);
}

public function teacherCourses(Request $request)
{
    $user = $request->user();

    if ($user->role !== 'teacher') {
        return response()->json([
            'message' => 'Unauthorized. Only teachers can access this endpoint.'
        ], 403);
    }

    $courses = $user->teacherCourses()
        ->withCount(['students', 'lessons'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($course) {
            $course->students_count = $course->students_count ?? 0;
            $course->lessons_count = $course->lessons_count ?? 0;
            return $course;
        });

    return response()->json(['courses' => $courses], 200);
}

public function showTeacherCourse(Request $request, $id)
{
    $user = $request->user();

    if ($user->role !== 'teacher') {
        return response()->json([
            'message' => 'Unauthorized. Only teachers can access this endpoint.'
        ], 403);
    }

    $course = $user->teacherCourses()
        ->where('courses.id', $id)
        ->with(['students', 'lessons', 'assignments'])
        ->withCount(['students', 'lessons', 'assignments'])
        ->first();

    if (!$course) {
        return response()->json(['message' => 'Course not found'], 404);
    }

    $course->total_students = $course->students_count;
    $course->total_lessons = $course->lessons_count;
    $course->total_assignments = $course->assignments_count;

    return response()->json(['course' => $course], 200);

    
}
public function getCourseSubmissions(Request $request, $id)
{
    $user = $request->user();

    if ($user->role !== 'teacher') {
        return response()->json([
            'message' => 'Unauthorized. Only teachers can access this endpoint.'
        ], 403);
    }

    $course = $user->teacherCourses()
        ->where('courses.id', $id)
        ->first();

    if (!$course) {
        return response()->json(['message' => 'Course not found'], 404);
    }

    $submissions = Submission::whereHas('assignment', function($query) use ($id) {
            $query->where('course_id', $id);
        })
        ->with(['assignment', 'student'])
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json(['submissions' => $submissions], 200);
}
}