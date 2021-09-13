<?php


namespace System\Modules;


use Core\Factories\Classes\ModuleFactory\Module;
use Core\Factories\Providers\ImageFactory;
use Core\Managers\Classes\FileManager\Objects\File;
use Core\Managers\Providers\FileManager;

class RuntimeUtils extends Module
{
    public function bF2DIsValid() // bool - file to decompile is valid
    {
        $lszArgs = ImageFactory::call()->getImage()->lszGetArgs();

        if (!isset($lszArgs[1])) {
            return false;
        }

        $jFile = FileManager::call()->register($lszArgs[1]);

        if (!($jFile instanceof File)) {
            return false;
        } else if (!$jFile->bExist()) {
            return false;
        } else if ($jFile->szGetExtension() != "exe") {
            return false;
        }

        return true;
    }
}