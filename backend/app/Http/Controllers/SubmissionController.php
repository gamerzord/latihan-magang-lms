<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Submission;
use Illuminate\Support\Facades\Storage;


class SubmissionController extends Controller
{
    public function index(Request $request) {
        $query = Submission::with(['assignment', 'student']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $submissions = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['submissions' => $submissions], 200);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'assignment_id' => 'required|exists:assignments,id',
            'student_id' => 'required|exists:users,id',
            'file' => 'required|file|max:10240', // up to 10MB
        ]);

        $file = $request->file('file');
        $filePath = $file->store('submissions', 'public'); // store to storage/app/public/submissions

        $submission = Submission::create([
            'assignment_id' => $data['assignment_id'],
            'student_id' => $data['student_id'],
            'file_url' => $filePath,
            'filename' => $file->getClientOriginalName(),
            'mimetype' => $file->getClientMimeType(),
            'status' => 'submitted',
            'grade' => 0,
        ]);

        return response()->json([
            'message' => 'Submission Added Successfully',
            'submission' => $submission,
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
            'assignment_id' => 'required|exists:assignments,id',
            'student_id' => 'required|exists:users,id',
            'file' => 'nullable|file|max:10240',
            'status' => 'required|in:submitted,late,not_submitted',
            'grade' => 'required|numeric',
        ]);

        if ($request->hasFile('file')) {
            // delete old file if exists
            if ($submission->file_url && Storage::disk('public')->exists($submission->file_url)) {
                Storage::disk('public')->delete($submission->file_url);
            }

            $file = $request->file('file');
            $filePath = $file->store('submissions', 'public');

            $submission->file_url = $filePath;
            $submission->filename = $file->getClientOriginalName();
            $submission->mimetype = $file->getClientMimeType();
        }

        $submission->assignment_id = $data['assignment_id'];
        $submission->student_id = $data['student_id'];
        $submission->status = $data['status'];
        $submission->grade = $data['grade'];
        $submission->save();

        return response()->json([
            'message' => 'Submission Updated Successfully',
            'submission' => $submission,
        ], 200);
    }

    public function destroy($id) {
        $submission = Submission::find($id);

        if (!$submission) {
            return response()->json(['message' => 'Submission Not Found'], 404);
        }

        if ($submission->file_url && Storage::disk('public')->exists($submission->file_url)) {
            Storage::disk('public')->delete($submission->file_url);
        }

        $submission->delete();

        return response()->json(['message' => 'Submission Deleted Successfully'], 200);
    }

public function download($id) {
    $submission = Submission::findOrFail($id);

    if (!$submission->file_url || !Storage::disk('public')->exists($submission->file_url)) {
        return response()->json(['message' => 'No file found for this submission'], 404);
    }

    return response()->streamDownload(function () use ($submission) {
        echo Storage::disk('public')->get($submission->file_url);
    }, $submission->filename, [
        'Content-Type' => $submission->mimetype,
    ]);
}
}
