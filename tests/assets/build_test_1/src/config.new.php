<?php

return [
    'debug'  => false,
    'kernel' => \SiteOne\Kernel::class,
    'site'   => [
        'title'       => 'redirect-plugin.',
        'url'         => 'http://localhost:3000',
    ],
    'content_types' => [
        'blog' => [
            'permalink'  => 'blog/test/{year}/{slug}.html',
        ],
    ],
];
