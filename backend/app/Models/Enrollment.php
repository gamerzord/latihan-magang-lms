<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_id',
        'student_id'
    ];

    public function course() {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function student() {
        return $this->belongsTo(User::class, 'student_id');
    }
}
