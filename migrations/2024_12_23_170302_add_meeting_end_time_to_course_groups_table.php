<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMeetingEndTimeToCourseGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('course_groups', function (Blueprint $table) {
            $table->dateTime('meeting_end_time')->nullable()->after('meeting_start_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('course_groups', function (Blueprint $table) {
            $table->dropColumn('meeting_end_time');
        });
    }
}
