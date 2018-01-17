<?php

if (file_exists(__DIR__.'/../../autoload.php')) {
    require(__DIR__.'/../../autoload.php');
} else {
    require(__DIR__.'/vendor/autoload.php');
}


// Give access to prestashop stuff ;)
// Useful to create fixtures and delete them properly
require_once PS_DIR.'/config/config.inc.php';