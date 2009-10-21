<?php
class DbLocaleManager extends AkLocaleManager
{
    
    function updateLocaleFiles()
    {
        static $ran=false;
        //if($ran) return;
        $new_core_entries = array();
        $new_controller_entries = array();
        $new_controller_files = array();
        //error_log('Db locale manager'."\n",3,'/tmp/test.log');
        $used_entries = AkLocaleManager::getUsedLanguageEntries();
        //error_log(var_export($used_entries,true)."\n",3,'/tmp/test.log');
        //Ak::getLogger()->log('message','used entries:'.var_export($used_entries,true));
        list($core_locale,$core_dictionary) = self::getCoreDictionary(AK_FRAMEWORK_LANGUAGE);
        $controllers_dictionaries = array();
        Ak::import('DbTranslationLanguage');
        Ak::import('DbTranslation');
        $l=new DbTranslationLanguage();
        $languageObjects=array();
        //Ak::getLogger()->log('message','Looking up language with iso:'.AK_FRAMEWORK_LANGUAGE);
        if(!$languageObj=&$l->findFirstBy('iso',AK_FRAMEWORK_LANGUAGE)) {
            //Ak::getLogger()->log('message','Creating language with iso:'.AK_FRAMEWORK_LANGUAGE);
            $languageObj=&$l->create(array('iso'=>AK_FRAMEWORK_LANGUAGE,'name'=>AK_FRAMEWORK_LANGUAGE));
        }
        $languageObjects[AK_FRAMEWORK_LANGUAGE]=&$languageObj;
        //Ak::getLogger()->log('message','Updating used entries:'.var_export($used_entries,true));
       
        foreach ($used_entries as $k=>$v){
            // This is a controller file
           
            if(is_array($v)){
                $_previous_dict = AkLocaleManager::getDictionary(AK_FRAMEWORK_LANGUAGE,$k);
                //$v=array_diff(array_keys($_previous_dict),array_keys($v));
                $v = self::_getNewEntries($v,$_previous_dict);
                //Ak::getLogger()->log('message','New entries in '.$k.':'.var_export($v,true));
                if(!empty($v)) {
                    foreach($v as $key=>$value) {
                        $languageObj->translation->create(array('identifier'=>$key,'translation'=>$value,'namespace'=>$k));
                        foreach (Ak::langs() as $lang){
                            if($lang !== AK_FRAMEWORK_LANGUAGE) {
                                if(!isset($languageObjects[$lang])) {
                                    if(!$lobj=&$l->findFirstBy('iso',$lang)) {
                                        $lobj=&$l->create(array('iso'=>$lang,'name'=>$lang));
                                    }
                                    $languageObjects[$lang] = &$lobj;
                                }
                                $languageObjects[$lang]->translation->create(array('identifier'=>$key,'translation'=>$value,'namespace'=>$k));
                            }
                        }
                    }
                    
                }
            }else {
                if(!isset($core_dictionary[$k])){
                    
                    $new_core_entries[$k] = $k;
                }
            }
            
        }
       
        
        // Core locale files
        //Ak::getLogger()->log('message','Core dict:'.var_export($core_dictionary,true));
        $new_core_entries=self::_getNewEntries($new_core_entries,$core_dictionary);
        //Ak::getLogger()->log('message','New core entries:'.var_export($new_core_entries,true));
        foreach($new_core_entries as $key=>$value) {
            $trans=$languageObj->translation->create(array('identifier'=>$key,'translation'=>$value,'namespace'=>null));
            foreach (Ak::langs() as $lang){
                if($lang !== AK_FRAMEWORK_LANGUAGE) {
                    if(!isset($languageObjects[$lang])) {
                        if(!$lobj=&$l->findFirstBy('iso',$lang)) {
                            $lobj=&$l->create(array('iso'=>$lang,'name'=>$lang));
                        }
                        $languageObjects[$lang] = &$lobj;
                    }
                    $languageObjects[$lang]->translation->create(array('identifier'=>$key,'translation'=>$value,'namespace'=>null));
                }
            }
        }
        $ran=true;
    }
}
?>