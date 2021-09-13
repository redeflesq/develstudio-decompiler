<?php


namespace Core\Managers\Providers;


use Core\Bootstrap\Classes\Manager;
use Core\Managers\Classes\HookManager\Hook;

class HookManager extends Manager
{
    const HOOK_ARRAY_INPUT = 0x1;

    public function __construct()
    {
        parent::__construct(
            function ($szName, $fMethod) {
                $lszContainer =& HookManager::call()->getContainer();
                $lszContainer[$szName] = new Hook($fMethod);
            }
        );
    }
}