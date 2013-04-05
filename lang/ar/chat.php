<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'chat', language 'ar', branch 'MOODLE_22_STABLE'
 *
 * @package   chat
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['autoscroll'] = 'تمرير تلقائي';
$string['beep'] = 'نغمة';
$string['chat:chat'] = 'شارك في المحاثة';
$string['chat:deletelog'] = 'حذف سجلات المحادثة';
$string['chat:readlog'] = 'قراءة سجلات المحادثة';
$string['chat:talk'] = 'التحدث في المحادثة';
$string['chatintro'] = 'نص المقدمة';
$string['chatname'] = 'أسم غرفة المحادثة الحاليه';
$string['chatreport'] = 'جلسة المحادثة';
$string['chattime'] = 'الموعد القادم المحادثة';
$string['configmethod'] = 'الطريقة العادية للمحادثة تتطلب من المستخدمة الاتصال المتكرر بالخادم وذلك للحصول على التحديثات. هذا لا يتطلب إى نوع من الاعدادات ويعمل في أي مكان، ولكن من الممكن أن يحمل الخادم الكثير عندما يكون عدد المتحدثين كثير. أستخدام خادم ديمون يتطلب الدخول على يونكس، ولكن يؤثر في تحسين وتسريع بيئة المحاثة.';
$string['confignormalupdatemode'] = 'تحديث غرفة المحادثة عادة تقدم بكفاءة باستخدام <em>استمرار النشاط</em> ميزة HTTP 1.1، ولكن هذا لا تزال ثقيلة جدا على الخادم. وهناك طريقة أكثر تقدما لاستخدام <em>التدفق</em> استراتيجية لتغذية التحديثات إلى المستخدمين. باستخدام <em>التدفق</em> ق المقاييس أفضل بكثير (على غرار الأسلوب chatd) ولكن قد لا تكون معتمدة من قبل الخادم الخاص بك.';
$string['configoldping'] = 'عند عدم سماع مستخدم، ماهي المده القصوى التي نعتبر المستخدم قد غادر غرفة المحاثة';
$string['configrefreshroom'] = 'ماهي المدة التي يحبذ ان تنشط خلالها غرفة المحادثة (بالثواني)؟ اختيا قيمه منخفظه يجعل غرفة المحادثة تبدو سريعة، ولكن هذا الإعدادسيحمل خادم موقعك عبء حينما يكون عدد المتحدثين في الغرفة كبير';
$string['configrefreshuserlist'] = 'ماهي المدة التي خلالها يتم تنشيط قائمة المستخدمين (بالثواني)';
$string['configserverhost'] = 'أسم الحاسب المضيف لديمون هو';
$string['configserverip'] = 'رقم عنوان بروتوكل الانترنت المطابق لاسم المضيف السابق';
$string['configservermax'] = 'الحد الاعلى لعدد العملاء';
$string['configserverport'] = 'المنفذ المستخدم على  الخادم لإستخدام ديمون';
$string['currentchats'] = 'نشط جلسة المحادثة';
$string['currentusers'] = 'المستخدمين الحاليين';
$string['deletesession'] = 'حذف هذه الجلسة';
$string['deletesessionsure'] = 'هل انت متأكد للقيام بحذف هذه الجلسة';
$string['donotusechattime'] = 'لا تقم بنشر المحادثة في أي وقت';
$string['enterchat'] = 'أضغط هناء للدخول إلى المحادثة';
$string['errornousers'] = 'لا يوجد أي مستخدم';
$string['explaingeneralconfig'] = 'هذه الاعدادات <strong>دائما</strong> فعاله';
$string['explainmethoddaemon'] = 'هذه الاعدادات مهمة <strong>فقط</strong> لو قمت باختيار "خادم ديمون للمحادثة" كطريقة المحادثة';
$string['explainmethodnormal'] = 'هذه الاعدادات مهمة <strong>فقط</strong> لو قمت باختيار "طريقة المحادثة العادية" كطريقة المحادثة';
$string['generalconfig'] = 'إعدادات عامة';
$string['idle'] = 'خامل';
$string['list_all_sessions'] = 'قائمة جميع الجلسات.';
$string['list_complete_sessions'] = 'قائمة الجلسات المكتملة فقط';
$string['listing_all_sessions'] = 'قائمة كل الجلسات';
$string['messagebeepseveryone'] = '{$a}  إرسال نغمة للجميع!';
$string['messagebeepsyou'] = '{$a}  أرسل نغمة لك!';
$string['messageenter'] = '{$a}  دخل غرفة محادثة';
$string['messageexit'] = '{$a}  خرج من غرفة محادثة';
$string['messages'] = 'رسائل';
$string['method'] = 'طريقة المحادثة';
$string['methoddaemon'] = 'خادم ديمون للمحادثة';
$string['methodnormal'] = 'الطريقة الاعتيادية';
$string['modulename'] = 'محادثة';
$string['modulenameplural'] = 'محادثات';
$string['neverdeletemessages'] = 'لاتمسح الرسائل أبداً';
$string['nextsession'] = 'الجلسة المجدوله التالية';
$string['no_complete_sessions_found'] = 'لا يوجد محادثات مكتملة';
$string['noguests'] = 'المحادثة غير متاحة للزوار';
$string['nomessages'] = 'لا توجد رسائل بعد';
$string['normalkeepalive'] = 'البقاء نشط';
$string['normalstream'] = 'تزويد';
$string['noscheduledsession'] = 'لا يوجد الجلسة مجدوله';
$string['notallowenter'] = 'انت غير مصرح لك بالدخول لغرفة الدردشة';
$string['oldping'] = 'قطع المهلة';
$string['pastchats'] = 'جلسات الدردشة الماضية';
$string['pluginname'] = 'محادثة';
$string['refreshroom'] = 'تحديث الغرفة';
$string['refreshuserlist'] = 'تحديث قائمة المستخدمين';
$string['removemessages'] = 'ازالة كل الرسائل';
$string['repeatdaily'] = 'في نفس الموعد كل يوم';
$string['repeatnone'] = 'لا تكرار- قم بنشر الموعد المحدد فقط';
$string['repeattimes'] = 'جلسات مكررة';
$string['repeatweekly'] = 'في نفس الموعد اسبوعياً';
$string['savemessages'] = 'أحفظ الجلسات السابقة';
$string['seesession'] = 'أعرض هذه الجلسة';
$string['serverhost'] = 'اسم الخادم';
$string['serverip'] = 'برتوكول الخادم (ip)';
$string['servermax'] = 'الحد الأقصى للمستخدمين';
$string['serverport'] = 'منفذ الخادم';
$string['sessions'] = 'جلسة محادثة';
$string['strftimemessage'] = '%H:%M';
$string['studentseereports'] = 'يستطيع الجميع معاينة الجلسات السابقة';
$string['updatemethod'] = 'تحديث الطريقة';
$string['viewreport'] = 'معاينة جلسات المحادثة السابقة';
