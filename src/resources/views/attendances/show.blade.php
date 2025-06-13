@extends('layouts.app')

@section('title', '勤怠詳細')

@push('styles')
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
    $isApprovalPending = $attendance->correctionRequests()
                          ->where('status','pending')
                          ->exists();
    $user   = Auth::user();
    $breaks = $attendance->breaks;
  @endphp

  @if (! $isApprovalPending)
    <form action="{{ route('attendances.update', $attendance) }}"
          method="POST"
          class="detail-form"
          novalidate>
      @csrf

      <div class="detail-card">
        <table class="detail-table">
          {{-- 名前 --}}
          <tr>
            <th>名前</th>
            <td class="value-cell">{{ $user->name }}</td>
            <td></td>
          </tr>

          {{-- 日付 --}}
          <tr>
            <th>日付</th>
            <td class="value-cell">{{ $attendance->work_date->format('Y年') }}</td>
            <td class="value-cell">{{ $attendance->work_date->format('n月j日') }}</td>
          </tr>

          {{-- 出勤・退勤 --}}
          <tr>
            <th>出勤・退勤</th>
            <td class="value-cell input-cell">
              <input type="time"
                     name="clock_in"
                     value="{{ old('clock_in', optional($attendance->clock_in)->format('H:i')) }}"
                     class="time-input"
                     required>
              @error('clock_in')
                <div class="error-message">{{ $message }}</div>
              @enderror
            </td>
            <td class="value-cell input-cell">
              <span class="tilde">〜</span>
              <input type="time"
                     name="clock_out"
                     value="{{ old('clock_out', optional($attendance->clock_out)->format('H:i')) }}"
                     class="time-input"
                     required>
              @error('clock_out')
                <div class="error-message">{{ $message }}</div>
              @enderror
            </td>
          </tr>

          {{-- 休憩＋追加 --}}
          @foreach($breaks->concat([null]) as $i => $b)
          <tr>
            <th>{{ $i===0 ? '休憩' : '休憩'.($i+1) }}</th>
            <td class="value-cell input-cell">
              <input type="time"
                     name="breaks[{{ $i }}][break_start]"
                     value="{{ old("breaks.$i.break_start", optional($b)->break_start ? \Illuminate\Support\Carbon::parse($b->break_start)->format('H:i') : '') }}"
                     class="time-input">
              @error("breaks.$i.break_start")
                <div class="error-message">{{ $message }}</div>
              @enderror
            </td>
            <td class="value-cell input-cell">
              <span class="tilde">〜</span>
              <input type="time"
                     name="breaks[{{ $i }}][break_end]"
                     value="{{ old("breaks.$i.break_end", optional($b)->break_end ? \Illuminate\Support\Carbon::parse($b->break_end)->format('H:i') : '') }}"
                     class="time-input">
              @error("breaks.$i.break_end")
                <div class="error-message">{{ $message }}</div>
              @enderror
            </td>
          </tr>
          @endforeach

          {{-- 備考 --}}
          <tr>
            <th>備考</th>
            <td class="input-cell" colspan="2">
              <textarea name="comment"
                        class="comment-input"
                        placeholder="備考を入力">{{ old('comment') }}</textarea>
              @error('comment')
                <div class="error-message">{{ $message }}</div>
              @enderror
            </td>
          </tr>
        </table>
      </div>

      <div class="button-wrapper">
        <button type="submit" class="btn-submit">修正</button>
      </div>
    </form>

  @else
    {{-- 承認待ち（編集不可） --}}
    <div class="detail-card readonly">
      <table class="detail-table">
        <tr>
          <th>名前</th>
          <td class="value-cell" colspan="2">{{ $user->name }}</td>
        </tr>
        <tr>
          <th>日付</th>
          <td class="value-cell">{{ $attendance->work_date->format('Y年') }}</td>
          <td class="value-cell">{{ $attendance->work_date->format('n月j日') }}</td>
        </tr>
        <tr>
          <th>出勤・退勤</th>
          <td class="value-cell">
            {{ $attendance->clock_in
               ? \Illuminate\Support\Carbon::parse($attendance->clock_in)->format('H:i')
               : 'ー' }}
          </td>
          <td class="value-cell">
            <span class="tilde">〜</span>
            {{ $attendance->clock_out
               ? \Illuminate\Support\Carbon::parse($attendance->clock_out)->format('H:i')
               : 'ー' }}
          </td>
        </tr>
        @foreach($breaks as $i => $b)
        <tr>
          <th>{{ $i===0 ? '休憩' : '休憩'.($i+1) }}</th>
          <td class="value-cell">
            {{ \Illuminate\Support\Carbon::parse($b->break_start)->format('H:i') }}
          </td>
          <td class="value-cell">
            <span class="tilde">〜</span>
            {{ \Illuminate\Support\Carbon::parse($b->break_end)->format('H:i') }}
          </td>
        </tr>
        @endforeach
        <tr>
          <th>備考</th>
          <td colspan="2">{{ $attendance->remarks ?? 'ー' }}</td>
        </tr>
      </table>
    </div>
    <p class="pending-message">*承認待ちのため修正はできません。</p>
  @endif
</div>
@endsection
