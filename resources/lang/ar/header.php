<?php
$translations = [
    'language_selection' => 'اللغة',
    'quick_create' => 'إنشاء سريع',
    'add_customer' => 'إضافة عميل',
    'add_vendor' => 'إضافة مورد',
    'notifications' => 'الإشعارات',
    'options' => 'الخيارات',
    'account' => 'الحساب',
    'profile' => 'الملف الشخصي',
    'sign_out' => 'تسجيل الخروج',
];

$translations = array_replace_recursive($translations, [
    'create_invoice'=>'إنشاء فاتورة',
    'receive_payment'=>'استلام دفعة',
    'record_expense'=>'تسجيل مصروف',
    'journal_entry'=>'قيد يومية',
]);

return $translations;
