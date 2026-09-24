@extends('layouts.app')

@section('title', 'Schedule Detail')

@section('content')
@php
    $scheduleId = data_get($schedule, 'id');
    $scheduleStatus = data_get($schedule, 'status', '-');
    $scheduleStart = data_get($schedule, 'start_at');
    $scheduleEnd = data_get($schedule, 'end_at');
    $scheduleEnrolled = data_get($schedule, 'enrolled', 0);
    $courseCode = data_get($schedule, 'course.code', '-');
    $courseTitle = data_get($schedule, 'course.title', '-');
    $instructorName = data_get($schedule, 'course.instructor.name', '-');
    $classroomName = data_get($schedule, 'classroom.name', '-');
    $buildingName = data_get($schedule, 'classroom.building', '-');
@endphp
<div class="page-head">
    <h1 class="page-title">Schedule #{{ $scheduleId }}</h1>
    <a class="btn secondary" href="{{ route('admin.schedule') }}">Back to Schedules</a>
</div>

<div class="grid grid-2">
    <section class="card">
        <h2>Schedule Information</h2>
        <p><strong>Status:</strong> <span class="chip">{{ $scheduleStatus }}</span></p>
        <p><strong>Start:</strong> {{ optional($scheduleStart)->format('M d, Y H:i') }}</p>
        <p><strong>End:</strong> {{ optional($scheduleEnd)->format('M d, Y H:i') }}</p>
        <p><strong>Enrolled:</strong> {{ $scheduleEnrolled }}</p>
    </section>

    <section class="card">
        <h2>Course and Room</h2>
        <p><strong>Course:</strong> {{ $courseCode }} - {{ $courseTitle }}</p>
        <p><strong>Instructor:</strong> {{ $instructorName }}</p>
        <p><strong>Classroom:</strong> {{ $classroomName }}</p>
        <p><strong>Building:</strong> {{ $buildingName }}</p>
    </section>
</div>
@endsection
