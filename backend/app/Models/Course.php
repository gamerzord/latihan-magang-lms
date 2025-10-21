<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'course_code',
        'description',
        'teacher_id'
    ];

    public function students()
    {
        return $this->belongsToMany(User::class, 'enrollments', 'course_id', 'student_id')
            ->join('users', 'enrollments.student_id', '=', 'users.id')
            ->where('users.role', '=', 'student')
            ->select('users.*');
    }

    
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    public function getTeacherNameAttribute()
    {
        return $this->teacher ? $this->teacher->name : 'Unknown Teacher';
    }

    public function getLessonCountAttribute()
    {
        return $this->lessons()->count();
    }
}