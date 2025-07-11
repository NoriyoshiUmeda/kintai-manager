{{-- resources/views/attendances/show.blade.php --}}
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
    use Illuminate\Support\Carbon;

    // コントローラから渡されたリクエスト変数
    $isApproved = isset($approvedRequest) && $approvedRequest;
    $isPending  = isset($pendingRequest)  && $pendingRequest;

    if ($isApproved) {
        // 承認済み申請を優先表示
        $displayIn      = Carbon::parse($approvedRequest->requested_in)->format('H:i');
        $displayOut     = Carbon::parse($approvedRequest->requested_out)->format('H:i');
        $displayBreaks  = collect($approvedRequest->requested_breaks);
        $displayComment = $approvedRequest->comment;
    } else {
        // 承認待ち or 通常表示 用データ
        $displayIn      = old('clock_in', optional($attendance->clock_in ? Carbon::parse($attendance->clock_in) : null)->format('H:i'));
        $displayOut     = old('clock_out', optional($attendance->clock_out ? Carbon::parse($attendance->clock_out) : null)->format('H:i'));
        $displayBreaks  = collect(old(
                             'breaks',
                             $attendance->breaks
                               ->map(fn($b) => [
                                 'break_start' => $b->break_start,
                                 'break_end'   => $b->break_end,
                               ])->toArray()
                          ));
        $displayComment = old('comment', $attendance->comment);
    }

    // 編集フォーム用に空行を1つ追加
    $formBreaks = $displayBreaks->concat([['break_start' => '', 'break_end' => '']]);
  @endphp

  {{-- 編集フォーム：承認待ちでも承認済みでもないとき --}}
  @if (! $isPending && ! $isApproved)
    <form action="{{ route('attendances.update', $attendance) }}" method="POST" class="detail-form" novalidate>
      @csrf
      <div class="detail-card">
        <table class="detail-table">
          {{-- 名前 --}}
          <tr>
            <th>名前</th>
            <td class="value-cell" colspan="2">{{ Auth::user()->name }}</td>
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
                <input type="time"  name="clock_in"  value="{{ $displayIn }}"  class="time-input" required>
                <span class="tilde">〜</span>
                <input type="time"  name="clock_out" value="{{ $displayOut }}" class="time-input" required>
              </div>
              @php
                $msgs = collect([$errors->first('clock_in'), $errors->first('clock_out')])
                          ->unique()->filter();
              @endphp
              @if($msgs->isNotEmpty())
                <div class="error-message">{{ $msgs->first() }}</div>
              @endif
            </td>
            <td></td>
          </tr>
          {{-- 休憩入力 --}}
          @foreach($formBreaks as $i => $b)
          <tr>
            <th>{{ $i===0 ? '休憩' : '休憩'.($i+1) }}</th>
            <td class="value-cell input-cell">
              <div class="input-cell-inner">
                <input
                  type="time"
                  name="breaks[{{ $i }}][break_start]"
                  value="{{ optional($b['break_start']?Carbon::parse($b['break_start']):null)->format('H:i') }}"
                  class="time-input"
                >
                <span class="tilde">〜</span>
                <input
                  type="time"
                  name="breaks[{{ $i }}][break_end]"
                  value="{{ optional($b['break_end']?Carbon::parse($b['break_end']):null)->format('H:i') }}"
                  class="time-input"
                >
              </div>
              @php
                $errs = collect([
                  $errors->first("breaks.$i.break_start"),
                  $errors->first("breaks.$i.break_end"),
                ])->unique()->filter();
              @endphp
              @if($errs->isNotEmpty())
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
              <textarea name="comment" class="comment-input">{{ $displayComment }}</textarea>
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

  {{-- 読み取り専用表示 --}}
  @else
    <div class="detail-card readonly">
      <table class="detail-table">
        {{-- 名前 --}}
        <tr>
          <th>名前</th>
          <td class="value-cell" colspan="2">{{ Auth::user()->name }}</td>
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
          <td class="value-cell">
            {{ $displayIn }}<span class="tilde">〜</span>{{ $displayOut }}
          </td>
          <td></td>
        </tr>
        {{-- 休憩表示 --}}
        @foreach($displayBreaks as $i => $b)
          @if(! empty($b['break_start']) && ! empty($b['break_end']))
            <tr>
              <th>{{ $i===0 ? '休憩' : '休憩'.($i+1) }}</th>
              <td class="value-cell">
                {{ Carbon::parse($b['break_start'])->format('H:i') }}
                <span class="tilde">〜</span>
                {{ Carbon::parse($b['break_end'])->format('H:i') }}
              </td>
              <td></td>
            </tr>
          @endif
        @endforeach
        {{-- 備考 --}}
        <tr>
          <th>備考</th>
          <td class="remark-cell" colspan="2">{{ $displayComment }}</td>
        </tr>
      </table>
    </div>

    {{-- 承認待ちメッセージ --}}
    @if($isPending && ! $isApproved)
      <p class="pending-message">*承認待ちのため修正はできません。</p>
    @endif
  @endif
</div>
@endsection
