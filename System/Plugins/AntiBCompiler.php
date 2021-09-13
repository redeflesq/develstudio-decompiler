<?php


namespace System\Plugins;


use Core\Factories\Classes\PluginFactory\Plugin;
use Core\Factories\Providers\DecompilerFactory;
use Core\Factories\Providers\ModuleFactory;
use Core\Factories\Providers\PluginFactory;
use Core\Managers\Classes\FileManager\Objects\Undefined;
use Core\Managers\Providers\HookManager;

class AntiBCompiler extends Plugin //For Default Decompiler
{
    const BCOMPILER = '$_EXEVFILE';

    public $bIsBCompiler;

    public function __construct()
    {
        $this->bIsBCompiler = false;

        HookManager::call()->register(
            "System->Decompilers->DefaultDecompiler->szStringForceDecode",
            function ($szString) {
                $g_Plugin =& PluginFactory::call()->get("AntiBCompiler");
                begin:
                $szString = DecompilerFactory::call()->get("DefaultDecompiler")->_hszStringForceDecode($szString);
                if (is_string($szString) && $g_Plugin->bIsBCompiler($szString)) {
                    $szString = $g_Plugin->szRemoveBCompiler($szString);
                    $g_Plugin->bIsBCompiler = true;
                    goto begin;
                }
                return $szString;
            }
        );

        HookManager::call()->register(
            "System->Decompilers->DefaultDecompiler->vFindEventData",
            function () {
                $g_Plugin =& PluginFactory::call()->get("AntiBCompiler");
                $g_Decompiler =& DecompilerFactory::call()->get("DefaultDecompiler");
                $g_Decompiler->_hvFindEventData();

                if (!$g_Plugin->bIsBCompiler) {
                    return;
                } else if (!isset($g_Decompiler->g_Sections[AntiBCompiler::BCOMPILER]) || !is_string($g_Decompiler->g_Sections[AntiBCompiler::BCOMPILER])) {
                    return;
                }

                $g_DVS =& $g_Decompiler->jDVS;
                $g_EVF =& $g_Decompiler->g_Sections[AntiBCompiler::BCOMPILER];

                foreach ($g_DVS->EventData as $lszFormsI => $lszFormsV) {
                    foreach ($lszFormsV as $lszObjectsI => $lszObjectsV) {
                        foreach ($lszObjectsV as $lszActionsI => $szActionsV) {
                            if (is_string($szActionsV)) {
                                $lszActionsV = explode("\n", $szActionsV);

                                $lszMessage = array_slice($lszActionsV, 0, 2); //Example: "#ReDecompiler x.0alpha -> DefaultDecompiler"
                                $lszMessage[] = "#bcompiler - true";
                                $lszMessage[] = "\n";

                                $lszActionsV = array_slice($lszActionsV, 3); //Get action body
                                $szActionsV = implode($lszActionsV);

                                $lszAction = explode("::", $szActionsV);
                                $lszAction[0] = explode("_", $lszAction[0]);
                                $lszAction[1] = explode("(", $lszAction[1]);
                                $lszAction[1] = $lszAction[1][0];
                                $lszAction[0] = array_slice($lszAction[0], 4);

                                $szForm = $lszAction[0][0];
                                $szObject = $lszAction[0][1];
                                $szAction = $lszAction[1];

                                $szFormString = "___ev_{$szForm}_$szObject";
                                $szMethodString = "static public function {$szAction}(\$self)";

                                $iClassPos = strpos($g_EVF, $szFormString) + strlen($szFormString);
                                $iMethodPos = strpos($g_EVF, $szMethodString, $iClassPos) + strlen($szMethodString);

                                $iMBrArr = ModuleFactory::call()->get("Tokens")->iGetPtrBetweenTwoCh($g_EVF, "{", "}", $iMethodPos + 2);
                                $szNewMethod = "";

                                for ($i = $iMBrArr[0] + 1; $i < $iMBrArr[1] - 1; $i++) {
                                    $szNewMethod .= $g_EVF[$i];
                                }

                                $szNewMethod = str_replace('eval (enc_getValue("__incCode"));', "", $szNewMethod);
                                $szNewMethod = rtrim(trim($szNewMethod));
                                $szNewMethod = implode("\n", $lszMessage) . $szNewMethod;

                                $g_DVS->EventData[$lszFormsI][$lszObjectsI][$lszActionsI] = $szNewMethod;
                            }
                        }
                    }
                }
            }
        );
    }

    public function szRemoveBCompiler($szString)
    {
        $jTemp = new Undefined(__DIR__ . "/AntiBCompiler/temp");
        $jTemp = $jTemp->setDir();
        $jTemp = $jTemp->jCreateFile(time() . rand(0, 9));
        $jTemp->vWrite($szString);
        $szString = shell_exec('cd ' . __DIR__ . '\AntiBCompiler && php.exe unbcompiler.php "' . $jTemp->szGetPath() . '"');
        $jTemp->vDelete();
        return $szString;
    }

    public function bIsBCompiler($szString)
    {
        $szStringBC = "bcompiler v0.27s";
        if (substr($szString, 4, strlen($szStringBC)) == $szStringBC) {
            return true;
        } else {
            return false;
        }
    }
}