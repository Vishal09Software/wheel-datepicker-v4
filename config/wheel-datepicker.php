<?php

return [
    // Year range shown in the year wheel when a component omits year-start/year-end.
    'year_start' => 1990,
    'year_end' => now()->year + 20,

    // PHP date() format used when re-parsing the ISO value the native side sends back.
    'format' => 'Y-m-d',

    // Theme tokens - override in your published config to re-skin every picker at once.
    // Each is a hex color string, read natively via the `color_*` props.
    'theme' => [
        'bg' => '#0e1324',
        'card' => '#151b30',
        'border' => '#232a45',
        'text' => '#e7eaf5',
        'muted' => '#5b6280',
        'muted_2' => '#3a4160',
        'accent' => '#7c9bff',
    ],
];
