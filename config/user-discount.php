<?php

return [
    'stacking' => [
        'order' => ['priority_desc', 'percent_desc', 'fixed_desc', 'id_asc'],
        'max_percentage_cap' => 60,
    ],
    'rounding' => 'half_up', // half_up | half_down | bankers
];
