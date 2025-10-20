<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_id',
        'assignment_code',
        'title',
        'description',
        'due_date'
    ];

    protected $casts = [
        'due_date' => 'date'
    ];

    public function course() {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
