<?php
use yii\web\View;

$this->registerJS("$(function(){
    $('#{$this->context->containerId}').jstree({$this->context->clientOptions});
});", View::POS_END, "jstree-handler");