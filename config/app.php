<?php

return [
    'name' => 'Inventory System',
    'base_url' => '',
    'session_name' => 'INV_SESS_ID',
    'default_role' => 'staff',
    'items_per_page' => 10,
    'allowed_sort_columns' => [
        'name' => 'p.name',
        'sku' => 'p.sku',
        'price' => 'p.price',
        'stock' => 'p.stock',
        'category' => 'c.name',
        'created_at' => 'p.created_at',
    ],
    'allowed_sort_directions' => ['ASC', 'DESC'],
];
