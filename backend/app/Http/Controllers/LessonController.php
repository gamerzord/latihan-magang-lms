<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    public function index()
    {
        $lessons = Lesson::with(['course', 'attachments'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'lessons' => $lessons,
        ], 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'lesson_code' => 'required|string|max:50|unique:lessons,lesson_code',
            'content' => 'nullable|string',
        ]);

        $lesson = Lesson::create($data);

        if ($request->hasFile('attachments')) {
            $this->handleAttachments($request->file('attachments'), $lesson->id);
        }

        return response()->json([
            'message' => 'Lesson created successfully',
            'lesson' => $lesson->load('attachments'),
        ], 201);
    }

    public function show($id)
    {
        $lesson = Lesson::with(['course', 'attachments'])->find($id);
        
        if (!$lesson) {
            return response()->json([
                'message' => 'Lesson Not Found',
            ], 404);
        }
        
        return response()->json([
            'lesson' => $lesson,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $lesson = Lesson::with(['course', 'attachments'])->find($id);
        
        if (!$lesson) {
            return response()->json([
                'message' => 'Lesson Not Found',
            ], 404);
        }

        $data = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'lesson_code' => 'required|string|max:50|unique:lessons,lesson_code,' . $id,
            'content' => 'nullable|string',
        ]);

        $lesson->update($data);

        // Handle new file uploads
        if ($request->hasFile('attachments')) {
            $this->handleAttachments($request->file('attachments'), $lesson->id);
        }

        return response()->json([
            'message' => 'Lesson Updated Successfully',
            'lesson' => $lesson->load('attachments'),
        ], 200);
    }

    public function destroy($id)
    {
        $lesson = Lesson::with('attachments')->find($id);
        
        if (!$lesson) {
            return response()->json([
                'message' => 'Lesson Not Found',
            ], 404);
        }

        foreach ($lesson->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $lesson->delete();

        return response()->json([
            'message' => 'Lesson Deleted Successfully',
        ], 200);
    }

    public function uploadAttachments(Request $request, $lessonId)
    {
        $request->validate([
            'attachments' => 'required|array',
            'attachments.*' => 'file|max:10240',
        ]);

        $lesson = Lesson::findOrFail($lessonId);
        
        $uploadedFiles = $this->handleAttachments($request->file('attachments'), $lessonId);

        return response()->json([
            'message' => 'Files uploaded successfully',
            'attachments' => $uploadedFiles,
        ], 200);
    }

    public function deleteAttachment($attachmentId)
    {
        $attachment = LessonAttachment::findOrFail($attachmentId);
        
        // Delete file from storage
        Storage::disk('public')->delete($attachment->file_path);
        
        // Delete database record
        $attachment->delete();

        return response()->json([
            'message' => 'Attachment deleted successfully',
        ], 200);
    }


    private function handleAttachments($files, $lessonId)
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            // Generate unique filename
            $fileName = time() . '_' . $file->getClientOriginalName();
            
            // Store file in public/lesson_attachments
            $filePath = $file->storeAs('lesson_attachments', $fileName, 'public');
            
            // Get file info
            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();
            $fileType = $this->getFileType($mimeType);

            // Create database record
            $attachment = LessonAttachment::create([
                'lesson_id' => $lessonId,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_url' => Storage::url($filePath),
                'file_type' => $fileType,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
            ]);

            $uploadedFiles[] = $attachment;
        }

        return $uploadedFiles;
    }

    private function getFileType($mimeType)
    {
        if (str_contains($mimeType, 'pdf')) return 'pdf';
        if (str_contains($mimeType, 'word') || str_contains($mimeType, 'document')) return 'doc';
        if (str_contains($mimeType, 'image')) return 'image';
        if (str_contains($mimeType, 'video')) return 'video';
        if (str_contains($mimeType, 'audio')) return 'audio';
        return 'other';
    }
}