<?php


namespace System\Decompilers;


use Core\Factories\Classes\DecompilerFactory\Decompiler;
use Core\Factories\Classes\DecompilerFactory\DVS;
use Core\Factories\Providers\DecompilerFactory;
use Core\Factories\Providers\ImageFactory;
use Core\Factories\Providers\ModuleFactory;
use Core\Managers\Classes\FileManager\Objects\File;
use Core\Managers\Providers\FileManager;
use Core\Managers\Providers\HookManager;
use System\Images\DevelStudio;

class DefaultDecompiler extends Decompiler //Choosing decompilers in development
{
    public $g_EM, $g_Sections;

    function __construct()
    {
        parent::__construct(__CLASS__);

        $this->g_EM =& ModuleFactory::call()->get("ExeMod");
        $this->g_EM->vSetFile();

        $this->g_Sections = ModuleFactory::call()->get("ExeMod")->lszGetAllSectionsData();

        HookManager::call()->register(
            "System->Decompilers->DefaultDecompiler->vDecompile",
            function ($szOutput = NULL) {
                $g_Decompiler =& DecompilerFactory::call()->get("DefaultDecompiler");

                if (!$szOutput) {
                    $lszArgs = ImageFactory::call()->getImage()->lszGetArgs();
                    if (!isset($lszArgs[1])) {
                        return;
                    }
                    $jOutput = FileManager::call()->register($lszArgs[1]);
                    if (!($jOutput instanceof File)) {
                        return;
                    }
                    $szOutput = "{$jOutput->szGetDir()}\\{$jOutput->szGetFilename()}.dvs";
                }

                ImageFactory::call()->getImage()->vShowMessage("Total sections: " . sizeof($g_Decompiler->g_Sections));
                foreach ($g_Decompiler->g_Sections as $szSectionItem => $szSectionValue) {
                    $g_Decompiler->g_Sections[$szSectionItem] = $g_Decompiler->szStringForceDecode($szSectionValue);
                }
                ImageFactory::call()->getImage()->vShowArrayMessage("Section was founded (%d) - '%s'", array_keys($g_Decompiler->g_Sections), range(0, 16));
                DecompilerFactory::call()->get("DefaultDecompiler")->_hvDecompile($szOutput);
            }
        );

    }

    protected function vFindScripts()
    {
        $DVS =& DecompilerFactory::call()->getDecompiler()->jDVS;
        $szfModule = "";
        $szlModule = "";
        $szbModule = false;
        if (isset($this->g_Sections[Decompiler::SCRIPTS])) {
            ImageFactory::call()->getImage()->vShowMessage("Find Scripts ['" . Decompiler::SCRIPTS . "']");

            $g_Modules =& $this->g_Sections[Decompiler::SCRIPTS];
            $g_Modules = str_replace("?><?php", "?><?", $g_Modules);

            $lszModules = array();
            $lszScripts = array();

            foreach (explode("?><?", $g_Modules) as $iModule => $szModule) {
                if (substr($szModule, 0, 2) == "<?") {
                    $szModule = substr($szModule, 2);
                }

                if (substr($szModule, 0, 5) != "<?php") {
                    $szModule = "<?php " . $szModule;
                }

                $szScriptName = $this->uGetScriptName($szModule);
                $iStartFormatting = microtime(true);

                if (is_string($szModule)) {
                    $szBkpModule = $szModule;
                    $szModule = $this->uPrettyFormatCode($szModule);
                    if ($szModule == false) {
                        if (!$szfModule) {
                            $szfModule = $szlModule;
                        }
                        $lszModules[$szfModule . ".php"] .= $szBkpModule;
                        $szbModule = true;
                        continue;
                    } elseif ($szbModule == true) {
                        $lszModules[$szfModule . ".php"] = $this->uPrettyFormatCode($lszModules[$szfModule . ".php"], 2);
                        $szfModule = "";
                        $szbModule = false;
                    }
                }

                $lszScripts[] = (
                    "SI[{$iModule}]:" .
                    "SN['{$szScriptName}']:" .
                    "SO[" . strlen($szModule) . "b]:" .
                    "FM[" . substr(floatval(microtime(true) - $iStartFormatting), 0, 5) . "s]"
                );
                $lszModules[$szScriptName . ".php"] = $szModule;
                $szlModule = $szScriptName;
            }

            $DVS->Scripts = $lszModules;
            ImageFactory::call()->getImage()->vShowArrayMessage("Script was founded - '%s'", $lszScripts);
        }
    }

    protected function uGetScriptName($szScript)
    {
        if (substr($szScript, 0, 5) != "<?php" && substr($szScript, 0, 2) != "<?") {
            return false;
        }

        $lszTokens = token_get_all($szScript);
        $iToken = T_CLASS;
        $iIndex = 0;

        begin:

        $szName = "";
        foreach ($lszTokens as $i => $token) {
            if (is_array($token)) {
                if ($token[0] == $iToken) {
                    $szName = $lszTokens[ModuleFactory::call()->get("Tokens")->vtSubToken($lszTokens, $i, T_STRING, 3)][1];
                }
            }
        }

        if (!$szName && $iIndex < 3) {
            $iToken = T_FUNCTION;
            $iIndex++;
            goto begin;
        }

        return $szName;
    }

    protected function uPrettyFormatCode($szCode, $iTimes = 3)
    {

        $szBkp = $szCode;
        $iBkp = 0;

        formate:
        $szCode = $this->uFormatCode($szCode);
        if (is_array($szCode)) {
            return false;
        }
        if ((is_bool($szCode) || strlen($szCode) == 0)) {
            if ($iBkp < $iTimes) {
                $iBkp++;
                goto formate;
            } else {
                $szCode = $szBkp;
            }
        }

        return $szCode;
    }

    protected function uFormatCode($szCode)
    {
        $lszParameters = array(
            "spaces_around_map_operator" => true,
            "spaces_around_assignment_operators" => true,
            "spaces_around_bitwise_operators" => true,
            "spaces_around_relational_operators" => true,
            "spaces_around_equality_operators" => true,
            "spaces_around_logical_operators" => true,
            "spaces_around_math_operators" => true,
            "rewrite_short_tag" => true,
            "space_after_structures" => true,
            "align_assignments" => true,
            "indent_case_default" => true,
            "indent_number" => 4,
            "first_indent_number" => 0,
            "indent_char" => "\t",
            "charset" => "utf-8",
            "indent_style" => "PEAR",
            "code" => iconv("CP1251", "UTF-8", $szCode)
        );

        $szText = $this->szSocketQuery(
            "phpformatter.com",
            $lszParameters
        );

        $szText = trim(strrev($szText));
        $lszPointers = ModuleFactory::call()->get("Tokens")->iGetPtrBetweenTwoCh($szText, "}", "{");
        $szReceive = "";

        for ($i = $lszPointers[0]; $i < $lszPointers[1] + 1; $i++) {
            $szReceive .= $szText[$i];
        }

        $szReceive = strrev($szReceive);
        $szReceive = json_decode($szReceive, true);

        if (!$szReceive || !is_array($szReceive)) {
            //ImageFactory::call()->getImage()->vShowMessage("Formatting code error: " . serialize($szReceive));
            return false;
        }

        if (!isset($szReceive["plainoutput"])) {
            return $szReceive;
        } else {
            return $szReceive["plainoutput"];
        }
    }

    protected function szSocketQuery($szHost, $lszParameters, $iLineMaxSize = 256)
    {
        $fp = fsockopen($szHost, 80);
        $szContent = http_build_query($lszParameters);
        fwrite($fp, "POST /Output HTTP/1.1\r\n");
        fwrite($fp, "Host: $szHost\r\n");
        fwrite($fp, "Content-Type: application/x-www-form-urlencoded\r\n");
        fwrite($fp, "Content-Length: " . strlen($szContent) . "\r\n");
        fwrite($fp, "Connection: close\r\n");
        fwrite($fp, "\r\n");
        fwrite($fp, $szContent);
        $szAnswer = "";
        while (!feof($fp)) {
            $szAnswer .= fgets($fp, $iLineMaxSize);
        }
        fclose($fp);
        return $szAnswer;
    }

    protected function vFindData()
    {
        $DVS =& DecompilerFactory::call()->getDecompiler()->jDVS;
        if (isset($this->g_Sections[Decompiler::DATA])) {
            ImageFactory::call()->getImage()->vShowMessage("Find Resources ['" . Decompiler::DATA . "']");
            $DVS->Data = array();

            foreach ($this->g_Sections['$RESLIST$'] as $file) {
                $value = ModuleFactory::call()->get("ExeMod")->uExtractString($file);
                $file = substr($file, 5);
                $DVS->Data[$file] = $value;
            }

            ImageFactory::call()->getImage()->vShowArrayMessage("Resource was founded - '%s'", array_keys($DVS->Data));
        }
    }

    protected function vFindDFM()
    {
        $DVS =& DecompilerFactory::call()->getDecompiler()->jDVS;
        ImageFactory::call()->getImage()->vShowMessage("Find DFM ['" . Decompiler::DFM . "']");

        if (isset($this->g_Sections[Decompiler::DFM])) {
            $DVS->DFM = $this->g_Sections[Decompiler::DFM];
        } elseif (isset($this->g_Sections[str_replace("\\", "_", Decompiler::DFM)])) {
            $DVS->DFM = $this->g_Sections[str_replace("\\", "_", Decompiler::DFM)];
        }

        ImageFactory::call()->getImage()->vShowArrayMessage("DFM was founded - '%s'", array_keys($DVS->DFM));
    }

    protected function vFindEventData()
    {
        $DVS =& DecompilerFactory::call()->getDecompiler()->jDVS;
        if (isset($this->g_Sections[Decompiler::EVENT_DATA])) {
            ImageFactory::call()->getImage()->vShowMessage("Find EventData ['" . Decompiler::EVENT_DATA . "']");
            $lszActions = array();

            if (is_array($this->g_Sections[Decompiler::EVENT_DATA])) {
                foreach ($this->g_Sections[Decompiler::EVENT_DATA] as $lszFormsI => $lszFormsV) {
                    if (is_array($lszFormsV)) {
                        foreach ($lszFormsV as $lszObjectsI => $lszObjectsV) {
                            if (is_array($lszObjectsV)) {
                                foreach ($lszObjectsV as $lszActionsI => $szActionsV) {
                                    if (is_string($szActionsV)) {
                                        $szMessage = "#" . RD_CAPTION . " - DefaultDecompiler \n";
                                        $szMessage .= "#{$lszFormsI}->{$lszObjectsI}->{$lszActionsI}\n\n";
                                        $szActionsV = $szMessage . $szActionsV;
                                        $this->g_Sections[Decompiler::EVENT_DATA][$lszFormsI][$lszObjectsI][$lszActionsI] = $szActionsV;
                                        $lszActions[] = "['" . $lszFormsI . "']['" . $lszObjectsI . "']['" . $lszActionsI . "']";
                                    }
                                }
                            }
                        }
                    }
                }
            }

            ImageFactory::call()->getImage()->vShowArrayMessage("Action was founded - %s", $lszActions);
            $DVS->EventData = $this->g_Sections[Decompiler::EVENT_DATA];
        }
    }

    protected function vFindForms()
    {
        $DVS =& DecompilerFactory::call()->getDecompiler()->jDVS;
        if (isset($this->g_Sections[Decompiler::CONFIG][DVS::FORMS])) {
            ImageFactory::call()->getImage()->vShowMessage("Find Forms ['" . Decompiler::FORMS . "']['" . DVS::FORMS . "']");
            $DVS->Forms = $this->g_Sections[Decompiler::CONFIG][DVS::FORMS];
            ImageFactory::call()->getImage()->vShowArrayMessage("Form was founded - '%s'", array_keys($DVS->Forms));
        }
    }

    protected function vFindConfig()
    {
        $DVS =& DecompilerFactory::call()->getDecompiler()->jDVS;
        if (isset($this->g_Sections[Decompiler::CONFIG][DVS::CONFIG])) {
            ImageFactory::call()->getImage()->vShowMessage("Find Config ['" . Decompiler::CONFIG . "']['" . DVS::CONFIG . "']");
            $DVS->Config = $this->g_Sections[Decompiler::CONFIG][DVS::CONFIG];
        }
    }

    protected function szStringForceDecode($szString)
    {
        w1:
        $szOldString = $szString;

        if (is_string($szString) && $this->bIsBase64($szString)) {
            $szString = base64_decode($szString);
        }

        if (is_string($szString) && $this->szStringUnpack($szString) != $szString) {
            $szString = $this->szStringUnpack($szString);
        }

        if (is_string($szString) && $this->uMultiUnserialize($szString) != $szString) {
            $szString = $this->uMultiUnserialize($szString);
        }

        if ($szOldString != $szString) {
            goto w1;
        }

        return $szString;
    }

    protected function bIsBase64($szString)
    {
        if (!strlen($szString)) {
            return false;
        }

        $iCount = 0;
        $szDecodedString = base64_decode($szString);

        if (base64_encode($szDecodedString) === $szString) {
            $iCount++;
        }

        $iSpacesCount = substr_count($szString, " ");
        if (($iSpacesCount / strlen($szString)) < 0.05) {
            $iCount++;
        }

        if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $szString)) {
            $iCount++;
        }

        $lszZeroOne = array(
            'MA==',
            'MQ=='
        );

        if (in_array(str_split($szString, 4), $lszZeroOne)) {
            $iCount++;
        }

        $szHtml = htmlspecialchars($szDecodedString);

        if (!(empty($szHtml))) {
            $iMatches = 0;
            for ($i = 0; $i < strlen($szHtml); $i++) {
                if (ord($szHtml[$i]) > 126) {
                    $iMatches++;
                }
            }
            if ($iMatches <= 1) {
                $iCount++;
            }
        }

        return $iCount >= 4;
    }

    protected function szStringUnpack($szString)
    {
        $szMethodsUnpack = array(
            "gzcompress" => "gzuncompress",
            "gzdeflate" => "gzinflate",
            "gzencode" => "gzdecode"
        );
        $szMethod = $this->szGetCompressMethod($szString);
        if ($szMethod == "") {
            return $szString;
        } else {
            return $this->szStringUnpack(
                call_user_func_array(
                    $szMethodsUnpack[$szMethod],
                    array(
                        $szString
                    )
                )
            );
        }
    }

    protected function szGetCompressMethod($szString)
    {
        $bIsDS = false;
        if (ImageFactory::call()->getImage() instanceof DevelStudio) {
            $bIsDS = true;
            ModuleFactory::call()->get("ImageUtils")->uCallFunction("err_status", false);
        }

        $szMethod = "";
        if (function_exists("gzuncompress") && @gzuncompress($szString) != false) {
            $szMethod = "gzcompress";
        } else if (function_exists("gzinflate") && @gzinflate($szString) != false && @unserialize($szString) === false) {
            $szMethod = "gzdeflate";
        } else if (function_exists("gzdecode") && @gzdecode($szString) != false) {
            $szMethod = "gzencode";
        }

        if ($bIsDS) {
            ModuleFactory::call()->get("ImageUtils")->uCallFunction("err_status", true);
        }

        return $szMethod;
    }

    protected function uMultiUnserialize($szString)
    {
        $szUnserialize = @unserialize($szString);
        if ($szUnserialize !== false) {
            return $szUnserialize;
        }
        $szJsonDecode = @json_decode($szString, true);
        if ($szJsonDecode != false) {
            return $szJsonDecode;
        }
        return $szString;
    }
}