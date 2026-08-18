@extends('layouts.plain')

@section('title', $spot->name . 'のキッチンカー出店情報 | ' . config('app.name'))
@section('description', $spot->name . '（' . $spot->area . '）でのキッチンカー出店について、場所・住所' . ($spot->hours ? '・出店時間' : '') . 'を自治体の公式情報をもとにまとめています。')

@push('structured-data')
<script type="application/ld+json">
{!! json_encode(array_filter([
  '@@context' => 'https://schema.org',
  '@type' => 'Place',
  'name' => $spot->name,
  'address' => $spot->address,
  'geo' => [
      '@type' => 'GeoCoordinates',
      'latitude' => $spot->lat,
      'longitude' => $spot->lng,
  ],
  'url' => url("/spots/{$spot->id}"),
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'BreadcrumbList',
  'itemListElement' => [
      ['@type' => 'ListItem', 'position' => 1, 'name' => config('app.name'), 'item' => url('/')],
      ['@type' => 'ListItem', 'position' => 2, 'name' => '出店する場所', 'item' => url('/spots')],
      ['@type' => 'ListItem', 'position' => 3, 'name' => $spot->name, 'item' => url("/spots/{$spot->id}")],
  ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="container my-4">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h1 class="h3 fw-bold mb-2">{{ $spot->name }}</h1>
      <p class="text-secondary small mb-3">{{ $spot->area }}</p>

      <dl class="row mb-3">
        <dt class="col-4 col-sm-3 text-muted small">住所</dt>
        <dd class="col-8 col-sm-9">{{ $spot->address }}</dd>
        @if($spot->hours)
          <dt class="col-4 col-sm-3 text-muted small">出店時間</dt>
          <dd class="col-8 col-sm-9">{{ $spot->hours }}</dd>
        @endif
      </dl>

      @if($spot->note)
        <p>{{ $spot->note }}</p>
      @endif

      <p class="text-muted small">
        出典：<a href="{{ $spot->source_url }}" target="_blank" rel="nofollow noopener noreferrer">{{ $spot->source_label }}</a>
        <br>
        どの店がいつ来るかは自治体が毎月更新しています。出かける前に出典先で最新の出店予定をご確認ください。
      </p>

      <div class="my-3">
        <a href="https://www.google.com/maps/search/?api=1&query={{ $spot->lat }},{{ $spot->lng }}"
           target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">地図で場所を見る</a>
        <a href="{{ route('spots.index') }}" class="btn btn-outline-secondary btn-sm">出店場所の一覧へ</a>
        <a href="{{ route('appearances.index') }}" class="btn btn-secondary btn-sm">トップページに戻る</a>
      </div>

      @if($sameArea->isNotEmpty())
        <div class="mt-4 pt-3 border-top">
          <h2 class="h5 mb-3">{{ $spot->area }}のほかの出店場所</h2>
          <div class="d-flex flex-wrap gap-2">
            @foreach($sameArea as $other)
              <a href="{{ route('spots.show', $other) }}" class="btn btn-outline-secondary btn-sm">{{ $other->name }}</a>
            @endforeach
          </div>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
