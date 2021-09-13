<?php


namespace System\Modules;


class Tokens
{
    function vtSubToken($lszTokens, $iID, $iNumToken, $iCps = 0)
    {
        $iNID = 1;
        $inCps = $iCps;
        while (
            isset($lszTokens[$iID + $iNID][0]) && ($lszTokens[$iID + $iNID][0] != $iNumToken || (!is_array($lszTokens[$iID + $iNID]) && $lszTokens[$iID + $iNID] != $iNumToken))
        ) {
            $iNID += 1;
            if (($inCps > 0)) {
                $inCps -= 1;
            } elseif ($iCps != 0) {
                break;
            }
        }
        return $iID + $iNID;
    }

    function iGetPtrBetweenTwoCh($szString, $szCh1, $szCh2, $iStart = 0)
    {
        $iStartTag = 0;
        $iFinishTag = 0;
        $iRevOpenTag = 0;
        $iRevCloseTag = 0;

        for ($i = $iStart; $i < strlen($szString); $i++) {
            if ($szString[$i] == $szCh1) {
                if ($iRevOpenTag == 0) {
                    $iStartTag = $i;
                }
                $iRevOpenTag++;
            } else if ($szString[$i] == $szCh2) {
                $iRevCloseTag++;
                if ($iRevOpenTag == $iRevCloseTag) {
                    $iFinishTag = $i;
                    break;
                }
            }
        }

        return array(
            $iStartTag,
            $iFinishTag
        );
    }
}