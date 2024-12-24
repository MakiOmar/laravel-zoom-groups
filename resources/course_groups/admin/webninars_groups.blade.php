@extends('admin.layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h1>Webinars with Course Groups</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="/admin">Dashboard</a>
            </div>
            <div class="breadcrumb-item">Webinars with Course Groups</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
            <!-- Table for webinars with course groups -->
            <table class="table table-striped font-14 ">
                <thead>
                    <tr>
                        <th>Webinar Title</th>
                        <th>Total Groups</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($webinars as $webinar)
                        <tr>
                            <td>
                                <a href="{{ route('course-group.manage', $webinar->id) }}">
                                    {{ $webinar->title }}
                                </a>
                            </td>
                            <td>{{ $webinar->groups->count() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center">No webinars with course groups found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</section>
@endsection
