<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseGroupController;


Route::prefix( 'course-group' )->group(
	function () {
		Route::get( '/manage/{webinarId}', array( CourseGroupController::class, 'listGroups' ) )->name( 'course-group.manage' );

		Route::get( '/webinar-groups/', array( CourseGroupController::class, 'listWebinarsWithGroups' ) )->name( 'webinar-groups.manage' );

		Route::get( '/create', array( CourseGroupController::class, 'showCreateForm' ) )->name( 'course-group.create-form' );
		Route::post( '/store', array( CourseGroupController::class, 'createGroup' ) )->name( 'course-group.store' );
		Route::get( '/student', array( CourseGroupController::class, 'studentGroups' ) )->name( 'course-group.student' );

		Route::delete( '/course-group/{group}/student/{student}', array( CourseGroupController::class, 'removeStudent' ) )->name( 'group.student.remove' );

		Route::post( '/student/add', array( CourseGroupController::class, 'addStudent' ) )->name( 'course-group.student.add' );

		Route::post( '/{group}/add-student', array( CourseGroupController::class, 'addStudent' ) )->name( 'group.student.add' );

		Route::get( '/ajax/webinar/{webinar}/students', array( CourseGroupController::class, 'getStudents' ) );
	}
);
