<?php
function atomcredit_autoload($modelname){
    if(Model::exists($modelname)){
        include Model::getFullPath($modelname);
        return;
    }

    // PSR-4 style loader for App\Controller\* and App\Service\*
    if(strpos($modelname, 'App\\') === 0){
        $parts = explode('\\', $modelname);
        if(count($parts) >= 3){
            $subfolder = strtolower($parts[1]); // 'controller' or 'service'
            $classname = $parts[2];
            $fullpath = __DIR__ . "/" . $subfolder . "/" . $classname . ".php";
            if(file_exists($fullpath)){
                include $fullpath;
            }
        }
    }
}

spl_autoload_register("atomcredit_autoload");
?>
