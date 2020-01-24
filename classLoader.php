<?php
function autoload($class)
{
    $paths = [
      dirname(__FILE__) . "/model/",
      dirname(__FILE__) . "/controllers/",
      dirname(__FILE__) . "/modules/",
    ];

    foreach ($paths as $path)
    {
        if (is_file($path . "$class.php"))
            include_once($path . "$class.php");
    }
}

spl_autoload_register("autoload");
