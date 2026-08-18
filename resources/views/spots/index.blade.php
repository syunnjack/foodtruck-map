@extends('layouts.plain')

@section('title', 'キッチンカーが出店する場所' . $spots->count() . 'か所 | ' . config('app.name'))
@section('description', '自治体が公認・実施しているキッチンカーの出店場所を' . $spots->count() . 'か所まとめています。公園や区有地など、キッチンカーが定期的に出る場所を住所と出典つきで確認できます。')

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'CollectionPage',
  'name' => 'キッチンカーが出店する場所',
  'url' => url('/spots'),
  'description' => '自治体が公認・実施しているキッチンカーの出店場所の一覧。',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'BreadcrumbList',
  'itemListElement' => [
      ['@type' => 'ListItem', 'position' => 1, 'name' => config('app.name'), 'item' => url('/')],
      ['@type' => 'ListItem', 'position' => 2, 'name' => '出店する場所', 'item' => url('/spots')],
  ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="container my-4">
  <div class="card shadow-sm mb-3">
    <div class="card-body p-4">
      <h1 class="h3 fw-bold mb-3">キッチンカーが出店する場所</h1>
      <p class="text-muted mb-2">
        自治体が公認・実施しているキッチンカーの出店場所を{{ $spots->count() }}か所まとめています。
        公園や区有地など、キッチンカーが定期的に出る場所です。住所と出典を添えているので、行き先を決める前に確認できます。
      </p>
      <p class="text-muted small mb-3">
        どの店がいつ来るかは、自治体が毎月更新しています。当サイトでは日々の出店予定を持たず、
        各場所の出典先（自治体の公式ページ）をご案内します。出かける前に最新の予定をご確認ください。
      </p>
      <a href="{{ route('appearances.index') }}" class="btn btn-outline-secondary btn-sm">地図から探す</a>
      <a href="{{ route('trucks.index') }}" class="btn btn-outline-secondary btn-sm">トラック一覧</a>
    </div>
  </div>

  @foreach($spotsByArea as $area => $areaSpots)
    <div class="card shadow-sm mb-3">
      <div class="card-body p-4">
        <h2 class="h5 mb-3">{{ $area }}（{{ $areaSpots->count() }}か所）</h2>
        <div class="row g-3">
          @foreach($areaSpots as $spot)
            <div class="col-12 col-md-6">
              <div class="border rounded p-3 h-100">
                <h3 class="h6 fw-bold mb-1">
                  <a href="{{ route('spots.show', $spot) }}" class="text-decoration-none">{{ $spot->name }}</a>
                </h3>
                <p class="small text-muted mb-1">{{ $spot->address }}</p>
                @if($spot->hours)
                  <p class="small mb-0">出店時間：{{ $spot->hours }}</p>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  @endforeach
</div>
@endsection
