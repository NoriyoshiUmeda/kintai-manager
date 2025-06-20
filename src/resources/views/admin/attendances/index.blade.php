@extends('admin.layouts.app')

@section('title', '勤怠一覧')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/attendances/index.css') }}">
@endpush

@section('content')
<div class="container">

    {{-- 1. タイトル部分 --}}
    <div class="attendance-title">
        <span class="attendance-title-line"></span>
        <h2 class="attendance-title-text">
            {{ \Illuminate\Support\Carbon::parse($date)->format('Y年n月j日') }}の勤怠
        </h2>
    </div>

    {{-- 2. 日付ナビゲーション --}}
    <div class="day-nav">
        <a href="{{ route('admin.attendances.index',['date'=>\Illuminate\Support\Carbon::parse($date)->subDay()->toDateString()]) }}"
           class="day-nav-link">
           <img src="{{ asset('images/arrow-left.png') }}"
             alt="前日"
             class="day-nav-arrow-icon">
            <span>前日</span>
        </a>

        <div class="day-nav-current">
            <img src="{{ asset('images/calendar.png') }}"
                 alt="カレンダーアイコン"
                 class="month-nav-calendar-img">
            <span class="day-nav-date-text">
              {{ \Illuminate\Support\Carbon::parse($date)->format('Y/m/d') }}
            </span>
        </div>

        <a href="{{ route('admin.attendances.index',['date'=>\Illuminate\Support\Carbon::parse($date)->addDay()->toDateString()]) }}"
           class="day-nav-link">
           <span>翌日</span>
            <img src="{{ asset('images/arrow-right.png') }}"
             alt="翌日"
             class="day-nav-arrow-icon">
        </a>
    </div>

    {{-- 3. テーブルをカード状にラップ --}}
    <div class="table-card">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $att)
                    @php
                        // 休憩合計（分）
                        $breakMin = $att->breaks->reduce(function($sum,$b){
                            if($b->break_start&&$b->break_end){
                                $s=\Illuminate\Support\Carbon::parse($b->break_start);
                                $e=\Illuminate\Support\Carbon::parse($b->break_end);
                                return $sum+$e->diffInMinutes($s);
                            }
                            return $sum;
                        },0);
                        $breakDisplay = $breakMin
                            ? floor($breakMin/60).':'.sprintf('%02d',$breakMin%60)
                            : '';
                        // 実働
                        if($att->clock_in&&$att->clock_out){
                            $in = \Illuminate\Support\Carbon::parse($att->clock_in);
                            $out= \Illuminate\Support\Carbon::parse($att->clock_out);
                            $workMin = $out->diffInMinutes($in)-$breakMin;
                            $workDisplay = floor($workMin/60).':'.sprintf('%02d',$workMin%60);
                        } else {
                            $workDisplay = '';
                        }
                    @endphp
                    <tr>
                        <td>{{ $att->user->name }}</td>
                        <td>{{ $att->clock_in
                            ? \Illuminate\Support\Carbon::parse($att->clock_in)->format('H:i')
                            : '' }}</td>
                        <td>{{ $att->clock_out
                            ? \Illuminate\Support\Carbon::parse($att->clock_out)->format('H:i')
                            : '' }}</td>
                        <td>{{ $breakDisplay }}</td>
                        <td>{{ $workDisplay }}</td>
                        <td>
                            <a href="{{ route('admin.attendances.show',$att) }}"
                               class="attendance-link">詳細</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
