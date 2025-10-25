<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lesson;

class LessonController extends Controller
{
    public function index() {
        $lessons = Lesson::with('course')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(
            [
                'lessons' => $lessons,
            ],
            200,
        );
    }

    public function store(Request $request) {
        $data = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'lesson_code' => 'required|string|max:50|unique:lessons,lesson_code',
            'content' => 'nullable|string',
        ]);

        $lesson = Lesson::create($data);

        return response()->json(
            [
                'message' => 'Lesson created successfully',
                'lesson' => $lesson,
            ],
            201,
        );
    }

    public function show($id) {
        $lesson = Lesson::with('course')
            ->find($id);
        
        if(!$lesson) {
            return response()->json(
                [
                    'message' => 'Lesson Not Found',
                ],
                404,
            );
        }

        return response()->json(
            [
                'lesson' => $lesson,
            ],
            200,
        );
    }

    public function update(Request $request, $id) {
        $lesson = Lesson::with('course')
            ->find($id);
        
        if(!$lesson) {
            return response()->json(
                [
                    'message' => 'Lesson Not Found',
                ],
                404,
            );
        }

        $data = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'lesson_code' => 'required|string|max:50|unique:lessons,lesson_code' . $id,
            'content' => 'nullable|string',
        ]);

        $lesson->update($data);

        return response()->json(
            [
                'message' => 'Lesson Updated Successfully',
                'lesson' => $lesson,
            ],
            200,
        );
    }

    public function destroy($id) {
        $lesson = Lesson::with('course')
            ->find($id);
        
        if(!$lesson) {
            return response()->json(
                [
                    'message' => 'Lesson Not Found',
                ],
                404,
            );
        }

        $lesson->delete();

        return response()->json(
            [
                'message' => 'Lesson Deleted Successfully',
            ],
            200,
        );
    }
}
