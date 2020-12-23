<?php


class _PerfectConsole
{
    private $FFI;

    function __construct()
    {
        $this->FFI = _FFI::_Loader();
    }

    function __invoke()
    {
        return $this->FFI;
    }

    function GetKeyState($key)
    {
        $state = $this->FFI->user32("GetAsyncKeyState", array(
            $key
        ));
        return $state;
    }

    function Echof($msg)
    {
        return $this->FFI->msvcrt("system", array(
            "echo " . $msg
        ));
    }

    function Printf()
    {
        $args = func_get_args();
        $stdoutput_handle = $this->FFI->kernel32("GetStdHandle", array(
            STD_OUTPUT_HANDLE
        ));
        foreach ($args as $string) {
            $this->FFI->kernel32("WriteConsoleA", array(
                $stdoutput_handle,
                $string,
                strlen($string),
                NULL,
                NULL
            ));
        }
    }

    function SetTitle($title)
    {
        return $this->FFI->kernel32("SetConsoleTitleA", array(
            $title
        ));
    }

    function Allocate()
    {
        return $this->FFI->kernel32("AllocConsole");
    }

    function Free()
    {
        return $this->FFI->kernel32("FreeConsole");
    }
}