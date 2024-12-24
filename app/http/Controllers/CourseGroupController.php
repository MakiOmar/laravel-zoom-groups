<?php
namespace App\Http\Controllers;

use App\Models\CourseGroup;
use App\Models\GroupMember;
use App\User;
use App\Models\Webinar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\LOG;
use Illuminate\Support\MessageBag;
use App\Models\Sale;


class CourseGroupController extends Controller {

	/**
	 * Display the groups for a specific webinar.
	 */
	public function listGroups( $webinarId ) {
		$webinar     = Webinar::with( array( 'groups.members.student', 'groups.instructor' ) )->findOrFail( $webinarId );
		$instructors = User::where( 'role_id', 4 )->get(); // Replace 'role' with your actual logic
		$students    = User::where( 'role_id', 1 )->get();

		return view( 'course_groups.admin.index', compact( 'webinar', 'instructors', 'students' ) );
	}
	public function addStudent( Request $request, $groupId ) {
		$validated = $request->validate(
			array(
				'student_id' => 'required|exists:users,id',
			)
		);

		// Check if the student is already in the group
		$existingMember = GroupMember::where( 'group_id', $groupId )
									->where( 'student_id', $validated['student_id'] )
									->first();

		if ( $existingMember ) {
			return response()->json(
				array(
					'success' => false,
					'message' => 'Student is already in this group.',
				)
			);
		}

		// Add the student to the group
		GroupMember::create(
			array(
				'group_id'   => $groupId,
				'student_id' => $validated['student_id'],
			)
		);

		$student = User::find( $validated['student_id'] );

		return response()->json(
			array(
				'success'       => true,
				'group_id'      => $groupId,
				'student_id'    => $student->id,
				'student_name'  => $student->full_name,
				'student_email' => $student->email,
			)
		);
	}
	public function getStudents( $webinarId ) {
		// Get users who have purchased the webinar
		$students = Sale::where( 'webinar_id', $webinarId )
			->with( 'buyer:id,full_name' ) // Eager load buyer info
			->get()
			->map(
				function ( $sale ) {
					return array(
						'id'        => $sale->buyer->id,
						'full_name' => $sale->buyer->full_name,
					);
				}
			);
		return response()->json( $students );
	}

	private function getZoomAccessToken() {
		$clientId     = getFeaturesSettings( 'zoom_client_id' );
		$clientSecret = getFeaturesSettings( 'zoom_client_secret' );
		$account_id   = getFeaturesSettings( 'zoom_account_id' );
		if ( empty( $clientId ) || empty( $clientSecret ) || empty( $account_id ) ) {
			abort( 500, 'Zoom is not configured properly' );
		}
		$response = Http::asForm()->withBasicAuth( $clientId, $clientSecret )->post(
			'https://zoom.us/oauth/token',
			array(
				'grant_type' => 'account_credentials',
				'account_id' => $account_id,
			)
		);

		if ( $response->failed() ) {
			abort( 500, 'Failed to retrieve Zoom access token: ' . $response->body() );
		}

		$data = $response->json();

		// Access token will expire in 1 hour; you may store it temporarily
		return $data['access_token'];
	}
	public function listWebinarsWithGroups() {
		$webinars = Webinar::with( 'groups' ) // Load groups relationship
		->has( 'groups' ) // Only include webinars with at least one group
		->get();

		return view( 'course_groups.admin.webninars_groups', compact( 'webinars' ) );
	}
	public function removeStudent( $groupId, $studentId ) {
		$groupMember = GroupMember::where( 'group_id', $groupId )
									->where( 'student_id', $studentId )
									->first();

		if ( $groupMember ) {
			$groupMember->delete();
			return redirect()->back()->with( 'success', 'Student removed from the group successfully.' );
		}

		return redirect()->back()->withErrors( 'Failed to remove the student from the group.' );
	}

	/**
	 * Create a Zoom meeting for the specified instructor.
	 *
	 * @param object $instructor The instructor object, containing at least an email and timezone.
	 * @param array  $data The data required to create the meeting, including:
	 *      - 'webinar_id' (string): The webinar ID to include in the meeting topic.
	 *      - 'meeting_recurring' (bool): Whether the meeting is recurring.
	 *      - 'meeting_start_time' (string): The start time of the meeting in ISO 8601 format.
	 *      - 'meeting_duration' (int): The duration of the meeting in minutes.
	 *      - 'meeting_end_time' (string|null): Optional end time for recurring meetings in ISO 8601 format.
	 *
	 *  Recurrence Settings:
	 *  - Recurrence Type (`type`):
	 *      - `1`: Daily
	 *      - `2`: Weekly
	 *      - `3`: Monthly
	 *  - Repeat Interval (`repeat_interval`):
	 *      - For Daily (`type = 1`): Interval in days (e.g., `1` = every day, `2` = every 2 days).
	 *      - For Weekly (`type = 2`): Interval in weeks (e.g., `1` = every week, `2` = every 2 weeks).
	 *      - For Monthly (`type = 3`): Interval in months (e.g., `1` = every month, `2` = every 2 months).
	 *  - Additional Weekly Fields (`type = 2`):
	 *      - `weekly_days`: Comma-separated values of days (e.g., `2,4` = Monday, Wednesday).
	 *      - Days of the week mapping:
	 *          - `1`: Sunday
	 *          - `2`: Monday
	 *          - `3`: Tuesday
	 *          - `4`: Wednesday
	 *          - `5`: Thursday
	 *          - `6`: Friday
	 *          - `7`: Saturday
	 *  - Additional Monthly Fields (`type = 3`):
	 *      - `monthly_day`: Day of the month (e.g., `15` = 15th day).
	 *      - `monthly_week`: Week of the month (`-1` = last week).
	 *      - `monthly_week_day`: Day of the week (same as weekly_days mapping).
	 *
	 * @return array Response data including success status and meeting details or error information.
	 */
	private function createZoomMeeting( $instructor, $data ) {
		$accessToken = $this->getZoomAccessToken(); // Retrieve OAuth access token

		$zoomBaseUrl = env( 'ZOOM_BASE_URL', 'https://api.zoom.us/v2' );
		// Retrieve the Laravel application time zone
		$laravelTimeZone = config( 'app.timezone' );
		// Zoom API endpoint for creating a meeting
		$zoomUrl = $zoomBaseUrl . "/users/{$instructor->email}/meetings";

		// Prepare meeting data
		$meetingData = array(
			'topic'      => "Meeting for Webinar ID {$data['webinar_id']}",
			'type'       => $data['meeting_recurring'] ? 8 : 2, // 8 for recurring, 2 for scheduled
			'start_time' => $data['meeting_start_time'],
			'duration'   => $data['meeting_duration'], // Minutes
			'timezone'   => $instructor->timezone,
			'settings'   => array(
				'host_video'        => true,
				'participant_video' => true,
				'join_before_host'  => false,
				'mute_upon_entry'   => true,
				'approval_type'     => 1, // Automatically approve
			),
		);

		// Add recurrence settings for recurring meetings
		if ( $data['meeting_recurring'] ) {
			$meetingData['recurrence'] = array(
				'type'            => 1, // Daily
				'repeat_interval' => 1, // Every day
				'end_date_time'   => $data['meeting_end_time'] ?? null, // Optional end date for recurrence
			);
		}
		// Make API request to Zoom
		$response = Http::withToken( $accessToken )
			->post( $zoomUrl, $meetingData );
		if ( $response->failed() ) {
			return array(
				'success' => false,
				'error'   => 'Failed to create Zoom meeting: ' . $response->body(),
			);
		}

		return array(
			'success' => true,
			'data'    => $response->json(), // Return the response as an array
		);
	}


	/**
	 * Create a new group for a webinar.
	 */
	public function createGroup( Request $request ) {
		$validated = $request->validate(
			array(
				'webinar_id'         => 'required|exists:webinars,id',
				'meeting_start_time' => 'required|date',
				'meeting_end_time'   => 'required|date',
				'meeting_duration'   => 'required|integer',
				'meeting_recurring'  => 'required|boolean',
				'student_ids'        => 'required|array',
				'student_ids.*'      => 'exists:users,id',
			)
		);
		$webinar   = Webinar::find( $validated['webinar_id'] );

		if ( $webinar ) {
			$instructor = User::findOrFail( $webinar->teacher_id );
		} else {
			return redirect()->back()->with( 'Error', 'No constructor specified' );
		}

		// Generate Zoom Meeting
		$zoomMeetingResponse = $this->createZoomMeeting( $instructor, $validated );

		// Check if the Zoom meeting creation was successful
		if ( ! $zoomMeetingResponse['success'] ) {
			return redirect()->back()->withErrors( array( 'zoom_meeting' => $zoomMeetingResponse['error'] ) );
		}

		$zoomMeeting = $zoomMeetingResponse['data'];

		// Create the course group in the database
		$group = CourseGroup::create(
			array(
				'webinar_id'         => $validated['webinar_id'],
				'instructor_id'      => $webinar->teacher_id,
				'meeting_id'         => $zoomMeeting['id'], // Use Zoom's meeting ID
				'meeting_start_time' => $validated['meeting_start_time'],
				'meeting_end_time'   => $validated['meeting_end_time'],
				'meeting_duration'   => $validated['meeting_duration'],
				'meeting_recurring'  => $validated['meeting_recurring'],
			)
		);

		// Attach students to the group
		foreach ( $validated['student_ids'] as $studentId ) {
			GroupMember::create(
				array(
					'group_id'   => $group->id,
					'student_id' => $studentId,
				)
			);
		}

		return redirect()->route( 'course-group.manage', $validated['webinar_id'] )
			->with( 'success', 'Group and Zoom meeting created successfully!' );
	}



	/**
	 * Show the form to create a new group for a webinar.
	 */
	public function showCreateForm() {
		$webinars    = Webinar::all(); // Fetch all webinars
		$instructors = User::where( 'role_id', 4 )->get(); // Replace 'role' with your actual logic
		$students    = User::where( 'role_id', 1 )->get();

		return view( 'course_groups.admin.create', compact( 'webinars', 'instructors', 'students' ) );
	}

	/**
	 * Display the groups for a student.
	 */
	public function studentGroups() {
		$groups = GroupMember::where( 'student_id', auth()->id() )
			->with( 'group.webinar', 'group.instructor' )
			->get();

		return view( 'course-group.student', compact( 'groups' ) );
	}
}
