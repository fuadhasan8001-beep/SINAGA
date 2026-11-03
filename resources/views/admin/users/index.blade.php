@extends('layouts.admin')

@section('title', 'Akun Guru')
@section('heading', 'Akun Guru')
@section('subtitle', 'Buat, ubah, aktifkan, atau nonaktifkan akun Guru.')

@section('content')
    <section class="panel teacher-account-create" aria-labelledby="createTeacherHeading">
        <div class="teacher-account-create__header">
            <span class="teacher-account-create__icon" aria-hidden="true">♙</span>
            <div>
                <h2 id="createTeacherHeading">Buat Akun Guru</h2>
                <p>Buat akun baru untuk mengakses panel SINAGA.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="teacher-account-errors" role="alert">
                <strong>Akun belum tersimpan. Periksa kembali:</strong>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="post" action="{{ route('admin.users.store') }}" class="teacher-account-form">
            @csrf
            <label for="teacherName">
                Nama
                <input id="teacherName" name="name" value="{{ old('name') }}" required minlength="2" maxlength="120" autocomplete="name">
                @error('name')<span class="teacher-field-error">{{ $message }}</span>@enderror
            </label>
            <label for="teacherEmail">
                Email
                <input id="teacherEmail" type="email" name="email" value="{{ old('email') }}" required maxlength="254" autocomplete="email">
                @error('email')<span class="teacher-field-error">{{ $message }}</span>@enderror
            </label>
            <label class="teacher-password-field" for="teacherPassword">
                Kata Sandi Awal
                <input id="teacherPassword" type="password" name="password" required minlength="8" maxlength="200" autocomplete="new-password" aria-describedby="teacherPasswordHint">
                <span class="hint" id="teacherPasswordHint">Minimal 8 karakter. Kata sandi hanya ditampilkan kepada Admin saat dibuat.</span>
                @error('password')<span class="teacher-field-error">{{ $message }}</span>@enderror
            </label>
            <button class="primary teacher-create-submit" type="submit">Buat Akun Guru</button>
        </form>
    </section>

    <section class="panel teacher-account-list" aria-labelledby="teacherListHeading">
        <div class="teacher-account-list__header">
            <div>
                <h2 id="teacherListHeading">Daftar Akun Guru</h2>
                <p>{{ $users->total() }} akun terdaftar</p>
            </div>
        </div>
        <div class="table-wrap teacher-account-table-wrap" role="region" aria-label="Daftar akun Guru; geser tabel ke samping jika diperlukan" tabindex="0">
            <table class="teacher-account-table">
                <thead>
                    <tr><th>Akun Guru</th><th>Status</th><th>Perbarui akun</th></tr>
                </thead>
                <tbody>
                    @forelse ($users as $teacher)
                        <tr>
                            <td class="teacher-account-identity">
                                <strong>{{ $teacher->name }}</strong>
                                <span class="subline">{{ $teacher->email }}</span>
                            </td>
                            <td><span class="pill {{ $teacher->active ? 'published' : 'rejected' }}">{{ $teacher->active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td>
                                <form method="post" action="{{ route('admin.users.update', $teacher) }}" class="teacher-account-edit">
                                    @csrf
                                    @method('PUT')
                                    <label>Nama<input name="name" value="{{ $teacher->name }}" required minlength="2" maxlength="120" aria-label="Nama Guru"></label>
                                    <label>Email<input type="email" name="email" value="{{ $teacher->email }}" required maxlength="254" aria-label="Email Guru"></label>
                                    <label class="teacher-reset-password">Kata sandi baru<input type="password" name="password" minlength="8" maxlength="200" autocomplete="new-password" placeholder="Kosongkan jika tidak diubah" aria-label="Kata sandi baru (opsional)"></label>
                                    <label class="teacher-account-active"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" @checked($teacher->active)> Akun aktif</label>
                                    <button class="tiny" type="submit">Simpan</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty">Belum ada akun Guru.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{ $users->links() }}
    <p class="hint teacher-account-security-note">Admin tidak dapat melihat kata sandi pengguna. Isi kata sandi baru hanya untuk meresetnya.</p>
@endsection
