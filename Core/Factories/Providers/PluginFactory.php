<?php


namespace Core\Factories\Providers;


use Core\Bootstrap\Classes\Factory;
use Core\Managers\Providers\FileManager;

class PluginFactory extends Factory
{
    public $szPluginsFolder, $szPluginsNamespace;

    public function __construct($szPluginsFolder, $szPluginsNamespace)
    {
        $this->szPluginsFolder = $szPluginsFolder;
        $this->szPluginsNamespace = $szPluginsNamespace;
        parent::__construct(
            function ($szPluginName) {
                $szPluginPath = PluginFactory::call()->szPluginsFolder . "\\" . $szPluginName . ".php";

                $szPluginPath = realpath($szPluginPath);

                if (!$szPluginPath) {
                    return NULL;
                }

                $bInclude = include_once $szPluginPath;
                if ($bInclude) {
                    $szNamespace = PluginFactory::call()->szPluginsNamespace . "\\" . $szPluginName;
                    $lszContainer = &PluginFactory::call()->getContainer();
                    $lszContainer[$szPluginName] = new $szNamespace;
                }
            }
        );
    }

    public function registerPlugins()
    {
        $jDir = FileManager::call()->register($this->szPluginsFolder);

        foreach ($jDir->lszGetFiles() as $jFile) {
            $szName = $jFile->szGetFilename();
            $this->register($szName);
        }
    }
}