@extends('layouts.app')

@section('content')
<div style="max-width:720px;margin:24px auto;padding:20px;background:var(--white);border:1px solid var(--border);border-radius:8px">
    <h2>Create Notification</h2>

    @if(session('success'))
        <div style="padding:8px;background:#eef9f1;border:1px solid #c7eed6;margin:8px 0;border-radius:6px">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ url('/admin/notifications') }}">
        @csrf
        <div style="margin-bottom:12px">
            <label>Title</label><br>
            <input name="title" required style="width:100%;padding:8px;border:1px solid var(--border);border-radius:6px" />
        </div>
        <div style="margin-bottom:12px">
            <label>Body</label><br>
            <textarea name="body" style="width:100%;height:120px;padding:8px;border:1px solid var(--border);border-radius:6px"></textarea>
        </div>
        <div style="margin-bottom:12px">
            <label>Send to user (optional)</label><br>
            <input name="user_id" placeholder="user id (leave blank for all)" style="width:100%;padding:8px;border:1px solid var(--border);border-radius:6px" />
        </div>
        <button style="background:var(--blue);color:white;padding:8px 12px;border-radius:6px;border:0">Create</button>
    </form>
</div>
@endsection
