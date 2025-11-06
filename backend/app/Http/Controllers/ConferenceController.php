<?php

namespace App\Http\Controllers;

use App\Models\Conference;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ConferenceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        if ($user->role === 'teacher') {
            $conferences = Conference::where('teacher_id', $user->id)
                ->with('course')
                ->latest()
                ->get();
        } else {
            // Students see conferences from their enrolled courses
            $courseIds = $user->enrollments()->pluck('course_id');
            $conferences = Conference::whereIn('course_id', $courseIds)
                ->with('course', 'teacher')
                ->latest()
                ->get();
        }

        return response()->json([
            'conferences' => $conferences,
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
        ]);

        $user = $request->user();

        // Verify teacher owns the course
        $course = Course::where('id', $validated['course_id'])
            ->where('teacher_id', $user->id)
            ->firstOrFail();

        $conference = Conference::create([
            'course_id' => $validated['course_id'],
            'teacher_id' => $user->id,
            'title' => $validated['title'],
            'room_id' => Str::random(16),
            'status' => 'scheduled',
        ]);

        return response()->json([
            'conference' => $conference->load('course')], 201);
    }

    public function show(Request $request, $id)
    {
        $conference = Conference::with('course', 'teacher')->findOrFail($id);
        
        $user = $request->user();
        
        // Check authorization
        if ($user->role === 'teacher' && $conference->teacher_id !== $user->id) {
            abort(403, 'Unauthorized');
        }
        
        if ($user->role === 'student') {
            $enrolled = $user->enrollments()->where('course_id', $conference->course_id)->exists();
            if (!$enrolled) {
                abort(403, 'You are not enrolled in this course');
            }
        }

        return response()->json([
            'conference' => $conference,
        ], 200);
    }

    public function start(Request $request, $id)
    {
        $conference = Conference::findOrFail($id);
        
        if ($conference->teacher_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $conference->update([
            'status' => 'active',
            'started_at' => now(),
        ]);

        return response()->json([
            'conference' => $conference,
        ], 200);
    }

    public function end(Request $request, $id)
    {
        $conference = Conference::findOrFail($id);
        
        if ($conference->teacher_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $conference->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);

        return response()->json([
            'conference' => $conference,
        ], 200);
    }

    public function destroy(Request $request, $id)
    {
        $conference = Conference::findOrFail($id);
        
        if ($conference->teacher_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $conference->delete();

        return response()->json(['message' => 'Conference deleted successfully']);
    }
}