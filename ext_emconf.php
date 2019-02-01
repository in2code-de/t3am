<?php
$EM_CONF['t3am'] = [
    'title' => 'T3AM',
    'description' => 'T3AM - TYPO3 Authentication Manager',
    'category' => 'services',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Oliver Eglseder',
    'author_email' => 'php@vxvr.de',
    'author_company' => 'in2code GmbH',
    'version' => '1.1.0-dev',
    'constraints' => [
        'depends' => [
            'typo3' => '7.6.0-9.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
