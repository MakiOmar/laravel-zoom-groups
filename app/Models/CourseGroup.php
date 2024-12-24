<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\GroupMember;
use App\User;
use App\Models\Webinar;

class CourseGroup extends Model {

	protected $fillable = array(
		'webinar_id',
		'instructor_id',
		'meeting_id',
		'meeting_start_time',
		'meeting_end_time',
		'meeting_duration',
		'meeting_recurring',
	);

	public function webinar() {
		return $this->belongsTo( Webinar::class, 'webinar_id' );
	}

	public function instructor() {
		return $this->belongsTo( User::class, 'instructor_id' );
	}

	public function members() {
		return $this->hasMany( GroupMember::class, 'group_id' );
	}
}
