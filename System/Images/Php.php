<?php

namespace System\Images;

use Core\Factories\Classes\ImageFactory\Image;
use Core\Factories\Providers\ModuleFactory;

class Php extends Image
{
    protected function vExit()
    {
        die();
    }

    protected function lszGetArgs()
    {
        if (isset($GLOBALS["argv"])) {
            return $GLOBALS["argv"];
        } else {
            return array();
        }
    }

    protected function bDetect()
    {
        return true;
    }

    protected function vShowArrayMessage($szMask, $lszMainArray, $lszOptionals = array())
    {
        ob_start();
        foreach ($lszMainArray as $iIndex => $szMessage) {
            $this->vShowMessage(ModuleFactory::call()->get("ImageUtils")->szGetMaskString($szMask, $iIndex, $szMessage));
            //call_user_func_array(array($this, "szGetMaskString"), array_merge(array($szMask, $iIndex, $szMessage), $lszOptionals));
        }
        $this->vShowMessage(ob_get_clean());
    }

    protected function vShowMessage($szMessage, $szBr = "\n")
    {
        print($szMessage . $szBr);
    }
}