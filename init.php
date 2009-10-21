<?php

class DbTranslationsPlugin extends AkPlugin
{
    function load()
    {
        if(!defined('AK_LOCALE_MANAGER')) {
            define('AK_LOCALE_MANAGER','DbLocaleManager');
            require_once($this->getPath().DS.'lib'.DS.'DbLocaleManager.php');
        }
    }
}

?>
