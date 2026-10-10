@extends('layouts.app')

@section('title', 'Create Notification')
@section('body-class', 'admin-notification-create-page')

@section('content')
<main class="notification-create-page">
    <header class="notification-page-header">
        <div>
            <h1>Create Notification</h1>
            <p>Publish an announcement to everyone or send it to one user.</p>
        </div>
    </header>

    @if (session('success'))
        <div class="notification-feedback notification-feedback-success" role="status">
            <i class="fas fa-circle-check" aria-hidden="true"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="notification-feedback notification-feedback-error" role="alert">
            <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <section class="notification-form-panel" aria-labelledby="notificationFormHeading">
        <div class="notification-form-heading">
            <h2 id="notificationFormHeading">Announcement details</h2>
            <p>Use a short title and include the details recipients need to know.</p>
        </div>

        <form class="notification-form" method="POST" action="{{ route('admin.notifications.store') }}">
            @csrf
            <div class="notification-field">
                <label for="notificationTitle">Title <span class="required-label">Required</span></label>
                <input
                    id="notificationTitle"
                    name="title"
                    type="text"
                    value="{{ old('title') }}"
                    maxlength="255"
                    autocomplete="off"
                    required
                    aria-describedby="notificationTitleError"
                >
                @error('title')
                    <span class="notification-field-error" id="notificationTitleError">{{ $message }}</span>
                @enderror
            </div>

            <div class="notification-field">
                <label for="notificationBody">Body <span class="optional-label">Optional</span></label>
                <textarea
                    id="notificationBody"
                    name="body"
                    rows="6"
                    aria-describedby="notificationBodyHint notificationBodyError"
                >{{ old('body') }}</textarea>
                <span class="notification-field-hint" id="notificationBodyHint">Add context, timing, or any action recipients should take.</span>
                @error('body')
                    <span class="notification-field-error" id="notificationBodyError">{{ $message }}</span>
                @enderror
            </div>

            <div class="notification-field">
                <label for="notificationUserId">Send to user <span class="optional-label">Optional</span></label>
                <input
                    id="notificationUserId"
                    name="user_id"
                    type="number"
                    min="1"
                    step="1"
                    value="{{ old('user_id') }}"
                    inputmode="numeric"
                    aria-describedby="notificationUserHint notificationUserError"
                >
                <span class="notification-field-hint" id="notificationUserHint">Leave blank to send this announcement to everyone.</span>
                @error('user_id')
                    <span class="notification-field-error" id="notificationUserError">{{ $message }}</span>
                @enderror
            </div>

            <div class="notification-form-actions">
                <button class="notification-submit" type="submit">
                    <i class="fas fa-paper-plane" aria-hidden="true"></i>
                    <span>Create notification</span>
                </button>
            </div>
        </form>
    </section>
</main>

<style>
body.admin-notification-create-page .main-content {
    background: #f4f7fb;
}

.notification-create-page {
    --notice-navy: #0b1640;
    --notice-blue: #1a2f80;
    --notice-muted: #5a6785;
    --notice-border: #dbe3f5;
    width: min(100%, 1040px);
    min-height: 100%;
    padding: 22px 28px 48px;
    margin: 0 auto;
    color: var(--notice-navy);
}

.notification-page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    padding: 5px 0 22px;
    margin-bottom: 24px;
    border-bottom: 1px solid #e1e7f0;
}

.notification-page-header h1 {
    margin: 0;
    color: var(--notice-navy);
    font-size: 1.65rem;
    font-weight: 750;
    line-height: 1.25;
}

.notification-page-header p {
    margin: 7px 0 0;
    color: var(--notice-muted);
    font-size: .9rem;
    line-height: 1.5;
}

.notification-form-panel {
    width: min(100%, 760px);
    padding: 25px 28px 28px;
    border: 1px solid var(--notice-border);
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 2px 8px rgba(11,22,64,.045);
}

.notification-form-heading {
    padding-bottom: 17px;
    margin-bottom: 21px;
    border-bottom: 1px solid #e8edf4;
}

.notification-form-heading h2 {
    margin: 0;
    color: var(--notice-navy);
    font-size: 1.03rem;
    font-weight: 700;
}

.notification-form-heading p {
    margin: 5px 0 0;
    color: var(--notice-muted);
    font-size: .82rem;
    line-height: 1.5;
}

.notification-form {
    display: grid;
    gap: 19px;
}

.notification-field {
    display: grid;
    gap: 7px;
}

.notification-field label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--notice-navy);
    font-size: .83rem;
    font-weight: 650;
}

.required-label,
.optional-label {
    color: var(--notice-muted);
    font-size: .7rem;
    font-weight: 500;
}

.notification-field input,
.notification-field textarea {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    background: #fff;
    color: #172033;
    font: inherit;
    font-size: .9rem;
    line-height: 1.5;
    transition: border-color .15s ease, box-shadow .15s ease;
}

.notification-field input {
    min-height: 44px;
    padding: 9px 12px;
}

.notification-field textarea {
    min-height: 148px;
    padding: 11px 12px;
    resize: vertical;
}

.notification-field input:focus,
.notification-field textarea:focus {
    border-color: #1a2f80;
    outline: none;
    box-shadow: 0 0 0 3px rgba(26,47,128,.12);
}

.notification-field-hint {
    color: var(--notice-muted);
    font-size: .76rem;
    line-height: 1.45;
}

.notification-field-error {
    color: #b42318;
    font-size: .78rem;
    line-height: 1.4;
}

.notification-form-actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 3px;
}

.notification-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 0 16px;
    border: 1px solid #0b1640;
    border-radius: 6px;
    background: #0b1640;
    color: #fff;
    cursor: pointer;
    font: inherit;
    font-size: .84rem;
    font-weight: 650;
    transition: background-color .15s ease, border-color .15s ease;
}

.notification-submit:hover {
    border-color: #1a2f80;
    background: #1a2f80;
}

.notification-submit:focus-visible {
    outline: 3px solid #f5c518;
    outline-offset: 3px;
}

.notification-feedback {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    width: min(100%, 760px);
    padding: 12px 14px;
    margin: 0 0 16px;
    border: 1px solid;
    border-radius: 6px;
    font-size: .84rem;
    line-height: 1.45;
}

.notification-feedback-success {
    border-color: #bbf7d0;
    background: #f0fdf4;
    color: #166534;
}

.notification-feedback-error {
    border-color: #fecaca;
    background: #fff5f5;
    color: #991b1b;
}

@media (max-width: 768px) {
    .notification-create-page {
        padding: 18px 12px 32px;
    }

    .notification-page-header {
        padding-top: 12px;
        margin-bottom: 18px;
    }

    .notification-page-header h1 {
        font-size: 1.4rem;
    }

    .notification-form-panel {
        padding: 20px 17px 22px;
    }

    .notification-form-actions,
    .notification-submit {
        width: 100%;
    }
}

@media (prefers-reduced-motion: reduce) {
    .notification-field input,
    .notification-field textarea,
    .notification-submit {
        transition: none;
    }
}
</style>
@endsection