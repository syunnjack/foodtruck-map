@extends('layouts.plain')

@section('title', 'このサイトについて | ' . config('app.name'))
@section('description', config('app.name') . 'の運営方針、データの取り扱い、LINE通知の仕組みについて説明しています。')

@section('content')
<div class="container my-4" style="max-width: 720px;">
  <h1 class="h4 fw-bold mb-4">このサイトについて</h1>

  <section class="mb-4">
    <h2 class="h6">サイトの目的</h2>
    <p class="text-muted small">
      「{{ config('app.name') }}」は、フードトラック・キッチンカーの出店情報を投稿型マップで確認できるサイトです。
      フードトラックは固定の店舗を持たないため、出店者の方が「いつ・どこに出店するか」をその都度投稿する仕組みにしています。
      大手グルメサイトでは拾いきれない「今日どこにいるか」がリアルタイムに近い形で分かることが特徴です。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h6">トラック登録と出店情報について</h2>
    <p class="text-muted small">
      屋号・料理ジャンルなどの恒常的な情報は「トラック」として一度登録し、日々の出店場所・日時は「出店情報」として個別に投稿します。
      掲載しているすべての情報は投稿者からの申告であり、運営による事実確認は行っておりません。実際に出店しているかは現地でご確認ください。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h6">LINE通知について</h2>
    <p class="text-muted small">
      各トラックのページから「🔔 新しい出店情報が投稿されたらLINEで通知」を選ぶと、LINEログインのうえそのトラックを通知対象として登録できます。
      お気に入りのトラックが新しい出店情報を投稿すると、LINE公式アカウントからお知らせします。
    </p>
  </section>

  <section class="mb-4">
    <h2 class="h6">口コミ・投稿について</h2>
    <p class="text-muted small">
      口コミ（写真を含む）・出店情報・新規トラックの登録は、どなたでもログイン不要で行えます。投稿内容は運営による事前確認を行わず即時反映されますが、
      不適切な投稿を発見した場合は内容を精査のうえ削除などの対応を行います。
    </p>
  </section>

  <a href="{{ route('appearances.index') }}" class="d-block text-center text-muted mt-4">トップページに戻る</a>
</div>
@endsection
