<?php


namespace System;


use Core\Bootstrap\Loader\Classes\Singleton;
use Core\Factories\Providers\DecompilerFactory;
use Core\Factories\Providers\ImageFactory;
use Core\Factories\Providers\ModuleFactory;
use Core\Factories\Providers\PluginFactory;
use Core\Managers\Providers\FileManager;

class Runtime extends Singleton
{
    function __construct()
    {
        $this->vInitializeBootstrapFactory("ModuleFactory");
        $this->vInitializeBootstrapFactory("ImageFactory");

        if (!ModuleFactory::call()->get("RuntimeUtils")->bF2DIsValid()) {
            ModuleFactory::call()->get("ImageUtils")->vExitWMsg("File to decompile isn't valid!");
        }

        $this->vInitializeBootstrapFactory("PluginFactory");
        $this->vInitializeBootstrapFactory("DecompilerFactory");

        ImageFactory::call()->getImage()->vShowMessage(RD_CAPTION . "\nGithub: https://github.com/redeflesq/ReDecompiler\n");
        DecompilerFactory::call()->getDecompiler()->vDecompile();
        ImageFactory::call()->getImage()->vExit();
    }

    function vInitializeBootstrapFactory($szInit)
    {
        $szMainFolder = FileManager::call()->szGetMainFolder(__DIR__);

        switch ($szInit) {
            case "ImageFactory":
                ImageFactory::call(
                    "{$szMainFolder}\\System\\Images",
                    "System\\Images"
                );
                ImageFactory::call()->registerImages();
                break;
            case "ModuleFactory":
                ModuleFactory::call(
                    "{$szMainFolder}\\System\\Modules",
                    "System\\Modules"
                );
                ModuleFactory::call()->registerModules();
                break;
            case "DecompilerFactory":
                DecompilerFactory::call(
                    "{$szMainFolder}\\System\\Decompilers",
                    "System\\Decompilers"
                );
                DecompilerFactory::call()->registerDecompilers();
                break;
            case "PluginFactory":
                PluginFactory::call(
                    "{$szMainFolder}\\System\\Plugins",
                    "System\\Plugins"
                );
                PluginFactory::call()->registerPlugins();
                break;
        }
    }

    /*function vSimpleInitializeBootstrapFactory($szName)
    {
        $szClass = "{$szName}Factory";
        $szMainFolder = FileManager::call()->szGetMainFolder(__DIR__);
        $szClass::call(
            "{$szMainFolder}\\System\\{$szName}s",
            "System\\{$szName}s"
        );
        call_user_func_array(array($szClass::call(), "register{$szName}s"), array());
    }*/
}