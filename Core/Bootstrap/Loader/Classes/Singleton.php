<?php


namespace Core\Bootstrap\Loader\Classes;


class Singleton
{
    protected static $lszTrackableObjectInstances = array();

    public static function getAllInstances()
    {
        return self::$lszTrackableObjectInstances;
    }

    public static function getInstances($includeSubclasses = false)
    {
        $return = array();
        foreach(self::$lszTrackableObjectInstances as $instance) {
            $class = get_called_class();
            if($instance instanceof $class) {
                if ($includeSubclasses || (get_class($instance) === $class)) {
                    $return[] = $instance;
                }
            }
        }
        return $return;
    }

    public static function getCountOfInstances()
    {
        $iCount = 0;
        $szClass = get_called_class();
        foreach(self::$lszTrackableObjectInstances as $jInstance) {
            if($jInstance instanceof $szClass && (get_class($jInstance) === $szClass)){
                $iCount++;
            }
        }
        return $iCount;
    }

    public static final function call()
    {
        $szClass = get_called_class();
        if(self::getCountOfInstances() > 1){
            die("Singleton '{$szClass}' size > 1");
        } else {
            static $jInstance = NULL;
            if (!$jInstance) {
                if (method_exists($szClass, "__construct")) {
                    try {
                        $jInstance = new \ReflectionClass($szClass);
                    } catch (\ReflectionException $e) {
                        die("Singleton reflection '{$szClass}' exception!");
                    }
                    $jInstance = $jInstance->newInstanceArgs(func_get_args());
                } else {
                    $jInstance = new $szClass();
                }
            }
            return $jInstance;
        }
    }
}