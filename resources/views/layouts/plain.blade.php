<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#e0562b">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>@yield('title', config('app.name') . ' | 今日どこにいるかがわかるフードトラック・キッチンカーマップ')</title>
  <meta name="description" content="@yield('description', '全国のフードトラック・キッチンカーの出店情報を投稿型マップで確認できます。現在地から近い出店をすぐ見つけられ、お気に入りのトラックをフォローすると新しい出店情報がLINEで届きます。')">
  <link rel="canonical" href="{{ url()->current() }}">

  <meta property="og:site_name" content="{{ config('app.name') }}">
  <meta property="og:type" content="website">
  <meta property="og:title" content="@yield('title', config('app.name') . ' | 今日どこにいるかがわかるフードトラック・キッチンカーマップ')">
  <meta property="og:description" content="@yield('description', '全国のフードトラック・キッチンカーの出店情報を投稿型マップで確認できます。現在地から近い出店をすぐ見つけられ、お気に入りのトラックをフォローすると新しい出店情報がLINEで届きます。')">
  <meta property="og:url" content="{{ url()->current() }}">
  <meta property="og:locale" content="ja_JP">

  <meta name="twitter:card" content="summary">
  <meta name="twitter:title" content="@yield('title', config('app.name') . ' | 今日どこにいるかがわかるフードトラック・キッチンカーマップ')">
  <meta name="twitter:description" content="@yield('description', '全国のフードトラック・キッチンカーの出店情報を投稿型マップで確認できます。現在地から近い出店をすぐ見つけられ、お気に入りのトラックをフォローすると新しい出店情報がLINEで届きます。')">

  <link rel="icon" href="/favicon.ico" sizes="any">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body { background-color: #fdf5f1; font-family: system-ui, -apple-system, sans-serif; }
    .btn { min-height: 44px; }
    .btn-line { background: #06c755; color: #fff; border: none; }
    .btn-line:hover { background: #05a848; color: #fff; }
    .btn-truck { background: #e0562b; color: #fff; border: none; }
    .btn-truck:hover { background: #c2431e; color: #fff; }
  </style>
  @yield('styles')

  @stack('structured-data')
  @if(config('services.ga4.id'))
  <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.ga4.id') }}"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '{{ config('services.ga4.id') }}');
  </script>
  @endif
</head>
<body>
  <nav class="navbar navbar-dark p-2" style="background-color:#a3391a;">
    <div class="container-fluid">
      <a href="{{ route('appearances.index') }}" class="navbar-brand text-white text-decoration-none">🚐 {{ config('app.name') }}</a>
      <div>
        <a href="{{ route('trucks.index') }}" class="text-white text-decoration-none small me-3">トラック一覧</a>
        <a href="{{ route('about') }}" class="text-white text-decoration-none small">サイトについて</a>
      </div>
    </div>
  </nav>

  @yield('content')

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  @yield('scripts')
</body>
</html>
