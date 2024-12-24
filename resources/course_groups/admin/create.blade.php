@extends('admin.layouts.app')

@push('libraries_top')

@endpush

@php
    $values = !empty($setting) ? $setting->value : null;

    if (!empty($values)) {
        $values = json_decode($values, true);
    }
@endphp

@section('content')
<div class="container">
    <h1>Create a New Group</h1>

    <!-- Display Validation Errors -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Create Group Form -->
    <form action="{{ route('course-group.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="webinar_id">Select Webinar</label>
            <select name="webinar_id" id="webinar_id" class="form-control">
                @foreach ($webinars as $webinar)
                    <option value="{{ $webinar->id }}">{{ $webinar->title }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="meeting_start_time">Start Time</label>
            <input type="datetime-local" name="meeting_start_time" id="meeting_start_time" class="form-control" required>
        </div>

        <div id="meeting_end_time_wrapper" class="form-group">
            <label for="meeting_end_time">End Time</label>
            <input type="datetime-local" name="meeting_end_time" id="meeting_end_time" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="meeting_duration">Duration (minutes)</label>
            <input type="number" name="meeting_duration" id="meeting_duration" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="student_ids">Select Students</label>
            <select name="student_ids[]" id="student_ids" class="form-control" multiple>
                <option value="">Please select a webinar first</option>
            </select>
        </div>

        <div class="form-group">
            <label for="meeting_recurring">Is this a recurring meeting?</label>
            <select name="meeting_recurring" id="meeting_recurring" class="form-control">
                <option value="0">No</option>
                <option value="1">Yes</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Create Group</button>
    </form>
</div>
@endsection
@push('scripts_bottom')
<script>
    $(document).ready(function () {
        $('#webinar_id').on('change', function () {
            const webinarId = $(this).val();
            const $studentSelect = $('#student_ids');

            // Clear current options and show loading
            $studentSelect.html('<option value="">Loading...</option>');

            if (webinarId) {
                $.ajax({
                    url: `/admin/course-group/ajax/webinar/${webinarId}/students`,
                    method: 'GET',
                    success: function (data) {
                        console.log(data);
                        $studentSelect.empty();

                        if (data.length > 0) {
                            data.forEach(student => {
                                $studentSelect.append(`<option value="${student.id}">${student.full_name}</option>`);
                            });
                        } else {
                            $studentSelect.html('<option value="">No students found</option>');
                        }
                    },
                    error: function () {
                        swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while loading students. Please try again.',
                        });
                        $studentSelect.html('<option value="">Error loading students</option>');
                    }
                });
            } else {
                $studentSelect.html('<option value="">Please select a webinar first</option>');
            }
        });
    });
</script>
@endpush

