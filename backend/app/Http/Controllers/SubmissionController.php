<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Submission;
use App\Models\Assignment;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SubmissionController extends Controller
{
    public function index(Request $request) {
        $query = Submission::with(['assignment', 'student']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('assignment_id')) {
            $query->where('assignment_id', $request->assignment_id);
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $submissions = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['submissions' => $submissions], 200);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'student_id' => 'required|exists:users,id',
            'file' => 'required|file|max:10240', // max 10MB
        ]);

        // Check if student already submitted
        $existingSubmission = Submission::where('assignment_id', $data['assignment_id'])
            ->where('student_id', $data['student_id'])
            ->first();

        if ($existingSubmission) {
            return response()->json([
                'message' => 'You have already submitted this assignment. Use resubmit instead.'
            ], 422);
        }

        // Get assignment to check due date
        $assignment = Assignment::findOrFail($data['assignment_id']);
        $dueDate = Carbon::parse($assignment->due_date);
        $now = Carbon::now();
        
        // Determine status based on due date
        $status = $now->greaterThan($dueDate) ? 'late' : 'submitted';

        $file = $request->file('file');
        
        // Generate unique filename
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('submissions', $fileName, 'public');

        $submission = Submission::create([
            'assignment_id' => $data['assignment_id'],
            'student_id' => $data['student_id'],
            'file_url' => $filePath,
            'filename' => $file->getClientOriginalName(),
            'mimetype' => $file->getClientMimeType(),
            'status' => $status,
            'grade' => null,
        ]);

        return response()->json([
            'message' => 'Assignment submitted successfully',
            'submission' => $submission->load(['assignment', 'student']),
        ], 201);
    }

    public function show($id) {
        $submission = Submission::with(['assignment', 'student'])->find($id);

        if (!$submission) {
            return response()->json(['message' => 'Submission Not Found'], 404);
        }

        return response()->json(['submission' => $submission], 200);
    }

    public function update(Request $request, $id) {
        $submission = Submission::find($id);

        if (!$submission) {
            return response()->json(['message' => 'Submission Not Found'], 404);
        }

        $data = $request->validate([
            'assignment_id' => 'sometimes|exists:assignments,id',
            'student_id' => 'sometimes|exists:users,id',
            'file' => 'nullable|file|max:10240',
            'status' => 'sometimes|in:submitted,late,not_submitted',
            'grade' => 'nullable|numeric|min:0|max:100',
        ]);

        // Handle file resubmission
        if ($request->hasFile('file')) {
            // Delete old file
            if ($submission->file_url && Storage::disk('public')->exists($submission->file_url)) {
                Storage::disk('public')->delete($submission->file_url);
            }

            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('submissions', $fileName, 'public');

            $submission->file_url = $filePath;
            $submission->filename = $file->getClientOriginalName();
            $submission->mimetype = $file->getClientMimeType();
            
            // Update status based on due date if resubmitting
            $assignment = Assignment::find($submission->assignment_id);
            $dueDate = Carbon::parse($assignment->due_date);
            $now = Carbon::now();
            $submission->status = $now->greaterThan($dueDate) ? 'late' : 'submitted';
        }

        // Update other fields if provided
        if (isset($data['assignment_id'])) {
            $submission->assignment_id = $data['assignment_id'];
        }
        if (isset($data['student_id'])) {
            $submission->student_id = $data['student_id'];
        }
        if (isset($data['status'])) {
            $submission->status = $data['status'];
        }
        if (isset($data['grade'])) {
            $submission->grade = $data['grade'];
        }

        $submission->save();

        return response()->json([
            'message' => 'Submission updated successfully',
            'submission' => $submission->load(['assignment', 'student']),
        ], 200);
    }

    public function destroy($id) {
        $submission = Submission::find($id);

        if (!$submission) {
            return response()->json(['message' => 'Submission Not Found'], 404);
        }

        // Delete file from storage
        if ($submission->file_url && Storage::disk('public')->exists($submission->file_url)) {
            Storage::disk('public')->delete($submission->file_url);
        }

        $submission->delete();

        return response()->json(['message' => 'Submission Deleted Successfully'], 200);
    }

    public function download($id)
    {
        $submission = Submission::findOrFail($id);

        if (!$submission->file_url || !Storage::disk('public')->exists($submission->file_url)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $path = Storage::disk('public')->path($submission->file_url);

        return response()->download(
            $path,
            $submission->filename,
            ['Content-Type' => $submission->mimetype]
        );
    }

    // Grade a submission (for teachers)
    public function grade(Request $request, $id) {
        $submission = Submission::findOrFail($id);
        
        $data = $request->validate([
            'grade' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string',
        ]);

        $submission->grade = $data['grade'];
        
        if (isset($data['feedback'])) {
            $submission->feedback = $data['feedback'];
        }

        $submission->save();

        return response()->json([
            'message' => 'Submission graded successfully',
            'submission' => $submission->load(['assignment', 'student']),
        ], 200);
    }
}