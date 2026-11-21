<?php
return [
    'inventory' => [
        'resource' => 'Inventory',
        'create' => 'Add Inventory',
        'index' => 'View Inventory'
    ],
    'customers' => [
        'resource' => 'Customers',
        'create' => [
            'customer' => 'Add Customer',
            'invoice' => 'Add Invoice',
            'receipt' => 'Add Receipt'
        ],
        'index' => 'View Customers',
        'view' => 'View Invoice',
    ],
    'vendors' => [
        'resource' => 'Vendors',
        'create' => 'Add Vendor',
        'index' => [
            'vendors' => 'View Vendors',
            'vouchers' => 'Payment Vouchers',
            'view-purchase' => 'View Purchase',
            'orders' => 'Purchase Orders',
            'invocies' => 'Receive Invoice',
            'expenses' => 'Expenses',
            'expenses-view' => 'View Expenses'
        ]
    ],
    'budget' => [
        'resource' => 'Budget',
    ],
    'payrolls' => [
        'resource' => 'Payroll',
    ],
    'general-journal' => [
        'resource' => 'General Journal',
    ]
];
