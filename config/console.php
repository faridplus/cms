<?php
return [
    'id'        => 'kalpok_console',
    'bootstrap' => ['log'],
    'basePath' => dirname(__DIR__),
    'controllerMap' => [
        'rbac' => 'modules\user\install\console\RbacController',
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationPath' => '@modules/nad/install/migrations'
        ],
    ],
    'components' => [
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning']
                ]
            ]
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
        'db' => require(__DIR__ . '/local/db.php')
    ],
    'aliases' => [
        '@config' => '@app/config',
        '@themes' => '@app/themes',
        '@modules' => '@app/modules',
        '@nad' => '@app/modules/nad'
    ],
    'params' => [
        'adminEmail' => 'admin@example.com',
    ]
];
