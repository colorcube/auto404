<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'auto404',
    'description' => 'Page Not Found handling without configuration. Handle redirect to other domains when pages were moved.',
    'category' => 'fe',
    'author' => 'René Fritz',
    'author_email' => 'r.fritz@colorcube.de',
    'author_company' => 'Colorcube',
    'version' => '1.0.0',
    'state' => 'stable',
    'constraints' => [
        'depends' => [
            'typo3' => '7.6.0-8.99.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Colorcube\\Auto404\\' => 'Classes'
        ]
    ]
];
