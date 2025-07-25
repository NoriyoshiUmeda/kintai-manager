{{-- resources/views/admin/attendances/show.blade.php --}}
@extends('admin.layouts.app')

@section('title', '勤怠詳細（管理者）')

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
    $user   = $attendance->user;
    $breaks = $breaks ?? $attendance->breaks;

    // 申請状況フラグ
    $isPending  = !empty($pendingRequest);
    $isApproved = !empty($approvedRequest);
  @endphp

  @if (! $isPending)
    {{-- 編集フォーム（承認済みもOK） --}}
    <form action="{{ route('admin.attendances.update', $attendance) }}" method="POST" class="detail-form" novalidate>
      @csrf
      @method('PATCH')
      <div class="detail-card">
        <table class="detail-table">
          <tr>
            <th>名前</th>
            <td class="value-cell" colspan="2">{{ $user->name }}</td>
          </tr>
          <tr>
            <th>日付</th>
            <td class="value-cell" colspan="2">
              <span class="work-year">{{ $attendance->work_date->format('Y年') }}</span>
              <span class="work-date">{{ $attendance->work_date->format('n月j日') }}</span>
            </td>
          </tr>
          <tr>
            <th>出勤・退勤</th>
            <td class="value-cell input-cell" colspan="2">
              <div class="input-cell-inner">
                <input type="time" name="clock_in" value="{{ old('clock_in', optional($attendance->clock_in)->format('H:i')) }}" class="time-input" required>
                <span class="tilde">〜</span>
                <input type="time" name="clock_out" value="{{ old('clock_out', optional($attendance->clock_out)->format('H:i')) }}" class="time-input" required>
              </div>
              @php
                $clockMessages = collect([
                  $errors->first('clock_in'),
                  $errors->first('clock_out')
                ])->unique()->filter();
              @endphp
              @if ($clockMessages->isNotEmpty())
                <div class="error-message">{{ $clockMessages->first() }}</div>
              @endif
            </td>
          </tr>
          @foreach($breaks->concat([null]) as $i => $b)
          <tr>
            <th>{{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}</th>
            <td class="value-cell input-cell" colspan="2">
              <div class="input-cell-inner">
                <input type="time" name="breaks[{{ $i }}][break_start]" value="{{ old("breaks.$i.break_start", optional($b)->break_start ? \Illuminate\Support\Carbon::parse($b->break_start)->format('H:i') : '') }}" class="time-input">
                <span class="tilde">〜</span>
                <input type="time" name="breaks[{{ $i }}][break_end]" value="{{ old("breaks.$i.break_end", optional($b)->break_end ? \Illuminate\Support\Carbon::parse($b->break_end)->format('H:i') : '') }}" class="time-input">
              </div>
              @php
                $breakMessages = collect([
                  $errors->first("breaks.$i.break_start"),
                  $errors->first("breaks.$i.break_end")
                ])->unique()->filter();
              @endphp
              @if ($breakMessages->isNotEmpty())
                <div class="error-message">{{ $breakMessages->first() }}</div>
              @endif
            </td>
          </tr>
          @endforeach
          <tr>
            <th>備考</th>
            <td class="value-cell comment-cell" colspan="2">
              <textarea name="comment" class="comment-input" placeholder="備考を入力">{{ old('comment', $attendance->comment) }}</textarea>
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
    {{-- 読み取り専用 --}}
    <div class="detail-card readonly">
      <table class="detail-table">
        <tr>
          <th>名前</th>
          <td class="value-cell" colspan="2">{{ $user->name }}</td>
        </tr>
        <tr>
          <th>日付</th>
          <td class="value-cell" colspan="2">
            <span class="work-year">{{ $attendance->work_date->format('Y年') }}</span>
            <span class="work-date">{{ $attendance->work_date->format('n月j日') }}</span>
          </td>
        </tr>
        <tr>
          <th>出勤・退勤</th>
          <td class="value-cell" colspan="2">
            {{ optional($attendance->clock_in)->format('H:i') }}<span class="tilde">〜</span>{{ optional($attendance->clock_out)->format('H:i') }}
          </td>
        </tr>
        @foreach($breaks as $i => $b)
          @if(!empty($b->break_start) && !empty($b->break_end))
          <tr>
            <th>{{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}</th>
            <td class="value-cell" colspan="2">
              {{ \Illuminate\Support\Carbon::parse($b->break_start)->format('H:i') }}
              <span class="tilde">〜</span>
              {{ \Illuminate\Support\Carbon::parse($b->break_end)->format('H:i') }}
            </td>
          </tr>
          @endif
        @endforeach
        <tr>
          <th>備考</th>
          <td class="remark-cell" colspan="2">{{ $attendance->comment }}</td>
        </tr>
      </table>
    </div>
    <p class="pending-message">*承認待ちのため修正はできません。</p>
  @endif
</div>
@endsection
