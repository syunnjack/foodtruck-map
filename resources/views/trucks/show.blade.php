@extends('layouts.plain')

@section('title', $truck->name . ' の出店情報・口コミ | ' . config('app.name'))
@section('description', $truck->name . '（' . ($truck->cuisine_type ?? 'フードトラック') . '）の出店予定・利用者の口コミを確認できます。')

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'BreadcrumbList',
  'itemListElement' => [
      ['@type' => 'ListItem', 'position' => 1, 'name' => config('app.name'), 'item' => url('/')],
      ['@type' => 'ListItem', 'position' => 2, 'name' => $truck->name, 'item' => url("/trucks/{$truck->id}")],
  ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
<script type="application/ld+json">
{!! json_encode(array_filter([
  '@@context' => 'https://schema.org',
  '@type' => 'FoodEstablishment',
  'name' => $truck->name,
  'description' => $truck->description,
  'telephone' => $truck->phone,
  'sameAs' => $truck->sns_url,
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="container my-4">
  <div class="card shadow-sm">
    <div class="card-body p-4">
      <h1 class="h3 fw-bold mb-3">{{ $truck->name }}</h1>
      @if($truck->cuisine_type)
        <span class="badge bg-light text-dark border mb-2">{{ $truck->cuisine_type }}</span>
      @endif
      <p class="text-muted mb-2">{{ $truck->description }}</p>
      @if($truck->area)
        <p class="text-secondary small mb-1">主な活動エリア: {{ $truck->area }}</p>
      @endif
      @if($truck->phone)
        <p class="text-secondary small mb-1">電話: {{ $truck->phone }}</p>
      @endif
      @if($truck->sns_url)
        <p class="text-secondary small mb-4"><a href="{{ $truck->sns_url }}" target="_blank" rel="noopener noreferrer">SNSを見る</a></p>
      @endif

      <div class="mb-3">
        <a href="{{ route('appearances.index') }}" class="btn btn-secondary">出店情報一覧に戻る</a>
      </div>

      @if (session('success'))
        <div class="alert alert-success py-2 small">{{ session('success') }}</div>
      @endif
      @if ($errors->any())
        <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('trucks.favorite.toggle', $truck) }}" class="mb-3">
        @csrf
        @if ($isWatching)
          <button type="submit" class="btn btn-outline-secondary">🔕 通知をやめる</button>
        @else
          {{-- LINEの認証情報が未設定のうちは、押すとLINE側でエラーになるので出さない --}}
          @if (config('services.line.login_channel_id'))
          <button type="submit" class="btn btn-line">🔔 新しい出店情報が投稿されたらLINEで通知</button>
          @else
            <button type="button" class="btn btn-secondary" disabled>🔔 新しい出店情報が投稿されたらLINEで通知（準備中）</button>
          @endif
        @endif
      </form>

      <div class="d-flex align-items-center mt-4 mb-4">
        <button id="likeButton" data-truck-id="{{ $truck->id }}" class="btn btn-primary me-2">いいね！</button>
        <span id="likesCount" class="h4 fw-bold mb-0">{{ $truck->likes_count }}</span> <span class="text-muted ms-1">件のいいね！</span>
      </div>

      <h2 class="h5 mb-2">出店予定</h2>

      <h3 class="h6 mt-3 mb-2">出店情報を投稿する</h3>
      <p class="text-muted small">地図をタップして出店場所を選択してください。</p>
      <div id="map" style="height: 300px;" class="rounded shadow-sm border mb-3"></div>
      <form action="{{ route('trucks.appearances.store', $truck) }}" method="POST" class="bg-light p-3 rounded shadow-sm mb-4">
        @csrf
        <div style="position:absolute; left:-9999px;" aria-hidden="true">
          <label>ウェブサイト<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>
        <div class="mb-2">
          <label class="form-label small">エリア・目印 <span class="text-danger">*</span></label>
          <input type="text" name="area" class="form-control form-control-sm" placeholder="例：渋谷駅前、〇〇公園" required>
        </div>
        <div class="row">
          <div class="col-12 col-md-4 mb-2">
            <label class="form-label small">日付 <span class="text-danger">*</span></label>
            <input type="date" name="appearance_date" class="form-control form-control-sm" required>
          </div>
          <div class="col-6 col-md-4 mb-2">
            <label class="form-label small">開始時刻 <span class="text-danger">*</span></label>
            <input type="time" name="start_time" class="form-control form-control-sm" required>
          </div>
          <div class="col-6 col-md-4 mb-2">
            <label class="form-label small">終了時刻 <span class="text-danger">*</span></label>
            <input type="time" name="end_time" class="form-control form-control-sm" required>
          </div>
        </div>
        <div class="row mb-2">
          <div class="col-6">
            <label class="form-label small">緯度 <span class="text-danger">*</span></label>
            <input type="text" id="lat" name="lat" class="form-control form-control-sm" readonly required>
          </div>
          <div class="col-6">
            <label class="form-label small">経度 <span class="text-danger">*</span></label>
            <input type="text" id="lng" name="lng" class="form-control form-control-sm" readonly required>
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label small">ニックネーム（任意）</label>
          <input type="text" name="nickname" class="form-control form-control-sm" maxlength="30">
        </div>
        <div class="mb-2">
          <label class="form-label small">コメント（任意）</label>
          <textarea name="comment" class="form-control form-control-sm" rows="2" maxlength="1000" placeholder="例：本日11時から14時まで出店します！"></textarea>
        </div>
        <button type="submit" class="btn btn-dark">投稿する</button>
      </form>

      <div id="appearanceSlotList" class="mb-5">
        @forelse($truck->appearanceSlots as $slot)
          <div class="border rounded p-3 mb-2 bg-white">
            <div class="d-flex justify-content-between">
              <strong>{{ $slot->appearance_date->format('Y/m/d (D)') }} {{ substr($slot->start_time, 0, 5) }}〜{{ substr($slot->end_time, 0, 5) }}</strong>
              <span class="text-muted small">{{ $slot->area }}</span>
            </div>
            <div class="small text-muted">{{ $slot->nickname }}</div>
            @if($slot->comment)
              <p class="mb-0 mt-1">{{ $slot->comment }}</p>
            @endif
          </div>
        @empty
          <p class="text-muted">現在、投稿されている出店予定はありません。</p>
        @endforelse
      </div>

      <h3 class="h6 mt-4 mb-2">写真付き口コミを投稿する</h3>
      <form action="{{ route('trucks.reviews.store', $truck) }}" method="POST" enctype="multipart/form-data" class="bg-light p-3 rounded shadow-sm">
        @csrf
        <div style="position:absolute; left:-9999px;" aria-hidden="true">
          <label>ウェブサイト<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>
        <div class="mb-2">
          <label class="form-label small">ニックネーム（任意）</label>
          <input type="text" name="nickname" class="form-control form-control-sm" maxlength="30">
        </div>
        <div class="mb-2">
          <label class="form-label small">評価</label>
          <select name="rating" class="form-select form-select-sm" required>
            <option value="">選択してください</option>
            <option value="5">★★★★★</option>
            <option value="4">★★★★☆</option>
            <option value="3">★★★☆☆</option>
            <option value="2">★★☆☆☆</option>
            <option value="1">★☆☆☆☆</option>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label small">口コミ</label>
          <textarea name="comment" class="form-control form-control-sm" rows="3" minlength="5" maxlength="1000" required></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label small">料理の写真（任意）</label>
          <input type="file" name="photo" accept="image/*" class="form-control form-control-sm">
        </div>
        <button type="submit" class="btn btn-dark">投稿する</button>
      </form>

      <h3 class="h6 mt-5 mb-3">口コミ</h3>
      <div id="reviewList">
        @forelse($truck->reviews as $review)
          <div class="card mb-3 bg-light">
            @if($review->photo_path)
              <img src="{{ \Illuminate\Support\Facades\Storage::url($review->photo_path) }}" class="card-img-top" style="max-height:320px;object-fit:cover;" alt="{{ $truck->name }}の口コミ写真">
            @endif
            <div class="card-body">
              <div>{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }} <strong>{{ $review->nickname }}</strong></div>
              <p class="mb-1">{{ $review->comment }}</p>
              <small class="text-muted">投稿日: {{ $review->created_at->format('Y/m/d H:i') }}</small>
            </div>
          </div>
        @empty
          <p class="text-muted">まだ口コミはありません。</p>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const map = L.map('map').setView([35.6812, 139.7671], 8);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    let marker;
    map.on('click', function (e) {
      const lat = e.latlng.lat.toFixed(7);
      const lng = e.latlng.lng.toFixed(7);
      document.getElementById('lat').value = lat;
      document.getElementById('lng').value = lng;

      if (marker) {
        marker.setLatLng(e.latlng);
      } else {
        marker = L.marker(e.latlng).addTo(map);
      }
    });

    const likeButton = document.getElementById('likeButton');
    const likesCountSpan = document.getElementById('likesCount');
    if (likeButton) {
      likeButton.addEventListener('click', async function() {
        const truckId = likeButton.dataset.truckId;
        try {
          const response = await fetch(`/trucks/${truckId}/like`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
          });
          if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'いいね！に失敗しました。');
          }
          const data = await response.json();
          likesCountSpan.textContent = data.likes_count;
        } catch (error) {
          alert('エラー: ' + error.message);
        }
      });
    }
  });
</script>
@endsection
