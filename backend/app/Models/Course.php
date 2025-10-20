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
        'teacher_id',
        'is_active'
    ];

    public function students()
    {
        return $this->belongsToMany(User::class, 'enrollments', 'course_id', 'student_id')
        ->where('role', 'student');
    }
    
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

}
