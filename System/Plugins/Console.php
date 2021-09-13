<?php


namespace System\Plugins;


use Core\Factories\Providers\ImageFactory;
use Core\Factories\Providers\ModuleFactory;
use Core\Factories\Providers\PluginFactory;
use Core\Managers\Providers\HookManager;
use System\Images\DevelStudio;
use System\Plugins\Console\FFIHelper;
use System\Plugins\Console\VCLHelper;

class Console
{
    public $lszTemp = array();
    private $bConsoleAllocated = false;

    function __construct()
    {
        if (!(ImageFactory::call()->getImage() instanceof DevelStudio)) {
            return;
        }

        include_once "Console/Container.php";
        include_once "Console/FFIHelper.php";
        include_once "Console/VCLHelper.php";

        $this->vInitMsvcrt();
        $this->vInitKernel32();

        $this->vAllocate();

        HookManager::call()->register(
            "Core->Factories->Classes->ImageFactory->Image->vShowMessage",
            function ($szMessage) {
                $g_Plugin =& PluginFactory::call()->get("Console");
                $g_Plugin->vPrintMessage($szMessage . "\n");
            }
        );


        HookManager::call()->register(
            "Core->Factories->Classes->ImageFactory->Image->vShowArrayMessage",
            function ($szMask, $lszMainArray, $lszOptionals = array()) {
                $g_Plugin =& PluginFactory::call()->get("Console");
                HookManager::call()->register(
                    "Core->Factories->Classes->ModuleFactory->Module->uCallFunction",
                    function () {
                        $lszArgs = func_get_args();
                        $szName = $lszArgs[0];
                        if ($szName == "messageDlg") {
                            $g_Plugin =& PluginFactory::call()->get("Console");
                            $szMessage = $lszArgs[1];
                            $g_Plugin->lszTemp["szMessage"] = $szMessage;
                        } else {
                            return call_user_func_array(array(ModuleFactory::call()->get("ImageUtils"), "_huCallFunction"), $lszArgs);
                        }
                    }
                );
                ImageFactory::call()->getImage()->_hvShowArrayMessage($szMask, $lszMainArray);
                HookManager::call()->free("Core->Factories->Classes->ModuleFactory->Module->uCallFunction");
                ImageFactory::call()->getImage()->vShowMessage($g_Plugin->lszTemp["szMessage"]);
                unset($g_Plugin->lszTemp["szMessage"]);
            }
        );

        HookManager::call()->register(
            "Core->Factories->Classes->ImageFactory->Image->vExit",
            function () {
                $g_Plugin =& PluginFactory::call()->get("Console");
                $g_Plugin->vPrintMessage("Decompile end\n");
                VCLHelper::call()->vSetForm($GLOBALS["mainForm"]);
                VCLHelper::call()->bHide();
                FFIHelper::call()->lszLibraries->msvcrt->system("pause");
                $g_Plugin->vFree();
                VCLHelper::call()->bRestoreMDI();
                ImageFactory::call()->getImage()->_hvExit();
                return;
            }
        );
    }

    public function vSetTitle($szTitle)
    {
        FFIHelper::call()->lszLibraries->kernel32->SetConsoleTitleA(
            $szTitle
        );
    }

    public function vFree()
    {
        if ($this->bConsoleAllocated) {
            $this->bConsoleAllocated = false;
            FFIHelper::call()->lszLibraries->kernel32->FreeConsole();
        }
    }

    public function vAllocate()
    {
        if (!$this->bConsoleAllocated) {
            $this->bConsoleAllocated = true;
            FFIHelper::call()->lszLibraries->kernel32->AllocConsole();
        }
    }

    private function iGetHandle()
    {
        return FFIHelper::call()->lszLibraries->kernel32->GetStdHandle(STD_OUTPUT_HANDLE);
    }

    public function vSetTextColor($iColor)
    {
        FFIHelper::call()->lszLibraries->kernel32->SetConsoleTextAttribute(
            $this->iGetHandle(),
            $iColor
        );
    }

    public function vClearConsole()
    {
        FFIHelper::call()->lszLibraries->msvcrt->system("cls");
    }

    public function vPrintMessage($szMessage)
    {
        FFIHelper::call()->lszLibraries->kernel32->WriteConsoleA(
            $this->iGetHandle(),
            $szMessage,
            strlen($szMessage),
            NULL,
            NULL
        );
    }

    private function vInitMsvcrt()
    {
        FFIHelper::call()->vAttachDll(
            "msvcrt",
            "msvcrt.dll",
            array(
                "int system(char* _Command);",
                "int puts(char* str);"
            )
        );
        FFIHelper::call()->vBuildLibrary("msvcrt");
    }

    private function vInitKernel32()
    {
        FFIHelper::call()->vAttachDll(
            "kernel32",
            "kernel32.dll",
            array(
                "BOOL WINAPI AllocConsole();",
                "BOOL FreeConsole();",
                "BOOL WINAPI AttachConsole(DWORD dwProcessId);",
                "HANDLE WINAPI GetStdHandle(DWORD nStdHandle);",
                "BOOL SetConsoleTextAttribute(HANDLE hConsoleOutput, WORD wAttributes);",
                "BOOL WINAPI WriteConsoleA(HANDLE hConsoleOutput, char* lpBuffer, DWORD nNumberOfCharsToWrite, LPDWORD lpNumberOfCharsWritten, LPVOID lpReserved);",
                "int SetConsoleTitleA(char* lpConsoleTitle);",
                "DWORD GetProcessId(HANDLE Process);"
            )
        );
        FFIHelper::call()->vBuildLibrary("kernel32");
    }
}