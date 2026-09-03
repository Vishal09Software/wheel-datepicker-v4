<?php

return [
    'year_start' => 1990,
    'year_end' => null,
    'format' => 'Y-m-d',
    /*
    | Leave a key null/empty to inherit config/native-ui.php light tokens
    | (surface, outline, primary, …). Set a hex here only to override.
    */
    'theme' => [
        'bg' => null,
        'card' => null,
        'border' => null,
        'text' => null,
        'muted' => null,
        'muted_2' => null,
        'accent' => null,
    ],

    /*
    | Density-independent dp/pt. Same physical size on high-DPI phones.
    | For tablets, raise row_height / visible_items in this file or per tag.
    | visible_items is clamped to 3, 5, or 7 so the centre row stays aligned.
    */
    'size' => [
        'row_height' => 44,
        'visible_items' => 5,
        'wheel_height' => null,
        'card_padding' => 16,
        'corner_radius' => 20,
        'selected_font' => 20,
        'muted_font' => 15,
    ],
];
