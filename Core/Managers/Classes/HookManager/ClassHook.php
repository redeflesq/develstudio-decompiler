<?php


namespace Core\Managers\Classes\HookManager;


use Core\Managers\Providers\HookManager;

class ClassHook
{
    protected $szClass;

    function __construct($szClass = NULL)
    {
        $this->szClass = (($szClass === NULL) ? __CLASS__ : $szClass);
    }

    function __call($szName, $lszArgs)
    {
        //var_dump(array("Class" => $this->szClass, "Name" => $szName));
        $szHookName = str_replace("\\", "->", $this->szClass) . "->" . $szName;

        $bIsHookCall = false;
        if (substr($szName, 0, 2) == "_h") {
            $bIsHookCall = true;
            $szName = substr($szName, 2);
        }
        if (HookManager::call()->exist($szHookName) && !$bIsHookCall) {
            return HookManager::call()->get($szHookName)->call(HookManager::HOOK_ARRAY_INPUT, $lszArgs);
        } else {
            return call_user_func_array(array(&$this, $szName), $lszArgs);
        }
    }
}