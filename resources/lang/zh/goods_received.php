<?php
return [
    'title' => '已收货', 'breadcrumb' => '已收货', 'record_receipt' => '登记收货', 'approve_purchase_order' => '批准采购订单', 'approved_by' => '批准人',
    'receipt_number' => '收货编号', 'purchase_order' => '采购订单', 'supplier' => '供应商', 'branch' => '分支机构',
    'received_date' => '收货日期', 'received_by' => '收货人', 'notes' => '备注', 'item' => '项目', 'ordered_quantity' => '订购数量',
    'previously_received' => '此前已收', 'remaining_quantity' => '剩余数量', 'received_quantity' => '本次收货数量', 'unit' => '单位',
    'agreed_price' => '约定单价', 'status' => '状态', 'actions' => '操作', 'draft' => '草稿', 'confirmed' => '已确认',
    'save_draft' => '保存草稿', 'confirm' => '确认收货', 'view' => '查看', 'back' => '返回', 'empty' => '暂无收货记录。',
    'draft_help' => '草稿不会更改库存或采购订单状态。', 'confirm_help' => '确认后将同时更新符合条件的库存数量和采购订单状态。',
    'messages' => ['draft_saved' => '收货草稿已保存。', 'confirmed' => '收货已确认。', 'purchase_order_approved' => '采购订单已批准，可以收货。'],
    'errors' => ['only_draft_can_be_approved' => '只能批准草稿采购订单。', 'order_requires_items' => '批准前请至少添加一个项目。', 'order_not_receivable' => '只能针对已批准且仍有未收数量的采购订单收货。', 'invalid_submission' => '此提交编号已用于其他采购订单。', 'at_least_one_quantity' => '请至少为一个项目输入收货数量。', 'line_not_in_order' => '所选项目不属于此采购订单。', 'quantity_must_be_positive' => '收货数量必须大于零。', 'over_receiving' => '收货数量超过采购订单剩余数量。', 'inventory_unavailable' => '关联库存项目已不可用。', 'classify_inventory_first' => '确认前请为关联库存项目设置库存跟踪。', 'whole_stock_quantity' => '跟踪库存的项目必须按整数数量收货。', 'inventory_branch_mismatch' => '跟踪库存的项目必须属于采购订单分支机构。'],
];
