<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourseGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('course_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('webinar_id');
            $table->foreign('webinar_id')->references('id')->on('webinars')->onDelete('cascade');
            $table->unsignedInteger('instructor_id'); // Match users.id type
            $table->foreign('instructor_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('meeting_id')->unique();
            $table->dateTime('meeting_start_time');
            $table->unsignedInteger('meeting_duration')->comment('Duration in minutes');
            $table->boolean('meeting_recurring')->default(false);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('course_groups');
    }
}
