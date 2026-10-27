@php
$arabic = new \ArPHP\I18N\Arabic();
$ar = fn($text) => $arabic->utf8Glyphs((string)($text ?? ''), 1000, false);
@endphp
<!DOCTYPE html>
<html lang="ar" dir="ltr">

<head>
    <meta charset="UTF-8">
    <title>التقرير المفصل</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            direction: ltr;
            text-align: right;
            color: #1f2937;
            font-size: 12px;
            line-height: 1.6;
        }

        .header {
            border-bottom: 2px solid #1d4ed8;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            color: #1d4ed8;
            margin: 0 0 6px;
        }

        .subtitle {
            margin: 0;
            color: #6b7280;
        }

        .summary {
            width: 100%;
            margin-bottom: 18px;
            border-collapse: collapse;
        }

        .summary td {
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }

        .summary .label {
            width: 22%;
            background: #eff6ff;
            font-weight: bold;
        }

        .stats {
            margin: 14px 0 18px;
            padding: 10px 12px;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
        }

        table.report {
            width: 100%;
            border-collapse: collapse;
        }

        table.report th,
        table.report td {
            border: 1px solid #d1d5db;
            padding: 8px;
            vertical-align: top;
        }

        table.report th {
            background: #1d4ed8;
            color: #ffffff;
            font-weight: bold;
            font-size: 11px;
        }

        .muted {
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1 class="title">{{ $ar('تقرير مفصل للتذاكر') }}</h1>
        <p class="subtitle">{{ $ar($reportUser ? 'تقرير PDF خاص بالمستخدم المحدد ضمن صفحة التقارير' : 'تقرير PDF خاص بالقسم ضمن صفحة التقارير') }}</p>
    </div>

    <table class="summary">
        @if($reportUser)
        <tr>
            <td class="label">{{ $ar('اسم المستخدم') }}</td>
            <td>{{ $ar($reportUser ? $reportUser->fullname : 'كل المستخدمين') }}</td>
            <td class="label">{{ $ar('اسم الحساب') }}</td>
            <td>{{ $reportUser ? $reportUser->username : '---' }}</td>
        </tr>
        <tr>
            <td class="label">{{ $ar('البريد الإلكتروني') }}</td>
            <td>{{ ($reportUser && $reportUser->email) ? $reportUser->email : '---' }}</td>
            <td class="label">{{ $ar('فترة التقرير') }}</td>
            <td>
                {{ $ar('من') }} {{ $filters['from_date'] ?: $ar('بداية السجلات') }}
                {{ $ar('إلى') }} {{ $filters['to_date'] ?: $ar('اليوم') }}
            </td>
        </tr>
        @else
        <tr>
            <td class="label">{{ $ar('النوع') }}</td>
            <td>{{ $ar('تقرير القسم') }}</td>
            <td class="label">{{ $ar('فترة التقرير') }}</td>
            <td>
                {{ $ar('من') }} {{ $filters['from_date'] ?: $ar('بداية السجلات') }}
                {{ $ar('إلى') }} {{ $filters['to_date'] ?: $ar('اليوم') }}
            </td>
        </tr>
        @endif
        <tr>
            <td class="label">{{ $ar('العنوان') }}</td>
            <td>{{ $ar($filters['title'] ?: 'الكل') }}</td>
            <td class="label">{{ $ar('المشكلة') }}</td>
            <td>{{ $ar($filters['category'] ?: 'الكل') }}</td>
        </tr>
    </table>

    <div class="stats">
        <strong>{{ $ar('إجمالي التذاكر ضمن التقرير:') }}</strong>
        {{ $tickets->count() }}
    </div>

    <table class="report">
        <thead>
            <tr>
                <th style="width: 8%;">{{ $ar('رقم التذكرة') }}</th>
                <th style="width: 16%;">{{ $ar('العنوان') }}</th>
                <th style="width: 13%;">{{ $ar('المشكلة') }}</th>
                <th style="width: 13%;">{{ $ar('القسم') }}</th>
                <th style="width: 8%;">{{ $ar('الحالة') }}</th>
                <th style="width: 16%;">{{ $ar('الوصف') }}</th>
                <th style="width: 14%;">{{ $ar('المعين لهم') }}</th>
                <th style="width: 15%;">{{ $ar('التاريخ') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $ticket)
            <tr>
                <td>#{{ $ticket->id }}</td>
                <td>{{ $ar($ticket->title) }}</td>
                <td>{{ $ar($ticket->category?->name ?: '---') }}</td>
                <td>{{ $ar($ticket->category?->department?->name ?: '---') }}</td>
                <td>{{ $ar($ticket->status_label) }}</td>
                <td>{{ $ar($ticket->description ?: '---') }}</td>
                <td>
                    @if($ticket->assignedUsers->isNotEmpty())
                    {{ $ar($ticket->assignedUsers->pluck('fullname')->implode('، ')) }}
                    @else
                    ---
                    @endif
                </td>
                <td>{{ optional($ticket->created_at)->format('Y-m-d H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center;">{{ $ar('لا توجد بيانات مطابقة للمعايير المحددة.') }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <p class="muted" style="margin-top: 16px;">
        {{ $ar('تم إنشاء هذا التقرير بتاريخ') }} {{ now()->format('Y-m-d H:i') }}.
    </p>
</body>

</html>