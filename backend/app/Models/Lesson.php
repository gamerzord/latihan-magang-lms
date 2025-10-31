<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_id',
        'title',
        'lesson_code',
        'content'
    ];

    public function course() {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function attachments()
    {
        return $this->hasMany(LessonAttachment::class);
    }
}
