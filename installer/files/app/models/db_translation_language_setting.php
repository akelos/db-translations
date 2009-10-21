<?php
class DbTranslationLanguageSetting extends ActiveRecord
{
    var $belongsTo = array('language'=>array('class_name'=>'DbTranslationLanguage'));
    var $serialize='value';
    function afterSave()
    {

        if(!empty($this->_import_from_file)) return true;
        if(empty($this->db_translation_language_id) && empty($this->language->iso) && !$this->language->load()) {
            $this->addErrorOnEmpty('db_translation_language_id');

            return false;
        }
        if(empty($this->language->iso)) {
            $lang=&$this->language->load();
            //$this->log('message','lang:'.$lang);
        }
        //$this->log('message','this:'.$this->__toString());
        list($locales,$dictionary)=AkLocaleManager::getCoreDictionary($this->language->iso);
        $locales[$this->name] = $this->value;
        AkLocaleManager::setCoreDictionary($locales,$dictionary,$this->language->iso);
        return true;
    }
    function validate()
    {
        $this->validatesUniquenessOf('name',array('scope'=>'db_translation_language_id'));
    }
    function beforeDestroy()
    {
        $lang=$this->language->load();
        if(!empty($lang)) {
            $this->iso=$lang->iso;
            return true;
        }
        return false;
    }
    function afterDestroy()
    {
        if(!empty($this->_uninstalling_plugin)) return true;
        list($locales,$dictionary)=AkLocaleManager::getCoreDictionary($this->iso);
        unset($locales[$this->name]);
        if(empty($dictionary) && empty($locales)) {
            //echo "Delete core dictionary\n";
            AkLocaleManager::deleteCoreDictionary($this->iso);
        } else {
            AkLocaleManager::setCoreDictionary($locales,$dictionary,$this->iso);
        }
        return true;
    }
}
?>