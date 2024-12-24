@extends('admin.layouts.app')

@push('libraries_top')

@endpush

@push('styles_top')
<style>
    .border-style {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .flex-item {
        padding: 0.5rem;
        border-left: 2px dashed #ccc; /* خط متقطع على اليسار */
        border-right: 2px dashed #ccc; /* خط متقطع على اليمين */
        flex: 1; /* توزيع العناصر بالتساوي */
        text-align: center;
    }
    
    /* إزالة الحدود اليسرى لأول عنصر واليمنى لآخر عنصر */
    .flex-item:first-child {
        border-left: none;
    }
    
    .flex-item:last-child {
        border-right: none;
    }

</style>
@endpush

@section('content')
<section class="section">
    <div class="section-header">
        <h1>المجموعات لـ {{ $webinar->title }}</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="/admin">لوحة التحكم</a></div>
            <div class="breadcrumb-item"><a href="/admin/course-group/webinar-groups">الدورات مع المجموعات</a></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- المجموعات -->
            <h5>المجموعات</h5>
            <div id="groupsAccordion">
                @foreach ($webinar->groups as $group)
                <div class="card">
                    <div class="card-header" id="heading{{ $group->id }}">
                        <h2 class="mb-0">
                            <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapse{{ $group->id }}" aria-expanded="false" aria-controls="collapse{{ $group->id }}">
                                المجموعة: {{ $group->id }} - معرف الاجتماع: {{ $group->meeting_id }}
                            </button>
                            <button class="btn btn-primary btn-sm add-student-btn" data-group-id="{{ $group->id }}" data-toggle="modal" data-target="#addStudentModal">
                                إضافة طالب
                            </button>
                        </h2>
                    </div>
                    <div id="collapse{{ $group->id }}" class="collapse" aria-labelledby="heading{{ $group->id }}" data-parent="#groupsAccordion">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center flex-wrap border-style">
                                <div class="flex-item px-3">
                                    <strong>وقت بدء الاجتماع:</strong> {{ $group->meeting_start_time }}
                                </div>
                                <div class="flex-item px-3">
                                    <strong>المحاضر:</strong> {{ $group->instructor->full_name }}
                                </div>
                                <div class="flex-item px-3">
                                    <strong>عدد الطلاب:</strong> {{ $group->members->count() }}
                                </div>
                            </div>


                            <!-- جدول الطلاب -->
                            <div class="px-3 mt-4">
                                <h6>الطلاب</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>اسم الطالب</th>
                                                <th>البريد الإلكتروني</th>
                                                <th>الإجراء</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($group->members as $member)
                                                <tr>
                                                    <td>{{ $member->student->full_name }}</td>
                                                    <td>{{ $member->student->email }}</td>
                                                    <td>
                                                        <form method="POST" action="{{ route('group.student.remove', ['group' => $group->id, 'student' => $member->student->id]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
<div class="modal fade" id="addStudentModal" tabindex="-1" role="dialog" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStudentModalLabel">إضافة طالب إلى المجموعة</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="إغلاق">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addStudentForm">
                <div class="modal-body">
                    <input type="hidden" id="groupId" name="group_id">
                    <div class="form-group">
                        <label for="studentSelect">اختر الطالب</label>
                        <select class="form-control" id="studentSelect" name="student_id" required>
                            <option value="">-- اختر الطالب --</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->email }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">إضافة الطالب</button>
                </div>
            </form>
        </div>
    </div>
</div>


@endsection
@push('scripts_bottom')
<script>
    $(document).ready(function () {
        // فتح النافذة وتعيين معرف المجموعة
        $('.add-student-btn').on('click', function () {
            const groupId = $(this).data('group-id');
            $('#groupId').val(groupId);
        });

        // معالجة إرسال النموذج
        $('#addStudentForm').on('submit', function (e) {
            e.preventDefault();

            const groupId = $('#groupId').val();
            const studentId = $('#studentSelect').val();

            $.ajax({
                url: `{{ route('group.student.add', ':groupId') }}`.replace(':groupId', groupId),
                method: 'POST',
                data: {
                    student_id: studentId,
                    _token: '{{ csrf_token() }}',
                },
                success: function (response) {
                    if (response.success) {
                        // إغلاق النافذة وإعادة تعيين النموذج
                        $('#addStudentModal').modal('hide');
                        $('#addStudentForm')[0].reset();

                        // إضافة صف الطالب الجديد إلى الجدول
                        const newRow = `
                            <tr>
                                <td>${response.student_name}</td>
                                <td>${response.student_email}</td>
                                <td>
                                    <form method="POST" action="{{ route('group.student.remove', ['group' => ':groupId', 'student' => ':studentId']) }}"
                                          data-group-id="${response.group_id}" data-student-id="${response.student_id}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                    </form>
                                </td>
                            </tr>`;
                        $(`#collapse${response.group_id} .table tbody`).append(newRow);
                        // عرض رسالة نجاح
                        Swal.fire({
                            icon: 'success',
                            title: 'تم',
                            text: `تمت إضافة ${response.student_name} بنجاح`,
                            timer: 3000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: response.message || 'فشلت عملية الإضافة',
                        });
                    }
                },
                error: function (xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'فشلت عملية الإضافة',
                    });
                    console.log('An error occurred: ' + (xhr.responseJSON?.message || xhr.statusText));
                },
            });
        });
    });
</script>
@endpush
