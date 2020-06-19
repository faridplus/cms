<?php
namespace extensions\jstree\widgets;

use yii\web\AssetBundle;

class JsTreeAssetBundle extends AssetBundle
{
    public $sourcePath = __DIR__ . '/assets/';
    public $css = [
        'themes/default/style.min.css',
    ];
    public $js = [
        'jstree.min.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
        // 'yii\bootstrap\BootstrapAsset',
    ];
}
