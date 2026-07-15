@extends('layouts.plain')

@section('content')
<div class="container text-center mt-5">
  <h1 class="h4 mb-3">🙌 登録ありがとうございます！</h1>
  <p class="mb-4 text-muted">フードトラックマップに反映されました。トラックのページから出店情報を投稿できます。</p>
  <a href="{{ route('trucks.index') }}" class="btn btn-truck">トラック一覧へ</a>
</div>
@endsection
