<?php


namespace System\Modules;


use Core\Factories\Classes\ModuleFactory\Module;
use Core\Factories\Providers\ModuleFactory;

class ExeMod extends Module
{
    public static $sTag = "SO!#";
    public static $eTag = "EO!#";

    private $szFile;

    function vSetFile($szFile = NULL)
    {
        if (!$szFile) {
            $this->szFile = ModuleFactory::call()->get("ImageUtils")->uGetArgs(1);
        } else {
            $this->szFile = $szFile;
        }
    }

    function lszGetAllSectionsData()
    {
        $arr = array();

        $lszSections = array_unique(array_merge($this->lszGetAllSectionsNames(), $this->lszCompatibleGetSectionsNames()));

        foreach ($lszSections as $item => $val) {
            $arr[$val] = $this->uExtractString($val);
        }

        return $arr;
    }

    function lszGetAllSectionsNames()
    {
        $szFileData = $this->szGetFileContent();

        preg_match_all("/" . self::$eTag . ".*?" . self::$sTag . "/", $szFileData, $lszSections);
        $lszSections = $lszSections[0];

        foreach ($lszSections as $item => $value) {
            $value = substr($value, 4, -4);
            $lszSections[$item] = $value;
        }

        if (sizeof($lszSections) == 0) {
            if (self::$sTag[1] == chr(0xCE) || self::$eTag[1] == chr(0xCE)) {
                return array();
            } else {
                self::$sTag[1] = chr(0xCE);
                self::$eTag[1] = chr(0xCE);
                return $this->lszGetAllSectionsNames();
            }
        } else {
            return $lszSections;
        }
    }

    private function szGetFileContent()
    {
        return file_get_contents($this->szFile);
    }

    function lszCompatibleGetSectionsNames()
    {
        $szFileData = $this->szGetFileContent();
        $sections = array();
        $s_tag = self::$sTag;
        $r_tag = substr(html_entity_decode('&#182;', 0, 'UTF-8'), 1);
        $e_tag = self::$eTag;
        $point = 0;
        while (($point = strpos($szFileData, $s_tag, $point)) !== false) {
            $point += 4;
            $name_pos = $point;
            $point = strpos($szFileData, $r_tag, $point);
            $name = substr($szFileData, $name_pos, $point - $name_pos);
            $point += 1;
            $data_pos = $point;
            $point = strpos($szFileData, $e_tag, $point);
            $sections[$name] = substr($szFileData, $data_pos, $point - $data_pos);
            $out[] = $name;
        }
        $out = array_unique($out);
        return $out;
    }

    function uExtractString($szRes)
    {
        $szFileData = $this->szGetFileContent();

        if (!$szFileData) {
            return false;
        }

        if (strpos($szFileData, self::$sTag . $szRes . chr(182)) > 0) {
            $iStart = strpos($szFileData, self::$sTag . $szRes . chr(182)) + strlen(self::$sTag . $szRes . chr(182));
            $iEnd = strpos($szFileData, self::$eTag . $szRes);
            return substr($szFileData, $iStart, $iEnd - $iStart);
        }

        return false;
    }
}