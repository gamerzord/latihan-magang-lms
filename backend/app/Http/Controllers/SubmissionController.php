<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Submission;

class SubmissionController extends Controller
{
    public function index(Request $request) {
        $query = Submission::with(['assignment', 'student']);

        if($request->has('status')) {
            $query->where('status', $request->status);
        }

        $submissions = $query->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json(
            [
                'submissions' => $submissions,
            ],
            200,
        );
    }

    public function store(Request $request) {
        $data = $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'student_id' => 'required|exists:users,id',
            'file_url' => 'required|string|max:255',
            'status' => 'required|in:submitted, late, not_submitted',
            'grade' => 'required|numeric'
        ]);

        $submission = Submission::create($data);

        return response()->json(
            [
                'message' => 'Submission Added Successfully',
                'submission' => $submission,
            ],
            201,
        );
    }

    public function show($id) {
        $submission = Submission::with(['assignment', 'student'])
            ->find($id);

        if(!$submission) {
            return response()->json(
                [
                    'message' => 'Submission Not Found',
                ],
                404,
            );
        }

        return response()->json(
            [
                'submission' => $submission,
            ],
            200,
        );
    }

    public function update(Request $request, $id) {
        $submission = Submission::with(['assignment', 'student'])
            ->find($id);

        if(!$submission) {
            return response()->json(
                [
                    'message' => 'Submission Not Found',
                ],
                404,
            );
        }

        $data = $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'student_id' => 'required|exists:users,id',
            'file_url' => 'required|string|max:255',
            'status' => 'required|in:submitted, late, not_submitted',
            'grade' => 'required|numeric'
        ]);

        $submission->update($data);

        return response()->json(
            [
                'message' => 'Submission Edited Succesfully',
                'submission' => $submission,
            ],
            200,
        );
    }

    public function destroy($id) {
        $submission = Submission::with(['assignment', 'student'])
            ->find($id);

        if(!$submission) {
            return response()->json(
                [
                    'message' => 'Submission Not Found',
                ],
                404,
            );
        }

        if($submission->assignment()->exists() && $submission->student()->exists()) {
            return response()->json(
                [
                    'message' => 'Cannot Delete Submission since Assignment and Student Still Exist',
                ],
                422,
            );
        }

        $submission->delete();

        return response()->json(
            [
                'message' => 'Submission Deleted Successfully',
            ]
        );
    }
}
