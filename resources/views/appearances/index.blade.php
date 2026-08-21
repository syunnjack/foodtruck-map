@extends('layouts.plain')

@section('title', config('app.name') . ' | 今日どこにいるかがわかるフードトラック・キッチンカーマップ')
@section('description', '自治体や公園の管理者が公表しているキッチンカーの出店場所を、住所と出典つきで地図に載せています。現在地から近い場所を探せます。')

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'WebSite',
  'name' => config('app.name'),
  'url' => url('/'),
  'description' => '全国のフードトラック・キッチンカーの出店情報を投稿型マップで確認できるサイト。',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="container my-4">
  <div class="text-center mb-4">
    <h1 class="fw-bold h3">🚐 フードトラックマップ</h1>
    <p class="text-muted">現在地から近い出店をすぐ見つける・今日どこにいるかがわかる地図</p>
    <a href="{{ route('trucks.create') }}" class="btn btn-truck shadow-sm px-4">➕ フードトラックを登録</a>
    @if(Route::has('spots.index'))
      <a href="{{ route('spots.index') }}" class="btn btn-outline-secondary shadow-sm px-4">出店する場所を見る</a>
    @endif
  </div>

  <div class="d-flex justify-content-center mb-3">
    <button id="locateButton" class="btn btn-outline-primary">📍 現在地から近い出店を探す</button>
  </div>
  <p id="locateMessage" class="text-center text-muted small mb-3"></p>

  @php($spotList = ($spots ?? collect()))
  <div id="map"
       data-slots="{{ $slots->map(fn ($s) => ['id' => $s->id, 'truck_id' => $s->truck->id, 'truck_name' => $s->truck->name, 'area' => $s->area, 'lat' => $s->lat, 'lng' => $s->lng, 'date' => $s->appearance_date->format('n/j'), 'start' => substr($s->start_time, 0, 5), 'end' => substr($s->end_time, 0, 5)])->toJson() }}"
       data-spots="{{ $spotList->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'area' => $s->area, 'lat' => $s->lat, 'lng' => $s->lng])->toJson() }}"
       style="height: 360px;" class="rounded shadow-sm border mb-4"></div>

  <form method="GET" action="{{ route('appearances.index') }}" class="row g-2 mb-4">
    <div class="col-md-4">
      <label class="form-label">エリア</label>
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

  <h2 class="h5">出店予定一覧</h2>
  <div class="row" id="slotList">
    @forelse($slots as $slot)
      <div class="col-md-6 col-lg-4 mb-3" data-slot-card data-lat="{{ $slot->lat }}" data-lng="{{ $slot->lng }}">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h3 class="h6 card-title">
              <a href="{{ route('trucks.show', $slot->truck) }}" class="text-decoration-none">{{ $slot->truck->name }}</a>
              <span class="badge bg-secondary float-end">{{ $slot->area }}</span>
            </h3>
            <p class="card-text mb-1">
              <strong>{{ $slot->appearance_date->format('n/j (D)') }} {{ substr($slot->start_time, 0, 5) }}〜{{ substr($slot->end_time, 0, 5) }}</strong>
            </p>
            @if($slot->comment)
              <p class="card-text text-muted small">{{ $slot->comment }}</p>
            @endif
            <small class="text-muted d-block distance-label"></small>
          </div>
        </div>
      </div>
    @empty
      <p class="text-muted">
        利用者からの出店予定の投稿は、まだありません。
        下の「自治体が公表している出店場所」からお探しください。
      </p>
    @endforelse
  </div>

  <h2 class="h5 mt-4">自治体が公表している出店場所（{{ $spotList->count() }}か所）</h2>
  <p class="text-muted small">
    自治体や公園の管理者が、キッチンカーの出店を実施していると公表している場所です。
    どの店がいつ来るかは各自治体が毎月更新するため、お出かけ前に出典先でご確認ください。
  </p>

  <div class="row">
    @foreach($spotList->groupBy('area') as $areaName => $areaSpots)
      <div class="col-md-6 col-lg-4 mb-3">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h3 class="h6 card-title">{{ $areaName }}<span class="badge bg-secondary float-end">{{ $areaSpots->count() }}か所</span></h3>
            <ul class="list-unstyled mb-0 small">
              @foreach($areaSpots as $spot)
                <li><a href="{{ route('spots.show', $spot) }}" class="text-decoration-none">{{ $spot->name }}</a></li>
              @endforeach
            </ul>
          </div>
        </div>
      </div>
    @endforeach
  </div>
</div>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const mapEl = document.getElementById('map');
    const slots = JSON.parse(mapEl.dataset.slots || '[]');

    const map = L.map('map').setView([35.6812, 139.7671], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    slots.forEach(function (s) {
      L.marker([s.lat, s.lng]).addTo(map)
        .bindPopup('<a href="/trucks/' + s.truck_id + '">' + s.truck_name + '</a><br><small>' + s.area + ' ' + s.date + ' ' + s.start + '〜' + s.end + '</small>');
    });

    // 自治体が公表している出店場所。投稿と見分けがつくよう、丸い印にする。
    const spots = JSON.parse(mapEl.dataset.spots || '[]');
    spots.forEach(function (s) {
      L.circleMarker([s.lat, s.lng], {
        radius: 7, color: '#e0562b', weight: 2, fillColor: '#e0562b', fillOpacity: 0.65
      }).addTo(map)
        .bindPopup('<a href="/spots/' + s.id + '">' + s.name + '</a><br><small>' + s.area + '</small>');
    });

    if (slots.length === 0 && spots.length > 0) {
      map.fitBounds(spots.map(function (s) { return [s.lat, s.lng]; }), { padding: [24, 24] });
    }

    function haversineKm(lat1, lng1, lat2, lng2) {
      const R = 6371;
      const dLat = (lat2 - lat1) * Math.PI / 180;
      const dLng = (lng2 - lng1) * Math.PI / 180;
      const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
      return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    const locateButton = document.getElementById('locateButton');
    const locateMessage = document.getElementById('locateMessage');

    locateButton.addEventListener('click', function () {
      if (!navigator.geolocation) {
        locateMessage.textContent = 'このブラウザは現在地取得に対応していません。';
        return;
      }

      locateMessage.textContent = '現在地を取得しています…';

      navigator.geolocation.getCurrentPosition(function (position) {
        const userLat = position.coords.latitude;
        const userLng = position.coords.longitude;

        map.setView([userLat, userLng], 11);
        L.marker([userLat, userLng], { title: '現在地' })
          .addTo(map)
          .bindPopup('現在地')
          .openPopup();

        const cards = Array.from(document.querySelectorAll('[data-slot-card]'));
        cards.forEach(function (card) {
          const lat = parseFloat(card.dataset.lat);
          const lng = parseFloat(card.dataset.lng);
          const distance = haversineKm(userLat, userLng, lat, lng);
          card.dataset.distance = distance;
          const label = card.querySelector('.distance-label');
          if (label) label.textContent = '現在地から約' + distance.toFixed(1) + 'km';
        });

        cards.sort(function (a, b) {
          return parseFloat(a.dataset.distance) - parseFloat(b.dataset.distance);
        });

        const list = document.getElementById('slotList');
        cards.forEach(function (card) { list.appendChild(card); });

        locateMessage.textContent = '現在地から近い順に並び替えました。';
      }, function () {
        locateMessage.textContent = '現在地を取得できませんでした。ブラウザの位置情報許可をご確認ください。';
      });
    });
  });
</script>
@endsection
