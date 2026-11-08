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

        <form method="post" action="{{ $panelRoute('admin.users.store') }}" class="teacher-account-form">
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
                <span class="teacher-password-input">
                    <input id="teacherPassword" type="password" name="password" required minlength="8" maxlength="200" autocomplete="new-password" aria-describedby="teacherPasswordHint">
                    <button class="teacher-password-toggle" type="button" data-password-toggle aria-controls="teacherPassword" aria-label="Tampilkan kata sandi" aria-pressed="false">
                        <svg data-eye-open viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg data-eye-closed viewBox="0 0 24 24" aria-hidden="true" hidden><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.1 3.9M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7a10 10 0 0 0 4-.8"/></svg>
                    </button>
                </span>
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
        <div class="table-wrap teacher-account-table-wrap" role="region" aria-label="Daftar akun Guru">
            <table class="teacher-account-table">
                <thead>
                    <tr><th>Akun Guru</th><th>Status sesi</th><th>Perbarui akun</th></tr>
                </thead>
                <tbody>
                    @forelse ($users as $teacher)
                        <tr>
                            <td class="teacher-account-identity">
                                <strong>{{ $teacher->name }}</strong>
                                <span class="subline">{{ $teacher->email }}</span>
                                <span class="subline">{{ $teacher->username ? 'Username login: '.$teacher->username : 'Username belum diatur; login dengan email' }}</span>
                            </td>
                            <td>
                                @php($teacherSession = $activeLocks->get($teacher->id))
                                @if($teacherSession)
                                    <span class="pill rejected">Sedang Digunakan</span>
                                    <span class="subline">Aktivitas terakhir {{ \Illuminate\Support\Carbon::parse($teacherSession->last_seen_at)->diffForHumans() }}</span>
                                @else
                                    <span class="pill {{ $teacher->active ? 'published' : 'rejected' }}">{{ $teacher->active ? 'Tidak Aktif' : 'Nonaktif' }}</span>
                                @endif
                                @if($teacher->active && !$teacherSession)
                                    <form method="post" action="{{ route('admin.users.impersonate',$teacher) }}" class="teacher-account-session-action">@csrf<button class="tiny" type="submit">Masuk sebagai Guru</button></form>
                                @endif
                            </td>
                            <td>
                                <form method="post" action="{{ $panelRoute('admin.users.update', $teacher) }}" class="teacher-account-edit">
                                    @csrf
                                    @method('PUT')
                                    <label>Nama<input name="name" value="{{ $teacher->name }}" required minlength="2" maxlength="120" aria-label="Nama Guru"></label>
                                    <label>Email<input type="email" name="email" value="{{ $teacher->email }}" required maxlength="254" aria-label="Email Guru"></label>
                                    <label class="teacher-reset-password">Kata sandi baru<input type="password" name="password" minlength="8" maxlength="200" autocomplete="new-password" placeholder="Kosongkan jika tidak diubah" aria-label="Kata sandi baru (opsional)"></label>
                                    <label class="teacher-account-active"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" @checked($teacher->active)> Akun aktif</label>
                                    <button class="tiny" type="submit">Simpan</button>
                                </form>
                                <form method="post" action="{{ $panelRoute('admin.users.destroy', $teacher) }}" class="teacher-account-delete">
                                    @csrf
                                    @method('DELETE')
                                    <button class="tiny deny" type="button" data-confirm-action data-confirm-title="Yakin ingin menghapus akun ini?" data-confirm-description="Apakah Anda yakin ingin menghapus akun Guru ini? Data akun yang sudah dihapus tidak dapat digunakan kembali untuk login." data-confirm-label="Ya, Hapus Akun">Hapus Akun</button>
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
