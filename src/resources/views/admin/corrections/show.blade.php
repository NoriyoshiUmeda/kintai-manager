{{-- resources/views/admin/corrections/show.blade.php --}}
@extends('admin.layouts.app')

@section('title', '修正申請承認')

@push('styles')
  {{-- 一般ユーザー詳細画面と同じCSSを流用 --}}
  <link rel="stylesheet" href="{{ asset('css/attendances/show.css') }}">
@endpush

@section('content')
<div class="container">
  {{-- タイトル --}}
  <div class="detail-title">
    <span class="detail-title-line"></span>
    <h2 class="detail-title-text">勤怠詳細</h2>
  </div>

  @php

    $corr       = $correction;
    $attendance = $attendance;
    $user       = $user;

    $breaks     = $corr->requested_breaks;
  @endphp

  <div class="detail-card readonly">
    <table class="detail-table">
      {{-- 名前 --}}
      <tr>
        <th>名前</th>
        <td class="value-cell" colspan="2">{{ $user->name }}</td>
      </tr>

      {{-- 日付 --}}
      <tr>
        <th>日付</th>
        <td class="value-cell">
          <span class="work-year">{{ $attendance->work_date->format('Y年') }}</span>
          <span class="work-date">{{ $attendance->work_date->format('n月j日') }}</span>
        </td>
        <td></td>
      </tr>

      {{-- 申請出勤・退勤 --}}
      <tr>
        <th>出勤・退勤</th>
        <td class="value-cell">
          {{ \Illuminate\Support\Carbon::parse($corr->requested_in)->format('H:i') }}
          <span class="tilde">〜</span>
          {{ \Illuminate\Support\Carbon::parse($corr->requested_out)->format('H:i') }}
        </td>
        <td></td>
      </tr>

      {{-- 申請休憩：開始・終了両方あるものだけ表示 --}}
      @foreach($breaks as $i => $b)
        @if(! empty($b['break_start']) && ! empty($b['break_end']))
          <tr>
            <th>{{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}</th>
            <td class="value-cell">
              {{ \Illuminate\Support\Carbon::parse($b['break_start'])->format('H:i') }}
              <span class="tilde">〜</span>
              {{ \Illuminate\Support\Carbon::parse($b['break_end'])->format('H:i') }}
            </td>
            <td></td>
          </tr>
        @endif
      @endforeach

      {{-- 備考 --}}
      <tr>
        <th>備考</th>
        <td class="remark-cell" colspan="2">{{ $corr->comment }}</td>
      </tr>
    </table>
  </div>

  {{-- 承認ボタン --}}
  <div class="button-wrapper">
    @if($corr->status === \App\Models\CorrectionRequest::STATUS_PENDING)
      <form action="{{ route('admin.corrections.approve', $corr) }}" method="POST">
        @csrf
        @method('PATCH')
        <button type="submit" class="btn-submit">承認</button>
      </form>
    @else
      <button type="button" class="btn-submit" disabled>承認済み</button>
    @endif
  </div>
</div>
@endsection
