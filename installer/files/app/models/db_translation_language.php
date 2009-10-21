<?php
class DbTranslationLanguage extends ActiveRecord
{
    var $hasMany = array('translations'=>array('class_name'=>'DbTranslation','dependent'=>'destroy'),
                         'settings'=>array('class_name' => 'DbTranslationLanguageSetting','dependent'=>'destroy'));
    
    function afterCreate()
    {   
        $this->_createAdminPermissionsForLanguage();
        if(empty($this->_do_not_recreate_namespaces)) {
            $this->importLocaleConfigFromFiles();
            $this->importTranslationsFromFiles();
            $this->_createAllNamespaces(AK_FRAMEWORK_LANGUAGE);
            $this->_buildLocale();
            
        }
        $this->_adoptConfigFilesAfterCreate();
        return true;
    }
    function afterDestroy()
    {
        $this->_adoptConfigFilesAfterDestroy();
        $this->_deleteTranslatorRole();
        return true;
    }
    function _deleteTranslatorRole()
    {
        Ak::import('Extension,Permission,Role');
        $rol=new Role();
        
        if($role=&$rol->findFirstBy('name','Translator for '.$this->iso)) {
            
            $role->destroy();
        }
    }
    function beforeValidate()
    {
        if(!empty($this->iso)) {
            $this->iso=trim($this->iso);
        }
        return true;
    }
    function validate()
    {
        $this->validatesFormatOf('iso','/[a-zA-Z_]+/');
        $this->validatesPresenceOf('iso');
        $this->validatesLengthOf('iso',array('within'=>array(2, 5)));
    }
    function _createAllNamespaces($lang=false)
    {
       
        $this->rebuildAllNamespaces($lang);
        
    }
    function _createAdminPermissionsForLanguage()
    {
        Ak::import('Extension,Permission,Role');
        $ext=new Extension();
        if(!$extension=&$ext->findFirstBy('name','Admin::DbTranslations')) {
            $extension=&$ext->create(array('name'=>'Admin::DbTranslations','is_core'=>false, 'is_enabled' => true));
        }
        $rol=new Role();
        $admin=&$rol->findFirstBy('name','Administrator');
        
        if(!$translatorRole=&$rol->findFirstBy('name','Translator')) {
            
            $translatorRole=&$admin->addChildrenRole('Translator');
        }
        
        
        if(!$role=&$rol->findFirstBy('name','Translator for '.$this->iso)) {
            
            $role=&$translatorRole->addChildrenRole('Translator for '.$this->iso);
        }
       
        
        $adminDashboardTabs=&$ext->findFirstBy('name','Admin::Dashboard');
        $adminIntranet=&$ext->findFirstBy('name','Admin::Intranet');
        $adminMenuTabs=&$ext->findFirstBy('name','Admin Menu Tabs');
        $perm=new Permission();
        $langPerm=&$perm->findOrCreateBy('name AND extension_id','Languages (db_translations controller, languages action)',$adminMenuTabs->id);
        $langPerm2=&$perm->findOrCreateBy('name AND extension_id','Db Translations (db_translations controller, languages action)',$adminMenuTabs->id);
        $langPerm3=&$perm->findOrCreateBy('name AND extension_id','DbTranslations (db_translations controller, languages action)',$adminMenuTabs->id);
        
        $nsPerm=&$perm->findOrCreateBy('name AND extension_id','Namespaces (db_translations controller, namespaces action)',$adminMenuTabs->id);
        
        $translatePerm=&$perm->findOrCreateBy('name AND extension_id','Db Translations (db_translations controller, translate action)',$adminMenuTabs->id);
        
        $translatePerm=&$perm->findOrCreateBy('name AND extension_id','Db Translations (db_translations controller, translate action)',$adminMenuTabs->id);
        
        $dashboardPerm2=&$perm->findOrCreateBy('name AND extension_id','index action',$adminDashboardTabs->id);
        $dashboardPerm=&$perm->findOrCreateBy('name AND extension_id','overview action',$adminDashboardTabs->id);
        
        $intranetPerm1=&$perm->findOrCreateBy('name AND extension_id','Access the intranet',$adminIntranet->id);
        
        $role->permission->add($langPerm);
        $role->permission->add($langPerm2);
        $role->permission->add($langPerm3);
        $role->permission->add($nsPerm);
        $role->permission->add($translatePerm);
        $role->permission->add($dashboardPerm);
        $role->permission->add($dashboardPerm2);
        $role->permission->add($intranetPerm1);
        
        $permission=new Permission();
        if(!$perm=&$permission->findFirstBy('name','Translate:'.$this->iso)) {
            
            $role->addPermission(array('name'=>'Translate:'.$this->iso, 'extension' => $extension));
            $role->addPermission(array('name'=>'index action', 'extension' => $extension));
            $role->addPermission(array('name'=>'languages action', 'extension' => $extension));
            $role->addPermission(array('name'=>'namespaces action', 'extension' => $extension));
            $role->addPermission(array('name'=>'translate action', 'extension' => $extension));
            $admin->addPermission(array('name'=>'Configure:'.$this->iso, 'extension' => $extension));
            
            $admin->addPermission(array('name'=>'Translate Core:'.$this->iso, 'extension' => $extension));
            $admin->addPermission(array('name'=>'Delete Translation:'.$this->iso, 'extension' => $extension));
            $admin->addPermission(array('name'=>'Create Translation:'.$this->iso, 'extension' => $extension));
        }
        $role->permission->add($perm);
        
    }
    
    function getNamespaces($changed_only=false)
    {
        if($changed_only) {
            return $this->_db->selectValues(array('SELECT distinct namespace FROM db_translations LEFT JOIN db_translation_languages ON db_translations.db_translation_language_id=db_translation_languages.id WHERE db_translation_languages.iso=? AND db_translations.has_changed_original=1 ORDER BY db_translations.namespace',$this->iso));
        } else {
            return $this->_db->selectValues(array('SELECT distinct namespace FROM db_translations LEFT JOIN db_translation_languages ON db_translations.db_translation_language_id=db_translation_languages.id WHERE db_translation_languages.iso=? ORDER BY db_translations.namespace',$this->iso));
        }
    }
    
    function importTranslationsFromFiles($baseDir=false)
    {
        set_time_limit(-1);
        Ak::import('DbTranslation');
        $root=AK_APP_DIR.DS.'locales';
        if(!$baseDir) {
            $baseDir=$root;
        }
        $diff=str_replace($root,'',$baseDir);
        $namespace=trim($diff,DS);
        $dir=opendir($baseDir);
        if($dir) {
            while($file=readdir($dir)) {
                if(!in_array($file,array('.','..'))) {
                    if(is_dir($baseDir.DS.$file)) {
                        $this->importTranslationsFromFiles($baseDir.DS.$file);
                    } else if($file==$this->iso.'.php') {
                        $dictionary=AkLocaleManager::getDictionary($this->iso,$namespace);
                        foreach($dictionary as $key=>$translation)
                        {
                            if(trim($key)=='') continue;

                            $translationObject=new DbTranslation();
                            $translationObject->setAttributes(array('db_translation_language_id'=>$this->id,'namespace'=>$namespace,'identifier'=>$key,'translation'=>$translation));
                            $translationObject->_import_from_file=true;
                            $translationObject->save();
                        }
                    }
                }
            }
        }
    }
    function importLocaleConfigFromFiles()
    {
        //Ak::getLogger()->log('message','importLocaleConfigFromFiles');
        Ak::import('DbTranslation');
        Ak::import('DbTranslationLanguageSetting');
        list($locales,$dictionary)=AkLocaleManager::getCoreDictionary($this->iso);
        foreach($locales as $setting=>$value) {
           //Ak::getLogger()->log('message','creating locale setting for iso:'.$this->iso.': '.$setting.'=>'.var_export($value,true));
            $localeObject=new DbTranslationLanguageSetting(array('db_translation_language_id'=>$this->id,'name'=>$setting,'value'=>$value));
            $localeObject->_import_from_file=true;
            $localeObject->save();
            
        }
        foreach($dictionary as $key=>$translation)
        {
            $translationObject=new DbTranslation(array('db_translation_language_id'=>$this->id,'namespace'=>null,'identifier'=>$key,'translation'=>$translation));
            $translationObject->_import_from_file=true;
            $translationObject->save();
        }
    }
    function _adoptConfigFilesAfterCreate()
    {
        $boot=Ak::file_get_contents(AK_CONFIG_DIR.DS.'boot.php');
        if(preg_match('/define\(\s*([\'"])AK_APP_LOCALES\\1\s*,\s*([\'"])(.*?)\\2\s*\);/s',$boot,$matches)) {
            $newboot=str_replace($matches[0],"define('AK_APP_LOCALES','".join(',',array_unique(array_merge(Ak::toArray($matches[3]),array($this->iso))))."');",$boot);
            Ak::file_put_contents(AK_CONFIG_DIR.DS.'boot.php',$newboot);
        } else {
            $config=Ak::file_get_contents(AK_CONFIG_DIR.DS.'config.php');
    
            if(preg_match('/define\(\s*([\'"])AK_APP_LOCALES\\1\s*,\s*([\'"])(.*?)\\2\s*\);/s',$config,$matches)) {
                $newconfig=str_replace($matches[0],"define('AK_APP_LOCALES','".join(',',array_unique(array_merge(Ak::toArray($matches[3]),array($this->iso))))."');",$config);
                Ak::file_put_contents(AK_CONFIG_DIR.DS.'config.php',$newconfig);
            } else {
                $config=Ak::file_get_contents(AK_CONFIG_DIR.DS.'environments'.DS.AK_ENVIRONMENT.'.php');
                if(preg_match('/define\(\s*([\'"])AK_APP_LOCALES\\1\s*,\s*([\'"])(.*?)\\2\s*\);/s',$config,$matches)) {
                    $newconfig=str_replace($matches[0],"define('AK_APP_LOCALES','".join(',',array_unique(array_merge(Ak::toArray($matches[3]),array($this->iso))))."');",$config);
                    Ak::file_put_contents(AK_CONFIG_DIR.DS.'environments'.DS.AK_ENVIRONMENT.'.php',$newconfig);
                }
            }
        }
    }
    function _adoptConfigFilesAfterDestroy()
    {
        $boot=Ak::file_get_contents(AK_CONFIG_DIR.DS.'boot.php');
        if(preg_match('/define\(\s*([\'"])AK_APP_LOCALES\\1\s*,\s*([\'"])(.*?)\\2\s*\);/s',$boot,$matches)) {
            $newboot=str_replace($matches[0],"define('AK_APP_LOCALES','".join(',',array_unique(array_diff(Ak::toArray($matches[3]),array($this->iso))))."');",$boot);
            Ak::file_put_contents(AK_CONFIG_DIR.DS.'boot.php',$newboot);
        } else {
            $config=Ak::file_get_contents(AK_CONFIG_DIR.DS.'config.php');
    
            if(preg_match('/define\(\s*([\'"])AK_APP_LOCALES\\1\s*,\s*([\'"])(.*?)\\2\s*\);/s',$config,$matches)) {
                $newconfig=str_replace($matches[0],"define('AK_APP_LOCALES','".join(',',array_unique(array_diff(Ak::toArray($matches[3]),array($this->iso))))."');",$config);
                Ak::file_put_contents(AK_CONFIG_DIR.DS.'config.php',$newconfig);
            }
        }
    }
    function _buildLocale()
    {
        $locales=&$this->setting->find(array('returns'=>'array'));
        if(!$locales && $this->iso!=AK_FRAMEWORK_LANGUAGE) {
            $lang=&$this->findFirstBy('iso',AK_FRAMEWORK_LANGUAGE);
            if($lang) {
                $locales=&$lang->setting->find(array('returns'=>'array'));
            }
        }
        
        if($locales) {
            $localedata=array();
            foreach($locales as $locale) {
                
                $localedata[$locale['name']]=$locale['value'];
                
            }
            
            
        }
        $localedata['description'] = !empty($this->name)?$this->name:$this->iso;
        $translations=&$this->translation->find(array('conditions'=>'namespace IS NULL','returns'=>'array'));
        if(!$translations && $this->iso!=AK_FRAMEWORK_LANGUAGE) {
            $lang=&$this->findFirstBy('iso',AK_FRAMEWORK_LANGUAGE);
            if($lang) {
                $translations=&$lang->translation->find(array('conditions'=>'namespace IS NULL','returns'=>'array'));
            }
        
        }
        if($translations) {
            $transdata=array();
            foreach($translations as $trans) {
                
                $transdata[$trans['identifier']]=$trans['translation'];
                
            }
            AkLocaleManager::setCoreDictionary($localedata,$transdata,$this->iso);
        }
        
    }
    
    function rebuildNamespace($ns)
    {
        $translations=&$this->translation->find(array('conditions'=>array('namespace = ? AND namespace IS NOT NULL',$ns),'returns'=>'array'));
        if(!$translations && $this->iso!=AK_FRAMEWORK_LANGUAGE) {
            $lang=&$this->findFirstBy('iso',AK_FRAMEWORK_LANGUAGE);
            if($lang) {
                $translations=&$lang->translation->find(array('conditions'=>array('namespace = ?',$ns),'returns'=>'array'));
            }
        
        }
        
        if($translations) {
            $data=array();
            $namespace=false;
            foreach($translations as $trans) {
                
                $data[$trans['identifier']]=$trans['translation'];
                
            }
            AkLocaleManager::setDictionary($data,$this->iso,$ns);
            
        }
    }
    function getAllNamespaces($lang = false)
    {
        return @$this->_db->selectValues(array('SELECT distinct namespace FROM db_translations LEFT JOIN db_translation_languages ON db_translations.db_translation_language_id=db_translation_languages.id WHERE db_translation_languages.iso=?',!$lang?$this->iso:$lang));
        
    }
    function rebuildAllNamespaces($lang=false)
    {
        foreach($this->getAllNamespaces($lang) as $ns){
            $this->rebuildNamespace($ns);
        }
    }
    
    function deleteAllNamespaces($lang=false)
    {
        foreach($this->getAllNamespaces($lang) as $ns){
            $this->deleteNamespace($ns);
        }
    }
    
    function deleteNamespace($ns)
    {
        $trans=new DbTranslation();
        $trans->destroyAll(array('namespace=? AND db_translation_language_id=?',$ns,$this->id));
        AkLocaleManager::deleteDictionary($this->iso,$ns);
    }
    
    
}
?>