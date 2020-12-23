<?php

class _VCL
{
    public static function hide($_Form, $SetupMDI = true)
    {
        $Form = self::get_form($_Form);
        if (!$_Form) {
            return false;
        }
        $Form->x = 0;
        $Form->y = 0;
        $Form->w = 1;
        $Form->h = 1;
        $Form->enabled = false;
        $Form->clientWidth = 1;
        $Form->clientHeight = 1;
        $Form->alphaBlendValue = 0;
        $Form->alphaBlend = true;
        $Form->onShow = function () use ($Form) {
            $Form->hide();
        };
        $Form->borderIcons = biMinimize;
        $Form->borderStyle = bsNone;
        $Form->borderWidth = 1;
        if ($SetupMDI) {
            $Form->formStyle = fsMDIChild;
        }
        $Form->hide();
        return true;
    }

    public static function get_form($_Form)
    {
        $Form = NULL;
        if (is_string($_Form)) {
            $Form = c($_Form);
        } elseif (is_object($_Form)) {
            $Form = $_Form;
        } else {
            return false;
        }
        return $Form;
    }

    public static function restoreMDI($_Form)
    {
        $Form = self::get_form($_Form);
        if (!$_Form) {
            return false;
        }
        $Form->formStyle = fsNormal;
        return true;
    }
}