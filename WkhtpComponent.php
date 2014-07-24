<?php

class WkhtpComponent extends CApplicationComponent
{
    public $mode = 'server';
    
    public $clientUrl = null;
    
    public function init()
    {
        parent::init();
        Yii::import('vendor.crisu83.yii-extension.behaviors.*');
        $this->attachBehavior('ext', new ComponentBehavior);
    }
    
    public function factory($runtimePath) {
        Yii::import('vendor.ivko.yii-wkhtmltopdf.*');
        
        if ($this->mode == 'client') {
            return new WkhtpClient($runtimePath, $this->clientUrl);
        }
        return new Wkhtmltopdf($runtimePath);
    }
}
