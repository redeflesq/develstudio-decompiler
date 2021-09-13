<?php

namespace Core;

define("RD_CAPTION", "ReDecompiler 2.0alpha1");

use System\Runtime;

include "Bootstrap/Loader/Classes/Singleton.php";
include "Bootstrap/Loader/Loader.php";

Bootstrap\Loader\Loader::call()->vIncludeDir(
    "./Core/Bootstrap/Classes/"
);

Bootstrap\Loader\Loader::call()->vIncludeDir(
    "./Core/Managers/"
);

Bootstrap\Loader\Loader::call()->vIncludeDir(
    "./Core/Factories/"
);

include "System/Runtime.php";

Runtime::call();