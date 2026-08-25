@extends('cms.layouts.app')

@section('title', $user->exists ? 'Edit User' : 'Tambah User')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('cms.users.index') }}" style="color:var(--muted)">← Daftar user</a>
            <h1 style="margin-top:8px">{{ $user->exists ? 'Edit user' : 'Tambah user baru' }}</h1>
            <p>Password minimal 10 karakter.</p>
        </div>
    </div>

    <form class="card card-pad user-form" method="post" action="{{ $user->exists ? route('cms.users.update', $user) : route('cms.users.store') }}">
        @csrf
        @if($user->exists)
            @method('PUT')
        @endif

        <label class="field">
            Nama
            <input class="input @error('name') is-invalid @enderror" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name">
            @error('name')<span class="field-error">{{ $message }}</span>@enderror
        </label>

        <label class="field">
            Email
            <input class="input @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
            @error('email')<span class="field-error">{{ $message }}</span>@enderror
        </label>

        <label class="field">
            Role
            <select class="input @error('role') is-invalid @enderror" name="role" required>
                @if(auth()->user()->role === 'developer' && $user->role === 'developer')
                    <option value="developer">Developer</option>
                @endif
                <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin — seluruh konten & user</option>
                <option value="editor" @selected(old('role', $user->role) === 'editor')>Editor — konten, inquiry & pengaturan</option>
            </select>
            @error('role')<span class="field-error">{{ $message }}</span>@enderror
        </label>

        @if($user->exists)
            <label class="check">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))>
                User aktif dan boleh login
            </label>
        @endif

        <div class="password-grid">
            <label class="field">
                {{ $user->exists ? 'Password baru (opsional)' : 'Password' }}
                <input class="input @error('password') is-invalid @enderror" type="password" name="password" minlength="10" autocomplete="new-password" {{ $user->exists ? '' : 'required' }}>
                <small class="field-hint">Gunakan minimal 10 karakter.</small>
                @error('password')<span class="field-error">{{ $message }}</span>@enderror
            </label>

            <label class="field">
                Konfirmasi password
                <input class="input @error('password_confirmation') is-invalid @enderror" type="password" name="password_confirmation" minlength="10" autocomplete="new-password" {{ $user->exists ? '' : 'required' }}>
                @error('password_confirmation')<span class="field-error">{{ $message }}</span>@enderror
            </label>
        </div>

        <button class="button button-primary">{{ $user->exists ? 'Simpan perubahan' : 'Buat user' }}</button>
    </form>

    <style>
        .user-form { max-width: 680px; }
        .password-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .input.is-invalid { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,.08); }
        .field-error { color: #dc2626; font-size: 12px; line-height: 1.45; }
        .field-hint { color: var(--muted); font-size: 12px; font-weight: 500; }
        @media (max-width: 700px) { .password-grid { grid-template-columns: 1fr; } }
    </style>
@endsection
