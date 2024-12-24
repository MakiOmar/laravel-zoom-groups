<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToCourseGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('course_groups', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('meeting_recurring');
            // You can change the default value to fit your needs
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
            $table->dropColumn('status');
        });
    }
}
