<?php


namespace System\Plugins\Console;


use Core\Bootstrap\Loader\Classes\Singleton;
use Core\Factories\Providers\ImageFactory;

class VCLHelper extends Singleton
{
    private $Form, $Parameters;

    public function vSetForm(&$Form)
    {
        $this->Form = $this->uGetForm($Form);
        $this->Parameters = array(
            'x' => 0,
            'y' => 0,
            'w' => 0,
            'h' => 0,
            'enabled' => false,
            'clientWidth' => 0,
            'clientHeight' => 0,
            'alphaBlendValue' => 0,
            'alphaBlend' => false,
            'onShow' => function() {},
            'borderIcons' => $this->uGetConstant("blMinimize"),
            'borderStyle' => $this->uGetConstant("bsNone"),
            'borderWidth' => 1,
            'formStyle' => $this->uGetConstant("fsNormal")
        );
    }

    private function uGetConstant($szConstant)
    {
        if(!defined($szConstant)){
            return NULL;
        } else {
            return constant($szConstant);
        }
    }

    public function bPushParameters()
    {
        if (!$this->Form) {
            return false;
        }
        $keys = array_keys($this->Parameters);
        foreach ($keys as $key){
            $this->Parameters[$key] = $this->Form->{$key};
        }
        return true;
    }

    public function bPopParameters()
    {
        if (!$this->Form) {
            return false;
        }
        foreach ($this->Parameters as $item => $value){
            $this->Form->{$item} = $value;
        }
        return true;
    }

    public function bHide($SetupMDI = true)
    {
        $Form =& $this->Form;
        if (!$Form) {
            return false;
        }
        $this->bPushParameters();
        $Form->x = 0;
        $Form->y = 0;
        $Form->w = 1;
        $Form->h = 1;
        $Form->enabled = false;
        $Form->clientWidth = 1;
        $Form->clientHeight = 1;
        $Form->alphaBlendValue = 0;
        $Form->alphaBlend = true;
        $Form->onShow = function () use ($Form) {
            $Form->hide();
        };
        $Form->borderIcons = $this->uGetConstant("biMinimize");
        $Form->borderStyle = $this->uGetConstant("bsNone");
        $Form->borderWidth = 1;
        if ($SetupMDI) {
            $Form->formStyle = $this->uGetConstant("fsMDIChild");
        }
        $Form->hide();
        return true;
    }

    public function vShow()
    {
        $this->bPopParameters();
        $this->Form->show();
    }

    public function bRestoreMDI()
    {
        $Form = $this->Form;
        if (!$Form) {
            return false;
        }
        $Form->formStyle = $this->uGetConstant("fsNormal");
        return true;
    }

    public function uGetForm($_Form)
    {
        $Form = NULL;
        if (is_string($_Form)) {
            $Form = ImageFactory::call()->getImage()->uCallFunction("c", $_Form);
        } elseif (is_object($_Form)) {
            $Form = $_Form;
        } else {
            return false;
        }
        return $Form;
    }
}