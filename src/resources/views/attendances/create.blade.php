{{-- resources/views/attendances/create.blade.php --}}
@extends('layouts.app')

@section('title', '勤怠登録')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/attendances/create.css') }}">
@endpush

@section('content')
<div class="container">
    {{-- ステータスバッジ表示 --}}
    @php
        $statusLabel = $attendance ? $attendance->status_label : '勤務外';
    @endphp
    <div class="text-center mb-4">
        <span class="status-badge">{{ $statusLabel }}</span>
    </div>

    {{-- 日付・現在時刻表示 --}}
    <div class="date-time text-center mb-6">
        @php
            // 0=日曜,1=月曜…6=土曜 のインデックスを漢字に変換
            $kanjiWeekdays = ['日','月','火','水','木','金','土'];
            $weekdayKanji = $kanjiWeekdays[$currentDateTime->dayOfWeek];
        @endphp
        <div class="date-time__date">
            {{ $currentDateTime->format('Y年n月j日') }}({{ $weekdayKanji }})
        </div>
        <div class="date-time__clock">{{ $currentDateTime->format('H:i') }}</div>
    </div>

    {{-- ボタンエリア --}}
    <div class="button-group">
        {{-- 1. 出勤前：$attendance が null のときのみ「出勤」を表示 --}}
        @if (! $attendance)
            <form action="{{ route('attendances.store') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn--primary">出勤</button>
            </form>

        {{-- 2. 出勤中：status が 1 のときのみ「退勤」「休憩入」を表示 --}}
        @elseif ($attendance->status === \App\Models\Attendance::STATUS_IN_PROGRESS)
            <form action="{{ route('attendances.leave') }}" method="POST" class="inline-block mr-2">
                @csrf
                <button type="submit" class="btn btn--primary">退勤</button>
            </form>
            <form action="{{ route('attendances.break-start') }}" method="POST" class="inline-block">
                @csrf
                <button type="submit" class="btn btn--secondary">休憩入</button>
            </form>

        {{-- 3. 休憩中：status が 2 のときのみ「休憩戻」を表示 --}}
        @elseif ($attendance->status === \App\Models\Attendance::STATUS_ON_BREAK)
            <form action="{{ route('attendances.break-end') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn--secondary">休憩戻</button>
            </form>

        {{-- 4. 退勤済：status が 3 のときのみ「お疲れ様でした。」を表示 --}}
        @elseif ($attendance->status === \App\Models\Attendance::STATUS_COMPLETED)
            <div>
                <p class="finished-message">お疲れ様でした。</p>
            </div>
        @endif
    </div>
</div>
@endsection
