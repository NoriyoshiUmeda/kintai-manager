@extends('admin.layouts.app')

@section('title', 'スタッフ一覧')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/admin/users/index.css') }}">
@endpush

@section('content')
<div class="container">
  {{-- タイトル部分 --}}
  <div class="detail-title">
    <span class="detail-title-line"></span>
    <h2 class="detail-title-text">スタッフ一覧</h2>
  </div>

  {{-- カード内テーブル --}}
  <div class="detail-card">
    <table class="detail-table">
      <thead>
        <tr>
          <th>名前</th>
          <th>メールアドレス</th>
          <th>月次勤怠</th>
        </tr>
      </thead>
      <tbody>
        @foreach($users as $user)
          <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>
              <a href="{{ route('admin.users.attendances.index', $user->id) }}" class="link-button">
                詳細
              </a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  {{-- ページネーション --}}
  <div class="mt-4">
    {{ $users->links() }}
  </div>
</div>
@endsection
