<?php

namespace modules\user\backend;

class Module extends \yii\base\Module
{
    public $title;
    public $menu;
    public $defaultRoute = 'manage/index';
    public $controllerNamespace = 'modules\user\backend\controllers';
    public $controllerMap = [
        'auth' => [
           'class' => 'modules\user\common\controllers\AuthController',
           'layout' => '//login',
        ],
    ];

    public function init()
    {
        parent::init();
        \Yii::configure($this, require(__DIR__ . '/config.php'));
    }
}
