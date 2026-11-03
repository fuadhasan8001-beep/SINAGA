@extends('layouts.admin')

@section('title', 'Profil Akun')
@section('heading', 'Profil Akun')
@section('subtitle', 'Perbarui nama dan kata sandi akun Anda.')

@section('content')
    @php
        $passwordFields = [
            ['id' => 'currentPassword', 'name' => 'current_password', 'label' => 'Kata sandi saat ini', 'autocomplete' => 'current-password'],
            ['id' => 'newPassword', 'name' => 'new_password', 'label' => 'Kata sandi baru', 'autocomplete' => 'new-password', 'minlength' => 8],
            ['id' => 'newPasswordConfirmation', 'name' => 'new_password_confirmation', 'label' => 'Ulangi kata sandi baru', 'autocomplete' => 'new-password', 'minlength' => 8],
        ];
    @endphp

    <form method="post" action="{{ $panelRoute('admin.profile.update') }}" class="panel profile-form account-profile-form">
        @csrf
        @method('PUT')

        <h2 class="form-title">Informasi akun</h2>
        <div class="account-identity-fields">
            <label for="profileName">Nama
                <input id="profileName" name="name" value="{{ old('name', auth()->user()->name) }}" required minlength="2" maxlength="120">
            </label>
            <label for="profileEmail">Email
                <input id="profileEmail" value="{{ auth()->user()->email }}" disabled>
            </label>
        </div>

        <div class="account-password-heading">
            <h2>Ganti kata sandi</h2>
            <p>Isi kata sandi saat ini untuk menggantinya. Kata sandi baru minimal 8 karakter.</p>
        </div>
        @foreach($passwordFields as $index => $field)
            @if($index === 1)<div class="account-new-password-fields">@endif
            <label for="{{ $field['id'] }}">{{ $field['label'] }}
                <span class="password-input-wrap">
                    <input id="{{ $field['id'] }}" name="{{ $field['name'] }}" type="password" autocomplete="{{ $field['autocomplete'] }}" @if(isset($field['minlength'])) minlength="{{ $field['minlength'] }}" @endif>
                    <button class="password-toggle" type="button" data-password-toggle aria-label="Tampilkan {{ strtolower($field['label']) }}" aria-controls="{{ $field['id'] }}" aria-pressed="false">
                        <svg data-eye-open viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg data-eye-closed viewBox="0 0 24 24" aria-hidden="true" hidden><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.1 3.9M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7a10 10 0 0 0 4-.8"/></svg>
                    </button>
                </span>
            </label>
            @if($index === count($passwordFields) - 1)</div>@endif
        @endforeach

        <div class="actions account-profile-actions">
            <a class="secondary" href="{{ $panelRoute('admin.dashboard') }}">Batal</a>
            <button class="primary" type="submit">Simpan perubahan</button>
        </div>
    </form>
@endsection
