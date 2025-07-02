@extends('admin.layouts.app')

@section('title', '申請一覧')

@push('styles')
  {{-- 一般ユーザー向けと同じCSSを流用 --}}
  <link rel="stylesheet" href="{{ asset('css/corrections/index.css') }}">
@endpush

@section('content')
<div class="container">
  {{-- タイトル --}}
  <div class="detail-title">
    <span class="detail-title-line"></span>
    <h2 class="detail-title-text">申請一覧</h2>
  </div>

  {{-- タブ切り替え --}}
  <ul class="tabs">
    <li class="tab active" data-target="pending">承認待ち</li>
    <li class="tab"        data-target="approved">承認済み</li>
  </ul>
  <div class="tabs-underline"></div>

  <div class="tab-content">
    {{-- 承認待ちテーブル --}}
    <div id="pending" class="tab-pane active">
      <div class="table-wrapper">
        <table class="list-table">
          <thead>
            <tr>
              <th>状態</th>
              <th>名前</th>
              <th>対象日時</th>
              <th>申請理由</th>
              <th>申請日時</th>
              <th>詳細</th>
            </tr>
          </thead>
          <tbody>
            @foreach($pending as $req)
            <tr>
              <td>承認待ち</td>
              <td>{{ $req->attendance->user->name }}</td>
              <td>{{ $req->attendance->work_date->format('Y/m/d') }}</td>
              <td class="reason-cell" title="{{ $req->comment }}">
                {{ $req->comment }}
              </td>
              <td>{{ $req->created_at->format('Y/m/d') }}</td>
              <td>
                <a href="{{ route('admin.corrections.show', $req->id) }}">
                  詳細
                </a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    {{-- 承認済みテーブル --}}
    <div id="approved" class="tab-pane">
      <div class="table-wrapper">
        <table class="list-table">
          <thead>
            <tr>
              <th>状態</th>
              <th>ユーザー</th>
              <th>対象日時</th>
              <th>申請理由</th>
              <th>申請日時</th>
              <th>詳細</th>
            </tr>
          </thead>
          <tbody>
            @foreach($approved as $req)
            <tr>
              <td>承認済み</td>
              <td>{{ $req->attendance->user->name }}</td>
              <td>{{ $req->attendance->work_date->format('Y/m/d') }}</td>
              <td class="reason-cell" title="{{ $req->comment }}">
                {{ $req->comment }}
              </td>
              <td>{{ $req->created_at->format('Y/m/d') }}</td>
              <td>
                {{-- 承認済みは詳細のみ表示 --}}
                <a href="{{ route('admin.corrections.show', $req->id) }}">
                  詳細
                </a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // タブ切り替えスクリプト（一般ユーザー画面と同じ）
  const tabs = document.querySelectorAll('.tabs .tab');
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
      tab.classList.add('active');
      document.getElementById(tab.dataset.target).classList.add('active');
    });
  });
</script>
@endpush
