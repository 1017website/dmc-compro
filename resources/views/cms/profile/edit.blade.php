@extends('cms.layouts.app')
@section('title','Ganti Password')
@section('content')
<div class="page-head"><div><h1>Keamanan akun</h1><p>Gunakan password unik yang tidak dipakai di layanan lain.</p></div></div>
<form class="card card-pad" style="max-width:640px" method="post" action="{{ route('cms.profile.password') }}">@csrf @method('PUT')<label class="field">Password saat ini<input class="input" type="password" name="current_password" autocomplete="current-password" required></label><label class="field">Password baru<input class="input" type="password" name="password" autocomplete="new-password" required><span class="hint">Minimal 10 karakter, berisi huruf besar, huruf kecil, dan angka.</span></label><label class="field">Konfirmasi password baru<input class="input" type="password" name="password_confirmation" autocomplete="new-password" required></label><button class="button button-primary">Ganti password</button></form>
@endsection
