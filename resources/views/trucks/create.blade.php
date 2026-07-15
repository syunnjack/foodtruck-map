@extends('layouts.plain')

@section('title', 'フードトラックを登録する - ' . config('app.name'))
@section('description', 'フードトラック・キッチンカーの屋号・料理ジャンルなどを登録できます。ログイン不要・匿名で登録可能です。')

@section('content')
<div class="container my-4" style="max-width: 640px;">
  <h1 class="h4 mb-3">➕ フードトラックを登録する</h1>
  <p class="text-muted small mb-3">
    ここでは屋号・料理ジャンルなど恒常的な情報を登録します。「今日どこで出店するか」は登録後、トラックのページから出店情報として投稿してください。
    ログイン不要で登録できます。
  </p>

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('trucks.store') }}" class="bg-light p-3 rounded shadow-sm">
    @csrf
    <div style="position:absolute; left:-9999px;" aria-hidden="true">
      <label>ウェブサイト<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
    </div>

    <div class="mb-3">
      <label class="form-label">屋号 <span class="text-danger">*</span></label>
      <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">料理ジャンル（任意）</label>
      <select name="cuisine_type" class="form-select">
        <option value="">選択してください</option>
        <option value="キッチンカー弁当">弁当</option>
        <option value="カレー">カレー</option>
        <option value="クレープ・スイーツ">クレープ・スイーツ</option>
        <option value="コーヒー・ドリンク">コーヒー・ドリンク</option>
        <option value="タコス・メキシカン">タコス・メキシカン</option>
        <option value="たこ焼き・粉もの">たこ焼き・粉もの</option>
        <option value="その他">その他</option>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">ひとことコメント</label>
      <textarea name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">主な活動エリア（任意）</label>
      <input type="text" name="area" value="{{ old('area') }}" class="form-control" placeholder="例：東京都、大阪府">
    </div>

    <div class="mb-3">
      <label class="form-label">SNS URL（任意）</label>
      <input type="url" name="sns_url" value="{{ old('sns_url') }}" class="form-control" placeholder="https://">
    </div>

    <div class="mb-3">
      <label class="form-label">電話番号（任意）</label>
      <input type="text" name="phone" value="{{ old('phone') }}" class="form-control">
    </div>

    <button type="submit" class="btn btn-truck w-100">登録する</button>
  </form>
</div>
@endsection
