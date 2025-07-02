@extends('layouts.app')

@section('title', '勤怠詳細')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/attendances/show.css') }}">
@endpush

@section('content')
<div class="container">
  <div class="detail-title">
    <span class="detail-title-line"></span>
    <h2 class="detail-title-text">勤怠詳細</h2>
  </div>
  @php
    // 承認待ち申請があるかどうか
    $isApprovalPending = (bool) $pendingRequest;
    $user = Auth::user();

    // 出退勤の表示値（申請中は requested_in/out、未申請は attendance の値）
    $displayIn  = $isApprovalPending
                  ? $pendingRequest->requested_in
                  : $attendance->clock_in;
    $displayOut = $isApprovalPending
                  ? $pendingRequest->requested_out
                  : $attendance->clock_out;
  @endphp

  @if (! $isApprovalPending)
    {{-- 編集フォーム --}}
    <form action="{{ route('attendances.update', $attendance) }}" method="POST" class="detail-form" novalidate>
      @csrf

      <div class="detail-card">
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

          {{-- 出勤・退勤 --}}
          <tr>
            <th>出勤・退勤</th>
            <td class="value-cell input-cell">
              <div class="input-cell-inner">
                <input
                  type="time"
                  name="clock_in"
                  value="{{ old('clock_in', optional($displayIn ? \Illuminate\Support\Carbon::parse($displayIn) : null)->format('H:i')) }}"
                  class="time-input"
                  required
                >
                <span class="tilde">〜</span>
                <input
                  type="time"
                  name="clock_out"
                  value="{{ old('clock_out', optional($displayOut ? \Illuminate\Support\Carbon::parse($displayOut) : null)->format('H:i')) }}"
                  class="time-input"
                  required
                >
              </div>
              @php
                $msgs = collect([
                  $errors->first('clock_in'),
                  $errors->first('clock_out'),
                ])->unique()->filter();
              @endphp
              @if ($msgs->isNotEmpty())
                <div class="error-message">{{ $msgs->first() }}</div>
              @endif
            </td>
            <td></td>
          </tr>

          {{-- 休憩入力：既存件数＋空行１つ --}}
          @foreach($breaks->concat([['break_start'=>'','break_end'=>'']]) as $i => $b)
          <tr>
            <th>{{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}</th>
            <td class="value-cell input-cell">
              <div class="input-cell-inner">
                <input
                  type="time"
                  name="breaks[{{ $i }}][break_start]"
                  value="{{ old("breaks.$i.break_start", optional($b['break_start'] ? \Illuminate\Support\Carbon::parse($b['break_start']) : null)->format('H:i')) }}"
                  class="time-input"
                >
                <span class="tilde">〜</span>
                <input
                  type="time"
                  name="breaks[{{ $i }}][break_end]"
                  value="{{ old("breaks.$i.break_end", optional($b['break_end'] ? \Illuminate\Support\Carbon::parse($b['break_end']) : null)->format('H:i')) }}"
                  class="time-input"
                >
              </div>
              @php
                $errs = collect([
                  $errors->first("breaks.$i.break_start"),
                  $errors->first("breaks.$i.break_end"),
                ])->unique()->filter();
              @endphp
              @if ($errs->isNotEmpty())
                <div class="error-message">{{ $errs->first() }}</div>
              @endif
            </td>
            <td></td>
          </tr>
          @endforeach

          {{-- 備考 --}}
          <tr>
            <th>備考</th>
            <td class="input-cell" colspan="2">
              <textarea
                name="comment"
                class="comment-input"
                placeholder="備考を入力"
              >{{ old('comment', $attendance->comment) }}</textarea>
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
    {{-- 読み取り専用表示（申請中は attendance ではなく requested_* を使う） --}}
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

        {{-- 出勤・退勤（requested があればそちらを H:i で表示） --}}
        <tr>
          <th>出勤・退勤</th>
          <td class="value-cell">
            {{ $displayIn  ? \Illuminate\Support\Carbon::parse($displayIn)->format('H:i')  : 'ー' }}
            <span class="tilde">〜</span>
            {{ $displayOut ? \Illuminate\Support\Carbon::parse($displayOut)->format('H:i') : 'ー' }}
          </td>
          <td></td>
        </tr>

        {{-- 休憩表示：空の休憩は表示しない --}}
        @foreach($breaks as $i => $b)
          @if($b['break_start'] || $b['break_end'])
          <tr>
            <th>{{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}</th>
            <td class="value-cell">
              {{ $b['break_start'] ? \Illuminate\Support\Carbon::parse($b['break_start'])->format('H:i') : 'ー' }}
              <span class="tilde">〜</span>
              {{ $b['break_end']   ? \Illuminate\Support\Carbon::parse($b['break_end'])->format('H:i')   : 'ー' }}
            </td>
            <td></td>
          </tr>
          @endif
        @endforeach

        {{-- 備考 --}}
        <tr>
          <th>備考</th>
          <td colspan="2"  class="remark-cell">{{ $pendingRequest->comment }}</td>
        </tr>
      </table>
    </div>

    <p class="pending-message">*承認待ちのため修正はできません。</p>
  @endif
</div>
@endsection
