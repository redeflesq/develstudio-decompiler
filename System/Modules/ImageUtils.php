<?php


namespace System\Modules;


use Core\Factories\Classes\ModuleFactory\Module;
use Core\Factories\Providers\ImageFactory;
use Core\Managers\Providers\FileManager;

class ImageUtils extends Module
{
    protected function vExitWMsg($szMessage)
    {
        ImageFactory::call()->getImage()->vShowMessage($szMessage);
        ImageFactory::call()->getImage()->vExit();
    }

    protected function bRuntimeGetExtension()
    {
        return strtolower(FileManager::call()->register($this->uGetArgs(0))->szGetExtension());
    }

    protected function uCallFunction()
    {
        $args = func_get_args();
        if (sizeof($args) < 1) {
            return false;
        }
        return call_user_func_array($args[0], array_slice($args, 1));
    }

    protected function uGetArgs($i = false)
    {
        $lszArgs = ImageFactory::call()->getImage()->lszGetArgs();
        if ($i != false && isset($lszArgs[$i])) {
            return $lszArgs[$i];
        } else {
            return $lszArgs;
        }
    }

    protected function szGetMaskString()
    {
        $lszArgs = func_get_args();
        if (sizeof($lszArgs) < 2) {
            return "";
        } else {
            $szMask = $lszArgs[0];
            $lszArgs = array_splice($lszArgs, 1);
            if (!is_array($lszArgs)) {
                return "";
            }
            foreach ($lszArgs as $item) {
                if (is_string($item)) {
                    $szMask = str_replace("%s", $item, $szMask);
                } else if (is_numeric($item)) {
                    $szMask = str_replace("%d", $item, $szMask);
                } else if (is_float($item)) {
                    $szMask = str_replace("%f", $item, $szMask);
                }
            }
            return $szMask;
        }
    }
}