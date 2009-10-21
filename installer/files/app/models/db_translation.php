<?php
class DbTranslation extends ActiveRecord
{
    var $belongsTo = array('language'=>array('class_name'=>'DbTranslationLanguage'));
    
    function beforeSave()
    {
        $this->identifier_hash=md5(@$this->identifier);
        $this->language->load();
        if(!empty($this->language->iso) && $this->language->iso != AK_FRAMEWORK_LANGUAGE && !empty($this->has_changed_original)) {
            $this->has_changed_original=false;
        }
        return true;
    }
    function afterCreate()
    {
        if(!empty($this->_import_from_file)) return true;
        $this->language->load();
        if(!empty($this->language->iso) && $this->language->iso==AK_FRAMEWORK_LANGUAGE) {
            /**
             * replicate translation in all languages
             */
            $l=new DbTranslationLanguage();
            foreach(Ak::langs() as $lang) {
                if($lang!=AK_FRAMEWORK_LANGUAGE) {
                    $languageObject=$l->findFirstBy('iso',$lang);
                    if($languageObject) {
                        $translation=new DbTranslation();
                        if(!($existing=$translation->findFirstBy('identifier AND namespace AND db_translation_language_id',$this->identifier,$this->namespace,$languageObject->id))) {
                            $translation->identifier=$this->identifier;
                            $translation->translation=$this->translation;
                            $translation->namespace=$this->namespace;
                            $translation->has_changed_original = true;
                            $translation->language->assign($languageObject);
                            $translation->save();
                        }
                    }
                }
            }
        }
        return true;
    }
    function afterSave()
    {
        
        if(!empty($this->_import_from_file)) return true;
        $this->language->load();
        if(empty($this->db_translation_language_id) && empty($this->language->iso)) {
            $this->addErrorOnEmpty('db_translation_language_id');
            return false;
        }
        if(!empty($this->namespace)) {
            $dictionary=AkLocaleManager::getDictionary($this->language->iso,!empty($this->namespace)?$this->namespace:false);
            $dictionary[$this->identifier] = $this->translation;
            AkLocaleManager::setDictionary($dictionary,$this->language->iso,!empty($this->namespace)?$this->namespace:false);
        } else {
            list($locales,$dictionary)=AkLocaleManager::getCoreDictionary($this->language->iso);
            $dictionary[$this->identifier] = $this->translation;
            AkLocaleManager::setCoreDictionary($locales,$dictionary,$this->language->iso);
        }
        if(!empty($this->language->iso) && $this->language->iso == AK_FRAMEWORK_LANGUAGE && $this->translation!=@$this->original_translation) {
            $this->updateAll('has_changed_original=1',array('db_translation_language_id<>? AND identifier=?',$this->db_translation_language_id,$this->identifier));
        }
        return true;
    }
    function validate()
    {
        $this->validatesPresenceOf('identifier');
        $this->validatesUniquenessOf('identifier_hash',array('scope'=>array('namespace','db_translation_language_id')));
        
    }

    
    function beforeDestroy()
    {
        $lang=&$this->language->load();
        if(!empty($lang)) {
            $this->iso=$lang->iso;
            //$this->log('message','BeforeDestroy true with iso:'.$this->iso);
            return true;
        }
         //$this->log('message','BeforeDestroy false');
        return false;
    }
    function afterDestroy()
    {
        if(!empty($this->_uninstalling_plugin)) return true;
        //$this->log('message','afterDestroy:'.$this->namespace.' - '.$this->iso);
        if(!empty($this->namespace)) {
            $dictionary=AkLocaleManager::getDictionary($this->iso,$this->namespace);
            unset($dictionary[$this->identifier]);
            if(empty($dictionary)) {
                AkLocaleManager::deleteDictionary($this->iso,!empty($this->namespace)?$this->namespace:false);
            } else {
                //echo "setting dictionary afterDestroy: '{$this->namespace}': ".var_export($dictionary,true);
                AkLocaleManager::setDictionary($dictionary,$this->iso,!empty($this->namespace)?$this->namespace:false);
            }
        } else {
            list($locales,$dictionary)=AkLocaleManager::getCoreDictionary($this->iso);
            unset($dictionary[$this->identifier]);
            if(empty($dictionary) && empty($locales)) {
                //echo "Delete core dictionary\n";
                AkLocaleManager::deleteCoreDictionary($this->iso);
            } else {
                //echo "setting core dictionary afterDestroy:".var_export($locales,true).'-'.var_export($dictionary,true);
                AkLocaleManager::setCoreDictionary($locales,$dictionary,$this->iso);
            }
        }
        if($this->language->load() && !empty($this->language->iso) && $this->language->iso==AK_FRAMEWORK_LANGUAGE) {
            foreach(Ak::langs() as $lang) {
                if($lang!=AK_FRAMEWORK_LANGUAGE) {
                    if(empty($this->namespace)) {
                        $cond=array('identifier = ? AND db_translation_language_id<>? AND namespace IS NULL',$this->identifier,$this->language->id);
                    } else {
                        $cond=array('identifier = ? AND db_translation_language_id<>? AND namespace = ?',$this->identifier,$this->language->id,$this->namespace);
                    }
                    $translations=&$this->findAll(array('conditions'=>$cond));
                    if($translations) {
                        foreach($translations as $trans) {
                            $trans->destroy();
                        }
                    }
                }
            }
        }
        return true;
    }
    
    function getOriginalTranslation($lang,$key,$namespace)
    {
        static $namespaces=array();
        if(!empty($namespaces[$lang][$namespace][$key])) {
            return $namespaces[$lang][$namespace][$key];
        } else {
            if(empty($namespaces[$lang])) {
                $namespaces[$lang]=array($namespace=>array());
                
            }
            $namespaces[$lang][$namespace] = AkLocaleManager::getDictionary($lang,$namespace);
        }
        return !empty($namespaces[$lang][$namespace][$key])?$namespaces[$lang][$namespace][$key]:$key;
    }
}
?>