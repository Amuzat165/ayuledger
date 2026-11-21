<?php
return [
    'title' => 'Goods Received', 'breadcrumb' => 'Goods Received', 'record_receipt' => 'Record Goods Received', 'approve_purchase_order' => 'Approve Purchase Order', 'approved_by' => 'Approved by',
    'receipt_number' => 'Receipt number', 'purchase_order' => 'Purchase Order', 'supplier' => 'Supplier', 'branch' => 'Branch',
    'received_date' => 'Received Date', 'received_by' => 'Received By', 'notes' => 'Notes', 'item' => 'Item', 'ordered_quantity' => 'Ordered Quantity',
    'previously_received' => 'Previously Received', 'remaining_quantity' => 'Remaining Quantity', 'received_quantity' => 'Received Quantity',
    'unit' => 'Unit', 'agreed_price' => 'Agreed Unit Price', 'status' => 'Status', 'actions' => 'Actions', 'draft' => 'Draft',
    'confirmed' => 'Confirmed', 'save_draft' => 'Save Draft', 'confirm' => 'Confirm Goods Received', 'view' => 'View', 'back' => 'Back',
    'empty' => 'No Goods Received records yet.', 'draft_help' => 'A draft does not update Inventory or the Purchase Order status.',
    'confirm_help' => 'Confirmation updates eligible Inventory quantities and the Purchase Order status together.',
    'messages' => ['draft_saved' => 'Goods Received draft saved.', 'confirmed' => 'Goods Received confirmed.', 'purchase_order_approved' => 'Purchase Order approved and ready for Goods Received.'],
    'errors' => [
        'only_draft_can_be_approved' => 'Only a draft Purchase Order can be approved.', 'order_requires_items' => 'Add at least one item before approving this Purchase Order.',
        'order_not_receivable' => 'Goods can only be received against an approved Purchase Order with outstanding quantities.',
        'invalid_submission' => 'This submission reference is already in use for a different Purchase Order.',
        'at_least_one_quantity' => 'Enter a received quantity for at least one item.', 'line_not_in_order' => 'One or more selected items do not belong to this Purchase Order.',
        'quantity_must_be_positive' => 'Received quantity must be greater than zero.', 'over_receiving' => 'The received quantity exceeds the remaining quantity on this Purchase Order.',
        'inventory_unavailable' => 'The linked Inventory item is no longer available.', 'classify_inventory_first' => 'Set Track Stock for each linked Inventory item before confirming Goods Received.',
        'whole_stock_quantity' => 'Stock-tracked items must be received in whole-number quantities.', 'inventory_branch_mismatch' => 'A stock-tracked Inventory item must belong to the Purchase Order branch.',
    ],
];
