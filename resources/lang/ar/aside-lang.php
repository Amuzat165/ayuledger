<?php
$translations = [
    'inventory' => ['resource' => 'المخزون', 'create' => 'إضافة إلى المخزون', 'index' => 'عرض المخزون'],
    'customers' => [
        'resource' => 'العملاء',
        'create' => ['customer' => 'إضافة عميل'],
        'index' => 'عرض العملاء',
    ],
    'vendors' => [
        'resource' => 'الموردون',
        'create' => 'إضافة مورد',
    ],
];

$translations = array_replace_recursive($translations, [
    'customers' => [
        'create' => ['invoice'=>'إضافة فاتورة','receipt'=>'إضافة إيصال'],
        'view'=>'عرض الفواتير','show'=>'عرض الإيصالات',
    ],
    'vendors' => ['index'=>[
        'vendors'=>'عرض الموردين','vouchers'=>'سندات الدفع','view-receipt'=>'عرض الدفعات',
        'view-purchase'=>'عرض المشتريات','orders'=>'أوامر الشراء','invocies'=>'استلام فاتورة',
        'expenses'=>'المصروفات','expenses-view'=>'عرض المصروفات',
    ]],
    'budget'=>['resource'=>'الموازنة'],
    'assets'=>['schedule'=>'الأصول'],
    'payrolls'=>['resource'=>'الرواتب'],
    'general-journal'=>['resource'=>'اليومية العامة'],
]);

return $translations;
