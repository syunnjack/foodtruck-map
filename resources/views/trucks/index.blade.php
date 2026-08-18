@extends('layouts.plain')

@section('title', 'トラック一覧 | ' . config('app.name'))
@section('description', '登録されているフードトラック・キッチンカーの一覧です。')

@push('structured-data')
{{-- 投稿が0件のときは itemListElement が空になる。空のItemListはGoogleに
     無効な項目として扱われるため、1件以上あるときだけ出力する。 --}}
@if ($trucks->isNotEmpty())
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'ItemList',
  'itemListElement' => $trucks->take(50)->values()->map(function ($truck, $i) {
      return [
          '@type' => 'ListItem',
          'position' => $i + 1,
          'url' => url("/trucks/{$truck->id}"),
          'name' => $truck->name,
      ];
  })->all(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endif
@endpush

@section('content')
<div class="container my-4">
  <div class="text-center mb-4">
    <h1 class="fw-bold h3">トラック一覧</h1>
    <a href="{{ route('trucks.create') }}" class="btn btn-truck shadow-sm px-4">➕ フードトラックを登録</a>
    <a href="{{ route('spots.index') }}" class="btn btn-outline-secondary shadow-sm px-4">出店する場所を見る</a>
  </div>

  <form method="GET" action="{{ route('trucks.index') }}" class="row g-2 mb-4">
    <div class="col-md-4">
      <label class="form-label">主な活動エリア</label>
      <select name="area" class="form-select">
        <option value="">すべて</option>
        @foreach($areas as $area)
          <option value="{{ $area }}" @selected(request('area') == $area)>{{ $area }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2 align-self-end">
      <button type="submit" class="btn btn-outline-primary w-100">絞り込む</button>
    </div>
  </form>

  <div class="row">
    @forelse($trucks as $truck)
      <div class="col-md-6 col-lg-4 mb-3">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h2 class="h6 card-title">
              <a href="{{ route('trucks.show', $truck) }}" class="text-decoration-none">{{ $truck->name }}</a>
            </h2>
            @if($truck->cuisine_type)
              <span class="badge bg-light text-dark border mb-1">{{ $truck->cuisine_type }}</span>
            @endif
            <p class="card-text text-muted small">{{ $truck->description }}</p>
            <small class="text-muted">{{ $truck->area }}</small>
          </div>
        </div>
      </div>
    @empty
      <p class="text-muted mb-2">まだトラックが登録されていません。</p>
      <p class="text-muted small mb-0">
        キッチンカーが出店する場所は
        <a href="{{ route('spots.index') }}">出店する場所の一覧</a>
        で確認できます。お気に入りのトラックがあれば、
        <a href="{{ route('trucks.create') }}">登録</a>して出店情報を共有できます。
      </p>
    @endforelse
  </div>
</div>
@endsection
