<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lesson_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_url');
            $table->string('file_type')->nullable(); // pdf, doc, image, etc
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0); // in bytes
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lesson_attachments');
    }
};