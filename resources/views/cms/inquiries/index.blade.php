@extends('cms.layouts.app')
@section('title','Permintaan Masuk')
@section('content')
<div class="page-head"><div><h1>Permintaan pelanggan</h1><p>Semua formulir yang dikirim dari website tersimpan di sini.</p></div></div>
<div class="card table-wrap"><table class="table"><thead><tr><th>Tanggal</th><th>Nama / Perusahaan</th><th>Kebutuhan</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($inquiries as $inquiry)<tr><td>{{ $inquiry->created_at->format('d M Y, H:i') }}</td><td><strong>{{ $inquiry->name }}</strong><br><span style="color:var(--muted)">{{ $inquiry->email }}</span></td><td>{{ $inquiry->need }}</td><td><span class="status status-{{ $inquiry->status }}">{{ ['new'=>'Baru','contacted'=>'Dihubungi','closed'=>'Selesai'][$inquiry->status] }}</span></td><td><a class="button button-small" href="{{ route('cms.inquiries.show',$inquiry) }}">Buka</a></td></tr>@empty<tr><td colspan="5" class="empty">Belum ada permintaan masuk.</td></tr>@endforelse
</tbody></table></div><div class="pagination">{{ $inquiries->links() }}</div>
@endsection
