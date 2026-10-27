<div dir="rtl">

# التطبيق الـ Native — الإصدار الأول (React Native + Expo)

> تطبيق عربي (RTL) للموظفين والدعم الفني، يعمل فقط داخل شبكة المستشفى.
> الكود في مجلد [`mobile-app/`](../../mobile-app) · مرجع الـ API في [API.md](API.md) · الخطة في [PLAN.md](PLAN.md).

اللقطات مأخوذة من التطبيق نفسه (نسخة الويب للتطوير) مربوطاً بالـ API الحقيقي وبيانات تجريبية.

## الدخول والشبكة

<table>
<tr>
<td align="center" width="50%"><img src="screenshots/01-login.jpg" width="240"><br><b>تسجيل الدخول</b><br>نفس حساب الشبكة (LDAP)، وإعدادات الخادم قابلة للتعديل</td>
<td align="center" width="50%"><img src="screenshots/02-offline.jpg" width="240"><br><b>خارج شبكة المستشفى</b><br>رسالة واضحة بدل خطأ تقني</td>
</tr>
</table>

## الموظف

<table>
<tr>
<td align="center" width="33%"><img src="screenshots/03-my-tickets.jpg" width="220"><br><b>تذاكري</b><br>فلاتر الحالة مع العدد، سحب للتحديث</td>
<td align="center" width="33%"><img src="screenshots/04-ticket-details.jpg" width="220"><br><b>تفاصيل التذكرة</b><br>المكان والنوع والمسؤول والمرفقات</td>
<td align="center" width="33%"><img src="screenshots/05-conversation.jpg" width="220"><br><b>المحادثة</b><br>رد مباشر + إرفاق صورة بالكاميرا</td>
</tr>
<tr>
<td align="center"><img src="screenshots/06-new-ticket.jpg" width="220"><br><b>تذكرة جديدة</b><br>المكان يُعبّأ تلقائياً من الحساب</td>
<td align="center"><img src="screenshots/07-category-picker.jpg" width="220"><br><b>نوع المشكلة</b><br>قائمة سفلية سهلة اللمس</td>
<td align="center"><img src="screenshots/08-validation.jpg" width="220"><br><b>التحقق</b><br>أخطاء تحت كل حقل تختفي عند التعديل</td>
</tr>
<tr>
<td align="center"><img src="screenshots/11-confirm-fix.jpg" width="220"><br><b>هل تم حل المشكلة؟</b><br>تأكيد الحل أو "المشكلة مستمرة"</td>
<td align="center"><img src="screenshots/12-closed.jpg" width="220"><br><b>تذكرة مكتملة</b><br>الرد مقفل بعد الإغلاق</td>
<td align="center"><img src="screenshots/13-account.jpg" width="220"><br><b>حسابي</b><br>البيانات والخادم وتسجيل الخروج</td>
</tr>
</table>

## الدعم الفني (Admin)

<table>
<tr>
<td align="center" width="50%"><img src="screenshots/09-admin-tickets.jpg" width="240"><br><b>تذاكر القسم</b><br>بحث برقم التذكرة أو العنوان أو اسم الموظف + فلاتر</td>
<td align="center" width="50%"><img src="screenshots/10-admin-actions.jpg" width="240"><br><b>إجراءات الأدمن</b><br>استلام · تم الحل · رد</td>
</tr>
</table>

## ما تم اختباره فعلياً

دورة كاملة عبر التطبيق مقابل الـ API:
1. الموظف يسجّل الدخول ويرى تذاكره.
2. يفتح تذكرة جديدة (مع التحقق من الحقول) ← تُنشأ وتُفتح مباشرة.
3. يرد على تذكرة ← يظهر الرد فوراً.
4. الأدمن يسجّل الدخول ← يرى التذكرة في "تذاكر القسم" ← **استلام** ← **تم الحل**.
5. الموظف يرى "هل تم حل المشكلة؟" ← **تأكيد الحل** ← التذكرة مكتملة والرد مقفل.
6. خادم غير متاح ← رسالة "تأكد من اتصالك بشبكة Wi-Fi المستشفى".

الفحوصات: `npx tsc --noEmit` و`npx eslint src` نظيفة، و46 اختبار للخادم (`php artisan test`).

## لم يُنفّذ بعد

- **الإشعارات الفورية** في التطبيق (تسجيل Expo push token) — جهة الخادم جاهزة.
- **شاشة الإشعارات** داخل التطبيق.
- **بناء التطبيق** (APK للأندرويد عبر EAS، والآيفون يحتاج حساب Apple Developer و HTTPS).
- الاختبار على **أجهزة حقيقية** (Expo Go على Wi-Fi المستشفى).

</div>
