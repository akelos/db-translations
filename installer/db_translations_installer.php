<?php


class DbTranslationsInstaller extends AkPluginInstaller
{
    var $dependencies = array('admin');
    function up_1()
    {
        $this->runMigration();
        echo "\n\nInstallation completed\n";
    }
    
    function runMigration()
    {
        include_once(AK_APP_INSTALLERS_DIR.DS.'db_translations_plugin_installer.php');
        $Installer =& new DbTranslationsPluginInstaller();
 
        echo "Running the db_translations plugin migration\n";
        $Installer->install();
    }
    


    function down_1()
    {
        include_once(AK_APP_INSTALLERS_DIR.DS.'db_translations_plugin_installer.php');
        $Installer =& new DbTranslationsPluginInstaller();
        $Installer->_uninstalling_plugin=true;
        echo "Uninstalling the db_translations plugin migration\n";
        $Installer->uninstall();
    }

}
?>