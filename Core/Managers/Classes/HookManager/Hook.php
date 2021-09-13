<?php


namespace Core\Managers\Classes\HookManager;


use Core\Managers\Providers\HookManager;

class Hook
{
    private $lszMethods;

    public function __construct($fMethod)
    {
        $this->lszMethods = array($fMethod);
    }

    public function call()
    {
        $lszArgs = func_get_args();

        if (sizeof($lszArgs) == 2 && is_array($lszArgs[1]) && $lszArgs[0] == HookManager::HOOK_ARRAY_INPUT) {
            $lszArgs = $lszArgs[1];
        }

        foreach ($this->lszMethods as $iExec => $fExec) {
            $uReturn = call_user_func_array($fExec, $lszArgs);
            if ($iExec == sizeof($this->lszMethods) - 1) {
                return $uReturn;
            } else {
                if (!is_array($uReturn) || is_null($uReturn)) {
                    $uReturn = array_pad(array(), sizeof($lszArgs), NULL);
                }
                if (is_array($uReturn) && sizeof($uReturn) == sizeof($lszArgs)) {
                    $lszArgs = $uReturn;
                }
            }
        }
        return NULL;
    }

    public function attach($fValue)
    {
        array_unshift($this->lszMethods, $fValue);
    }

    public function getClosure($iNum = 0)
    {
        return $this->lszMethods[$iNum];
    }

    public function getSize()
    {
        return sizeof($this->lszMethods);
    }

    public function replaceBlank()
    {
        $this->replace(
            function () {
                func_get_args();
            }
        );
    }

    public function replace($fReplace)
    {
        if (is_callable($fReplace)) {
            $this->lszMethods[sizeof($this->lszMethods) - 1] = $fReplace;
        }
    }
}