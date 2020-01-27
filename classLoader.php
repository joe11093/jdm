<?php
function autoload($class)
{
    $paths = [
      dirname(__FILE__) . "/model/",
      dirname(__FILE__) . "/controllers/",
      dirname(__FILE__) . "/modules/",
      dirname(__FILE__) . "/services/search/",
      dirname(__FILE__) . "/services/cache/",
      dirname(__FILE__) . "/services/pagination/"
    ];

    foreach ($paths as $path)
    {
        if (is_file($path . "$class.php"))
            include_once($path . "$class.php");
    }
}

spl_autoload_register("autoload");
