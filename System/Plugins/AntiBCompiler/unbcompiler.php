<?php

@$srcdir = dirname(__FILE__);
@require_once("$srcdir/Decompiler.class.php");
@$dc = @new Decompiler();
@$dc->decompileFile($_SERVER['argv'][1]);


return @$dc->output();