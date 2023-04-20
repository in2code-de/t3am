<?php

$EM_CONF['t3am'] = [
    'title' => 'T3AM',
    'description' => 'T3AM - TYPO3 Authentication Manager',
    'category' => 'services',
    'state' => 'stable',
    'author' => 'Oliver Eglseder',
    'author_email' => 'php@vxvr.de',
    'author_company' => 'in2code GmbH',
    'version' => '5.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.3.0-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
