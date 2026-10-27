<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تذكرة رقم #{{ $ticket->id }}</title>
    <style>
        @page { size: A4; margin: 0; }
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background: #fff;
            margin: 0;
            padding: 2cm;
        }

        .header-table { width: 100%; border-bottom: 2px solid #444; margin-bottom: 20px; padding-bottom: 10px; }
        .logo { font-size: 24px; font-weight: bold; color: #1a1a1a; }
        .report-title { text-align: left; font-size: 18px; color: #666; }

        .info-section { display: flex; justify-content: space-between; margin-bottom: 30px; background: #f9f9f9; padding: 15px; border-radius: 8px; }
        .info-box p { margin: 5px 0; font-size: 14px; }
        .info-box strong { color: #555; }

        .ticket-body { margin-bottom: 30px; }
        .ticket-title { font-size: 22px; color: #000; border-right: 5px solid #007bff; padding-right: 15px; margin-bottom: 15px; }
        .ticket-desc { font-size: 16px; text-align: justify; padding: 10px; background: #fff; border: 1px solid #eee; border-radius: 4px; }

        .section-header {
            border-right: 5px solid #007bff;
             padding-right: 15px;
              margin-bottom: 15px;
              font-weight: 800;
              font-size: 22px;
        }

        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th, .data-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: right;
            font-size: 13px;
        }
        .data-table th { background-color: #f8f9fa; color: #555; }

        .footer-section { margin-top: 50px; width: 100%; border-top: 1px solid #ddd; padding-top: 20px; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 1.5cm; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

     <div class="no-print" style="text-align: center; padding: 20px; background: #eee;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">تأكيد الطباعة</button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer;">إغلاق المعاينة</button>
    </div>

    <div class="container">
        <table class="header-table">
            <tr>
                <td class="logo">نظام الدعم الفني | Ticket System</td>
                <td class="report-title">تقرير تفاصيل التذكرة والسجل</td>
            </tr>
        </table>

        <div class="info-section">
            <div class="info-box">
                <p><strong>رقم التذكرة:</strong> #{{ $ticket->id }}</p>
                <p><strong>تاريخ الإنشاء:</strong> {{ $ticket->created_at->format('Y-m-d H:i') }}</p>
                <p><strong>حالة الطلب:</strong> {{ $ticket->status_label }}</p>
            </div>
            <div class="info-box" style="text-align: left;">
                <p><strong>المستخدم:</strong> {{ $ticket->creator->fullname ?? 'غير محدد' }}</p>
                <p><strong>القسم:</strong> {{ $ticket->category->department->name ?? 'غير محدد' }}</p>
                <p><strong>مكان المشكلة:</strong> {{ $ticket->office_location ?? 'غير محدد' }}</p>
            </div>
        </div>

        <div class="ticket-body">
            <h1 class="ticket-title">{{ $ticket->title }}</h1>
            <div class="ticket-desc">
                <strong>وصف المشكلة/الطلب:</strong>
                <p>{{ $ticket->description }}</p>
            </div>
        </div>

        <div class="section-header">الردود</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20%;">التاريخ</th>
                    <th style="width: 20%;">بواسطة</th>
                    <th style="width: 60%;">الرد</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ticket->messages as $message)
                <tr>
                    <td>{{ $message->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $message->user->fullname }}</td>
                    <td>{{ $message->content }}</td>
                </tr>
                @empty
                <tr><td colspan="3" style="text-align: center;">لا توجد ردود مسجلة.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-header">سجل تحركات التذكرة (Logs)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20%;">التاريخ</th>
                    <th style="width: 20%;">المسؤول</th>
                    <th style="width: 60%;">الإجراء (Content)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ticketLogs as $log)
                <tr>
                    <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $log->user->fullname ?? 'النظام' }}</td>
                    <td>{{ $log->content }}</td>
                </tr>
                @empty
                <tr><td colspan="3" style="text-align: center;">لا يوجد سجل نشاطات.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin-top: 30px; font-size: 11px; color: #888; text-align: right;">
            * تم استخراج هذا التقرير شاملاً الردود واللوج بتاريخ {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>

</body>
</html>
