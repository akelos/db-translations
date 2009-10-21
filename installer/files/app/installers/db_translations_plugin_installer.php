<?php
require_once(AK_LIB_DIR.DS.'AkLocaleManager.php');
class DbTranslationsPluginInstaller extends AkInstaller {

    var $vervose = false;

    function up_1()
    {
        $this->createTable('db_translation_languages','id,name,iso string(5) not null');
        $this->createTable('db_translations','id,db_translation_language_id,namespace string(128),identifier_hash string(32),identifier text,translation text');
        $this->createTable('db_translation_language_settings','id,db_translation_language_id,name string(128),value string(1024)');

    }
    function down_1()
    {
        Ak::import('Extension,Permission');
        $extension=new Extension();
        $adminExtension=&$extension->findFirstBy('name','Admin::DbTranslations',array('include'=>'permissions'));
        if($adminExtension && isset($adminExtension->permissions) && is_array($adminExtension->permissions)) {
            foreach($adminExtension->permissions as $perm) {
                $perm->destroy();
            }
            $adminExtension->destroy();
        }



        /**
         * delete permissions
         */

        $adminMenuTabs=&$extension->findFirstBy('name','Admin Menu Tabs');
        $perm=new Permission();
        $langPerm=&$perm->findFirstBy('name AND extension_id','Languages (db_translations controller, languages action)',$adminMenuTabs->id);
        if($langPerm) $langPerm->destroy();

        $nsPerm=&$perm->findFirstBy('name AND extension_id','Namespaces (db_translations controller, namespaces action)',$adminMenuTabs->id);
        if($nsPerm) $nsPerm->destroy();

        $translatePerm=&$perm->findFirstBy('name AND extension_id','Db Translations (db_translations controller, translate action)',$adminMenuTabs->id);
        if($translatePerm) $translatePerm->destroy();

        $this->dropTable('db_translation_languages');
        $this->dropTable('db_translations');
        $this->dropTable('db_translation_language_settings');
    }
    function up_2()
    {
        $this->addIndex('db_translation_languages','UNIQUE iso','UNQ_db_translation_language_iso');
        $this->addIndex('db_translations','UNIQUE identifier_hash,namespace,db_translation_language_id','UNQ_db_translation_item');
        $this->addIndex('db_translation_language_settings','UNIQUE db_translation_language_id,name','UNQ_db_translation_language_setting');
        $this->_createLanguages();
    }
    function up_3()
    {
        $this->addColumn('db_translations','has_changed_original bool default 0');
    }
    function down_3()
    {
        $this->removeColumn('db_translations','has_changed_original');
    }
    function down_2()
    {
        $this->_deleteTranslatorRoles();
        $this->removeIndex('db_translation_languages','UNQ_db_translation_language_iso');
        $this->removeIndex('db_translations','UNQ_db_translation_item');
        $this->removeIndex('db_translation_language_settings','UNQ_db_translation_language_setting');

    }
    function _createLanguages()
    {
        Ak::import('DbTranslationLanguage');
        $l=new DbTranslationLanguage;

        //$l->transactionStart();
        list($locales,$dict)=AkLocaleManager::getCoreDictionary(AK_FRAMEWORK_LANGUAGE);
        $language=new DbTranslationLanguage(array('iso'=>AK_FRAMEWORK_LANGUAGE,'name'=>!empty($locales['description'])?$locales['description']:$lang));
        $language->save();
        foreach(Ak::langs() as $lang) {
            if($lang==AK_FRAMEWORK_LANGUAGE) continue;
            list($locales,$dict)=AkLocaleManager::getCoreDictionary($lang);
            $language=new DbTranslationLanguage(array('iso'=>$lang,'name'=>!empty($locales['description'])?$locales['description']:$lang));
            $language->save();
        }
        //$l->transactionComplete();
    }


    function _deleteTranslatorRoles()
    {
        Ak::import('Extension,Permission,Role');
        $rol=new Role();
        foreach(Ak::langs() as $lang) {
            if($role=&$rol->findFirstBy('name','Translator for '.$lang)) {

                $role->destroy();
            }
        }
    }


}
?>