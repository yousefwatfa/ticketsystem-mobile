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
        .logo { font-size: 24px; fw-bold; color: #1a1a1a; }
        .report-title { text-align: left; font-size: 18px; color: #666; }

        .info-section { display: flex; justify-content: space-between; margin-bottom: 30px; background: #f9f9f9; padding: 15px; border-radius: 8px; }
        .info-box p { margin: 5px 0; font-size: 14px; }
        .info-box strong { color: #555; }

        .ticket-body { margin-bottom: 30px; }
        .ticket-title { font-size: 22px; color: #000; border-right: 5px solid #007bff; padding-right: 15px; margin-bottom: 15px; }
        .ticket-desc { font-size: 16px; text-align: justify; padding: 10px; background: #fff; border: 1px solid #eee; border-radius: 4px; }

        .footer-section { margin-top: 50px; width: 100%; border-top: 1px solid #ddd; padding-top: 20px; }
        .signature-table { width: 100%; text-align: center; margin-top: 40px; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 2cm; }
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
                <td class="report-title">تقرير تفاصيل التذكرة</td>
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

        <div style="margin-top: 20px; font-size: 12px; color: #888;">
            * تم إنشاء هذا التقرير تلقائياً عبر النظام بتاريخ {{ now()->format('Y-m-d H:i') }}
        </div>

        <table class="signature-table">
            <tr>


            </tr>
        </table>
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
