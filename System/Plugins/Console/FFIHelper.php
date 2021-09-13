<?php


namespace System\Plugins\Console;


use Core\Bootstrap\Loader\Classes\Singleton;
use Core\Factories\Providers\ImageFactory;
use Core\Factories\Providers\ModuleFactory;

class FFIHelper extends Singleton
{
    private $lszHeadLibraries, $lszBodyLibraries;
    public $lszLibraries;

    public function __construct()
    {
        define('STD_INPUT_HANDLE', 0xFFFFFFF6);
        define('STD_OUTPUT_HANDLE', 0xFFFFFFF5);
        define('STD_ERROR_HANDLE', 0xFFFFFFF4);
        define('INVALID_HANDLE_VALUE', 0xFFFFFFFF);
        define('FOREGROUND_BLUE', 0x0001);
        define('FOREGROUND_GREEN', 0x0002);
        define('FOREGROUND_RED', 0x0004);
        define('FOREGROUND_INTENSITY', 0x0008);
        define('BACKGROUND_BLUE', 0x0010);
        define('BACKGROUND_GREEN', 0x0020);
        define('BACKGROUND_RED', 0x0040);
        define('BACKGROUND_INTENSITY', 0x0080);

        $this->lszHeadLibraries = array();
        $this->lszBodyLibraries = array();
        $this->lszLibraries = new \Container();
    }

    public function vAttachDll($szUseName, $szHeaderName, $lszFuncList = array())
    {
        $this->lszHeadLibraries[$szUseName] = $szHeaderName;
        $this->lszBodyLibraries[$szUseName] = $lszFuncList;
    }

    public function vAttachFunction($szName, $szFuncHeader)
    {
        $this->lszBodyLibraries[$szName][] = $szFuncHeader;
    }

    public function vBuildLibrary($szName)
    {
        $szFFI = "[lib='" . $this->lszHeadLibraries[$szName] . "']\n";
        foreach ($this->lszBodyLibraries[$szName] as $szLib) {
            $szFFI .= $szLib . "\n";
        }

        if(!class_exists("FFI")){
            ModuleFactory::call()->get("ImageUtils")->vExitWMsg("FFIHelper - class FFI not exists");
        }

        $jInstance = new \ReflectionClass("FFI");
        $this->lszLibraries->{$szName} = $jInstance->newInstanceArgs(array($this->szFFIFix($szFFI)));
    }

    public function szFFIFix($szString)
    {
        $lszTypes = array(
            "szNone" => " ",
            "szInt" => " int ",
            "szString" => " char* "
        );
        $lszReplace = array(
            'BOOLEAN ' => $lszTypes["szInt"],
            'BOOL ' => $lszTypes["szInt"],
            'LPDWORD ' => $lszTypes["szInt"],
            'DWORD ' => $lszTypes["szInt"],
            'BYTE ' => $lszTypes["szInt"],
            'INTEGER ' => $lszTypes["szInt"],
            'STRING ' => $lszTypes["szString"],
            'UINT ' => $lszTypes["szInt"],
            'CARDINAL ' => $lszTypes["szInt"],
            'SHORT ' => $lszTypes["szInt"],
            'LPSTR ' => $lszTypes["szString"],
            'LPCSTR ' => $lszTypes["szString"],
            'LPCTSTR ' => $lszTypes["szString"],
            'LPVOID ' => $lszTypes["szInt"],
            'LCID ' => $lszTypes["szInt"],
            'HWND ' => $lszTypes["szInt"],
            'WINAPI ' => $lszTypes["szNone"],
            'HANDLE ' => $lszTypes["szInt"],
        );

        return str_ireplace(array_keys($lszReplace), array_values($lszReplace), $szString);
    }
}