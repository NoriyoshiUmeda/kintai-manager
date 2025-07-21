@extends('admin.layouts.app')

@section('title', $user->name . 'さんの勤怠一覧')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/attendances/index.css') }}">
@endpush

@section('content')
<div class="container">

    {{-- 1. タイトル部分 --}}
    <div class="attendance-title">
        <span class="attendance-title-line"></span>
        <h2 class="attendance-title-text">{{ $user->name }} さんの勤怠一覧</h2>
    </div>

    @php

        $currentMonth = \Illuminate\Support\Carbon::today()->format('Y-m');
    @endphp

    {{-- 2. 月ナビゲーション --}}
    <div class="month-nav">
        {{-- 前月ボタン --}}
        <a href="{{ route('admin.users.attendances.index', ['user' => $user->id, 'month' => $prevMonth]) }}"
           class="month-nav-link">
            <img src="{{ asset('images/arrow-left.png') }}"
                 alt="前月"
                 class="month-nav-arrow-img">
            <span class="month-nav-link-text">前月</span>
        </a>

        {{-- 現在年月 --}}
        <div class="month-nav-current">
            <img src="{{ asset('images/calendar.png') }}"
                 alt="カレンダーアイコン"
                 class="month-nav-calendar-img">
            <span class="month-nav-current-text">{{ $displayYearMonth }}</span>
        </div>

        {{-- 翌月ボタン --}}
        @if($nextMonth <= $currentMonth)
        <a href="{{ route('admin.users.attendances.index', ['user' => $user->id, 'month' => $nextMonth]) }}"
           class="month-nav-link">
            <span class="month-nav-link-text">翌月</span>
            <img src="{{ asset('images/arrow-right.png') }}"
                 alt="翌月"
                 class="month-nav-arrow-img">
        </a>
        @else
            <button type="button"
                    class="month-nav-link"
                    disabled
                    style="opacity:0.5;cursor:not-allowed;">
                <span class="month-nav-link-text">翌月</span>
                <img src="{{ asset('images/arrow-right.png') }}"
                     alt="翌月"
                     class="month-nav-arrow-img">
            </button>
        @endif
    </div>

    {{-- 3. テーブルをカード状にラップ --}}
    <div class="table-card">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $kanjiWeekdays = ['日','月','火','水','木','金','土'];
                    $current = $firstDayOfMonth->copy();
                @endphp

                @while($current->lte($lastDayOfMonth))
                    @php
                        $key = $current->toDateString();
                        $att = $attendances->get($key);

                        if ($att) {
                            $in = optional($att->clock_in)
                                   ? \Illuminate\Support\Carbon::parse($att->clock_in)->format('H:i')
                                   : '-';
                            $out = optional($att->clock_out)
                                    ? \Illuminate\Support\Carbon::parse($att->clock_out)->format('H:i')
                                    : '-';

                            $breakTotal = $att->breaks->reduce(function($sum, $b) {
                                if ($b->break_start && $b->break_end) {
                                    $start = \Illuminate\Support\Carbon::parse($b->break_start);
                                    $end   = \Illuminate\Support\Carbon::parse($b->break_end);
                                    return $sum + $end->diffInMinutes($start);
                                }
                                return $sum;
                            }, 0);

                            $breakDisplay = $breakTotal
                                ? floor($breakTotal / 60) . ':' . sprintf('%02d', $breakTotal % 60)
                                : '-';

                            if ($att->clock_in && $att->clock_out) {
                                $workMinutes = \Illuminate\Support\Carbon::parse($att->clock_out)
                                               ->diffInMinutes(\Illuminate\Support\Carbon::parse($att->clock_in))
                                               - $breakTotal;
                                $workDisplay = floor($workMinutes / 60) . ':' . sprintf('%02d', $workMinutes % 60);
                            } else {
                                $workDisplay = '-';
                            }
                        } else {
                            $in = $out = $breakDisplay = $workDisplay = '';
                        }

                        $weekdayKanji = $kanjiWeekdays[$current->dayOfWeek];
                    @endphp

                    <tr>
                        <td class="date-cell">
                            {{ $current->format('m/d') }}（{{ $weekdayKanji }}）
                        </td>
                        <td>{{ $in }}</td>
                        <td>{{ $out }}</td>
                        <td>{{ $breakDisplay }}</td>
                        <td>{{ $workDisplay }}</td>
                        <td>
                            @if($att)
                                <a href="{{ route('admin.attendances.show', $att->id) }}"
                                   class="attendance-link">
                                    詳細
                                </a>
                            @endif
                        </td>
                    </tr>

                    @php $current->addDay() @endphp
                @endwhile
            </tbody>
        </table>
    </div>

    {{-- 4. CSV出力ボタン --}}
    <div class="button-wrapper">
        <a href="{{ route('admin.users.attendances.csv', ['user' => $user->id, 'month' => request('month')]) }}"
           class="btn-csv">
            CSV出力
        </a>
    </div>

</div>
@endsection
