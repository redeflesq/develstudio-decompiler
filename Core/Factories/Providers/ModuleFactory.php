<?php


namespace Core\Factories\Providers;


use Core\Bootstrap\Classes\Factory;
use Core\Managers\Providers\FileManager;

class ModuleFactory extends Factory
{
    public $szModulesFolder, $szModulesNamespace;

    public function __construct($szModulesFolder, $szModulesNamespace)
    {
        $this->szModulesFolder = $szModulesFolder;
        $this->szModulesNamespace = $szModulesNamespace;

        parent::__construct(
            function ($szName) {

                $szModulePath = ModuleFactory::call()->szModulesFolder . "\\" . $szName . ".php";
                $szModulePath = realpath($szModulePath);

                if (!$szModulePath) {
                    return NULL;
                }

                $bInclude = include $szModulePath;

                if ($bInclude) {
                    $szNamespace = ModuleFactory::call()->szModulesNamespace . "\\" . $szName;
                    $lszContainer = &ModuleFactory::call()->getContainer();
                    $lszContainer[$szName] = new $szNamespace;
                }
            }
        );
    }

    public function registerModules()
    {
        $jDir = FileManager::call()->register($this->szModulesFolder);

        foreach ($jDir->lszGetFiles() as $jFile) {
            $szName = $jFile->szGetFilename();
            $this->register($szName);
        }
    }
}