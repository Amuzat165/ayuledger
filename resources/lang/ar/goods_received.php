<?php
return [
    'title' => 'البضائع المستلمة', 'breadcrumb' => 'البضائع المستلمة', 'record_receipt' => 'تسجيل استلام البضائع', 'approve_purchase_order' => 'اعتماد أمر الشراء', 'approved_by' => 'اعتمده',
    'receipt_number' => 'رقم الاستلام', 'purchase_order' => 'أمر الشراء', 'supplier' => 'المورد', 'branch' => 'الفرع',
    'received_date' => 'تاريخ الاستلام', 'received_by' => 'استلمه', 'notes' => 'ملاحظات', 'item' => 'الصنف', 'ordered_quantity' => 'الكمية المطلوبة',
    'previously_received' => 'المستلم سابقاً', 'remaining_quantity' => 'الكمية المتبقية', 'received_quantity' => 'الكمية المستلمة', 'unit' => 'الوحدة',
    'agreed_price' => 'سعر الوحدة المتفق عليه', 'status' => 'الحالة', 'actions' => 'الإجراءات', 'draft' => 'مسودة', 'confirmed' => 'مؤكد',
    'save_draft' => 'حفظ المسودة', 'confirm' => 'تأكيد استلام البضائع', 'view' => 'عرض', 'back' => 'رجوع', 'empty' => 'لا توجد سجلات استلام بعد.',
    'draft_help' => 'المسودة لا تغيّر المخزون أو حالة أمر الشراء.', 'confirm_help' => 'يحدّث التأكيد كميات المخزون المؤهلة وحالة أمر الشراء معاً.',
    'messages' => ['draft_saved' => 'تم حفظ مسودة استلام البضائع.', 'confirmed' => 'تم تأكيد استلام البضائع.', 'purchase_order_approved' => 'تم اعتماد أمر الشراء وأصبح جاهزاً للاستلام.'],
    'errors' => ['only_draft_can_be_approved' => 'يمكن اعتماد مسودة أمر الشراء فقط.', 'order_requires_items' => 'أضف صنفاً واحداً على الأقل قبل اعتماد الأمر.', 'order_not_receivable' => 'يجب أن يكون أمر الشراء معتمداً وله كميات متبقية.', 'invalid_submission' => 'مرجع الإرسال مستخدم بالفعل لأمر شراء آخر.', 'at_least_one_quantity' => 'أدخل كمية مستلمة لصنف واحد على الأقل.', 'line_not_in_order' => 'صنف أو أكثر لا يتبع أمر الشراء هذا.', 'quantity_must_be_positive' => 'يجب أن تكون الكمية المستلمة أكبر من صفر.', 'over_receiving' => 'الكمية تتجاوز الكمية المتبقية في أمر الشراء.', 'inventory_unavailable' => 'صنف المخزون المرتبط لم يعد متاحاً.', 'classify_inventory_first' => 'حدد تتبع المخزون للأصناف المرتبطة قبل التأكيد.', 'whole_stock_quantity' => 'يجب استلام الأصناف المتتبعة كمخزون بكميات صحيحة.', 'inventory_branch_mismatch' => 'يجب أن يتبع صنف المخزون المتتبع فرع أمر الشراء.'],
];
