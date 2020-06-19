<?php
namespace extensions\jstree\widgets;

use RecursiveIteratorIterator;
use RecursiveArrayIterator;
use yii\base\Widget;
use yii\helpers\Json;
use yii\base\InvalidParamException;

class JsTree extends Widget
{
    public $view = 'default';
    public $containerId = 'jstree_container';
    public $dataArray = [];
    public $clientOptions = [
        'plugins' => ['checkbox', 'search'],
        // 'themes' => [
        //     'responsive' => false,
        //     'variant' => 'small',
        //     'stripes' => true
        // ]
    ];

    public function init()
    {
        parent::init();

        if(!is_array($this->clientOptions)){
            throw new InvalidParamException("`\$clientOptions` property must be an array.");
        }
        if(!is_array($this->dataArray)){
            throw new InvalidParamException("`\$dataArray` property must be an array.");
        }

        JsTreeAssetBundle::register($this->getView());

        if (!empty($this->dataArray)) {
            if(!isset($this->clientOptions['core'])){
                $this->clientOptions['core'] = [];
            }
            $this->clientOptions['core']['data'] = $this->dataArray;
        }
        $this->clientOptions = Json::encode($this->clientOptions);
    }

    public function run()
    {
        return $this->render(
            $this->view
        );
    }

    /**
     * Not used, maybe later
     *
     */
    private function recursiveIconInjection(){
        foreach ($this->dataArray as $item) {
            if(is_array($item)){
                $item['icon'] = $this->defaultIcon;
                return recursiveIconInjection($item['children']);
            }else{
                return;
            }
        }
    }
}