<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assignment;

class AssignmentController extends Controller
{
    public function index() {
        $assignments = Assignment::with('course')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(
            [
                'assignments' => $assignments,
            ],
            200,
        );
    }

    public function store(Request $request) {
        $data = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'assignment_code' => 'required|string|max:50|unique:assignments,assignment_code',
            'title' => 'required|string|max:255',
            'description' => 'nullable:string',
            'due_date' => 'required|date',
        ]);

        $assignment = Assignment::create($data);

        return response()->json(
            [
                'message' => 'Assignment Created Succesfully',
                'assignment' => $assignment,
            ],
            201,
        );
    }

    public function show($id) {
        $assignment = Assignment::with('course')
            ->find($id);

        if(!$assignment) {
            return response()->json(
                [
                    'message' => 'Assignment Not Found',
                ],
                404,
            );
        }

        return response()->json(
            [
                'assignment' => $assignment,
            ],
            200,
        );
    }

    public function update(Request $request, $id) {
        $assignment = Assignment::with('course')
            ->find($id);

        if(!$assignment) {
            return response()->json(
                [
                    'message' => 'Assignment Not Found',
                ],
                404,
            );
        }

        $data = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'assignment_code' => 'required|string|max:50|unique:assignments,assignment_code' . $id,
            'title' => 'required|string|max:255',
            'description' => 'nullable:string',
            'due_date' => 'required|date',
        ]);

        $assignment->update($data);

        return response()->json(
            [
                'message' => 'Assignment Updated Successfully',
                'assignment' => $assignment
            ],
            200,
        );
    }

    public function destroy($id) {
                $assignment = Assignment::with('course')
            ->find($id);

        if(!$assignment) {
            return response()->json(
                [
                    'message' => 'Assignment Not Found',
                ],
                404,
            );
        }

        if($assignment->course()->exists()) {
            return response()->json(
                [
                    'message' => 'Cannot Delete Assignment if Course Still Exist',
                ],
                422,
            );
        }

        $assignment->delete();

        return response()->json(
            [
                'message' => 'Assignment Successfully Deleted',
            ]
        );
    }
}
