@extends('cms.layouts.app')
@section('title','Dashboard')
@section('content')
<div class="page-head"><div><h1>Ringkasan website</h1><p>Pantau pengunjung dan permintaan terbaru tanpa laporan yang rumit.</p></div><a class="button button-primary" href="{{ route('cms.content.index') }}">Edit halaman depan</a></div>
<div class="grid stats">
 <div class="card stat"><span>Tampilan hari ini</span><strong>{{ number_format($viewsToday) }}</strong></div>
 <div class="card stat"><span>Pengunjung unik</span><strong>{{ number_format($uniqueToday) }}</strong></div>
 <div class="card stat"><span>Tampilan bulan ini</span><strong>{{ number_format($viewsMonth) }}</strong></div>
 <div class="card stat"><span>Permintaan baru</span><strong>{{ number_format($newInquiries) }}</strong></div>
</div>
<div class="grid two-col">
 <section class="card card-pad"><div class="page-head" style="margin-bottom:0"><div><h1 style="font-size:19px">Aktivitas 7 hari</h1><p>Jumlah halaman yang dilihat.</p></div></div><div class="chart">@foreach($days as $day)<div class="bar-col"><strong>{{ $day['views'] }}</strong><div class="bar" style="height:{{ max(3,($day['views']/$maxViews)*150) }}px"></div><span>{{ $day['label'] }}</span></div>@endforeach</div></section>
 <section class="card"><div class="card-pad" style="border-bottom:1px solid var(--line)"><strong>Permintaan terbaru</strong></div>@forelse($recentInquiries as $inquiry)<a href="{{ route('cms.inquiries.show',$inquiry) }}" style="display:block;padding:14px 20px;border-bottom:1px solid var(--line)"><strong>{{ $inquiry->name }}</strong><br><span style="color:var(--muted)">{{ $inquiry->need }} · {{ $inquiry->created_at->diffForHumans() }}</span></a>@empty<div class="empty">Belum ada permintaan.</div>@endforelse</section>
</div>
@endsection
