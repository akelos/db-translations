<?php

class Admin_DbTranslationsController extends AdminController {
    var $models = 'DbTranslation,DbTranslationLanguage';
    var $controller_information = 'Db Translations management area.';

    var $xadmin_menu_options = array(
            'Db Translations' => array(
                    'id' => 'dbtranslation',
                    'url' => array(
                            'controller' => 'db_translations',
                            'action' => 'languages')));

    var $controller_menu_options = array(

            'Languages' => array('id' => 'languages',
                    'url' => array(
                            'controller' => 'db_translations',
                            'action' => 'languages')),
            'Namespaces' => array('id' => 'namespaces',
                    'url' => array(
                            'controller' => 'db_translations',
                            'action' => 'namespaces')));

    public $protect_all_actions = false;

    function index()
    {
        return $this->redirectToAction('languages');
    }
    function listing()
    {
        return $this->redirectToAction('languages');
    }
    function languages()
    {
        $this->languages=$this->DbTranslationLanguage->findAll();
        $this->allowed_languages=array();
        foreach((array)$this->languages as $lang) {
            if($this->CurrentUser->can('Translate:'.$lang->iso,'Admin::DbTranslations')) {
                $this->allowed_languages[]=$lang;
            }
        }
        if(count($this->allowed_languages)==1) {
            return $this->redirectTo(array('action'=>'namespaces','lang'=>$this->allowed_languages[0]->iso));
        }
    }

    function create_translation()
    {
        if(!$this->CurrentUser->can('Translate:'.@$this->params['lang'],'Admin::DbTranslations')) {
            $this->renderText('Forbidden',405);
        } else {
            $this->Translation=new DbTranslation();
            $this->Language=&$this->DbTranslationLanguage->findFirstBy('iso',$this->params['lang']);
            $this->Namespaces=$this->DbTranslation->_db->selectValues(array('SELECT distinct IF(namespace IS NULL,"_core_",namespace) FROM db_translations LEFT JOIN db_translation_languages ON db_translations.db_translation_language_id=db_translation_languages.id WHERE db_translation_languages.iso=?',@$this->params['lang']));
            $this->Translation->namespace=isset($this->params['id'])?$this->params['id']:null;
            if($this->Request->isPost()) {

                $this->Translation->namespace=isset($this->params['Translation']['namespace']) && $this->params['Translation']['namespace']!='_core_'?@$this->params['Translation']['namespace']:null;
                if(isset($this->params['Translation']['identifier'])) {
                    $this->params['Translation']['identifier']=preg_replace("/\r\n/","\n",$this->params['Translation']['identifier']);
                }
                $this->Translation->identifier=@$this->params['Translation']['identifier'];
                
                if(isset($this->params['Translation']['translation'])) {
                    $this->params['Translation']['translation']=preg_replace("/\r\n/","\n",$this->params['Translation']['translation']);
                }
                $this->Translation->translation=@$this->params['Translation']['translation'];
                if($this->Language) {
                    $this->Translation->language->assign($this->Language);
                }
                if($this->Translation->save()) {
                    $this->flash['notice']=$this->t('Translation successfully added',null,'application');
                    return $this->redirectTo(array('action'=>'translate','id'=>!empty($this->Translation->namespace)?$this->Translation->namespace:'_core_'));

                } else {
                    var_dump($this->Translation->getErrors());
                }

            }
        }
    }

    function rebuild()
    {
        return $this->renderText('Not implemented right now',404);
        if($this->CurrentUser->can('Rebuild Translations','Admin::DbTranslations')) {
            $translations=&$this->DbTranslation->findAll(array('conditions'=>array('__owner__language.iso=?',$this->params['lang']),'order'=>'namespace ASC','returns'=>'array','include'=>'language'));
            if($translations) {
                $data=array();
                $namespace=false;
                foreach($translations as $trans) {
                    if($namespace!==false && $trans['namespace']!=$namespace) {
                        DbLocaleManager::setDictionary($data,$this->params['lang'],$namespace);
                        $data=array();
                        $namespace=$trans['namespace'];
                    }
                    $data[$trans['identifier']]=$trans['translation'];

                }
                DbLocaleManager::setDictionary($data,$this->params['lang'],$namespace);
            }
        }
    }
function rebuild_everything()
    {
        if($this->CurrentUser->can('Rebuild Translations','Admin::DbTranslations')) {
            $languages=&$this->DbTranslationLanguage->findAll();
            if($languages) {
                foreach($languages as $language) {
                    $language->rebuildAllNamespaces();
                }
                $this->flash['notice']=$this->t('All translations have been rebuilt');

            } else {
                $this->flash['error']=$this->t('Could not rebuild all translations from db');

            }
            return $this->redirectTo(array('action'=>'languages'));
        } else {
            $this->flash['error']=$this->t('You dont have the necessary permissions to rebuild translations');

            return $this->redirectTo(array('action'=>'namespaces','id'=>$this->params['id'],'lang'=>$this->params['lang']));

        }
    }
    function rebuild_all()
    {
        if($this->CurrentUser->can('Rebuild Translations','Admin::DbTranslations')) {
            $language=&$this->DbTranslationLanguage->findFirstBy('iso',$this->params['lang']);
            if($language) {
                
                set_time_limit(-1);
                $language->_db->execute(array('DELETE FROM db_translation_language_settings where db_translation_language_id=?',$language->id));
                $language->_db->execute(array('DELETE FROM db_translations where db_translation_language_id=?',$language->id));
                $language->importLocaleConfigFromFiles();
                $language->importTranslationsFromFiles();
                $this->flash['notice']=$this->t('Namespace %ns has been rebuilt in all languages',array('%ns'=>$this->params['id'],'%lang'=>$this->params['lang']));

            } else {
                $this->flash['error']=$this->t('Namespace %ns could not be rebuild',array('%ns'=>$this->params['id'],'%lang'=>$this->params['lang']));

            }
            return $this->redirectTo(array('action'=>'namespaces','id'=>$this->params['id'],'lang'=>$this->params['lang']));
        } else {
            $this->flash['error']=$this->t('You dont have the necessary permissions to rebuild translations');

            return $this->redirectTo(array('action'=>'namespaces','id'=>$this->params['id'],'lang'=>$this->params['lang']));

        }
    }
    function rebuild_namespace()
    {
        if($this->CurrentUser->can('Rebuild Translations','Admin::DbTranslations')) {
            $language=&$this->DbTranslationLanguage->findFirstBy('iso',$this->params['lang']);
            if($language) {
                $language->rebuildNamespace($this->params['id']);
                $this->flash['notice']=$this->t('All Namespaces have been rebuilt in language %lang',array('%ns'=>$this->params['id'],'%lang'=>$this->params['lang']));

            } else {
                $this->flash['error']=$this->t('Could not rebuild language %lang',array('%ns'=>$this->params['id'],'%lang'=>$this->params['lang']));

            }
            return $this->redirectTo(array('action'=>'namespaces','id'=>$this->params['id'],'lang'=>$this->params['lang']));
        } else {
            $this->flash['error']=$this->t('You dont have the necessary permissions to rebuild translations');

            return $this->redirectTo(array('action'=>'namespaces','id'=>$this->params['id'],'lang'=>$this->params['lang']));

        }
    }
    function namespaces()
    {
        $this->languages=$this->DbTranslationLanguage->findAll();
        $this->allowed_languages=array();
        foreach((array)$this->languages as $lang) {
            if($this->CurrentUser->can('Translate:'.$lang->iso,'Admin::DbTranslations')) {
                $this->allowed_languages[]=$lang;
            }
        }
        $this->Language=&$this->DbTranslationLanguage->findFirstBy('iso',$this->params['lang']);
        if($this->Language) {
            $this->Namespaces=$this->Language->getNamespaces(!empty($this->params['changed']));
        }
        $this->changed=!empty($this->params['changed'])?$this->params['changed']==1:false;
    }
    function create_language()
    {
        if($this->CurrentUser->can('Create Language','Admin::DbTranslations')) {
            $this->Language=new DbTranslationLanguage();
            
            if($this->Request->isPost()) {
                $langs=Ak::langs();
                if(in_array(@$this->params['Language']['iso'],$langs)) {
                    $this->Language->_do_not_recreate_namespaces=true;
                }
                
                $this->Language->_dont_import_from_file=true;
                $this->Language->setAttributes(Ak::pick(array('iso','name'),$this->params['Language']));
                if($this->Language->save()) {
                    $this->flash['notice']=$this->t('Language %lang has been created.',array('%lang'=>$this->Language->iso));
                    return $this->redirectTo(array('action'=>'languages'));
                } else {
                    var_dump($this->Language->getErrors());
                    $this->flash['error']=$this->t('Language %lang could not be created.',array('%lang'=>$this->Language->iso));

                }
            }
        } else {
            $this->flash['error']=$this->t('You dont have permissions to create a language');
            return $this->redirectTo('back');
        }
    }
    function delete()
    {
        $this->Translation=new DbTranslation();
        if(!empty($this->params['id']) && $translation=&$this->Translation->find($this->params['id'],array('include'=>'language')) && $this->CurrentUser->can('Delete Translation:'.$translation->language->iso,'Admin::DbTranslations')) {
            if($translation->destroy()) {
                $this->flash['notice'] = $this->t('Translation <strong>"'.$translation->translation.'"</strong> has been deleted');
            } else {
                $this->flash['error'] = $this->t('Translation <strong>"'.$translation->translation.'"</strong> could not be deleted');
            }
            return $this->redirectTo(array(
              'action'=>'translate',
              'lang'=>$translation->language->iso,
              'id'=>empty($translation->namespace)?'_core_':$translation->namespace,
              'page'=>(!empty($this->params['page'])?$this->params['page']:null),
              'q'=>!empty($this->params['q'])?$this->params['q']:''
            ));
        }
        $this->flash['error'] = $this->t('Translation not found or insufficient permission');
    }

    function delete_language()
    {
        if(!empty($this->params['id']) && $language=&$this->DbTranslationLanguage->find($this->params['id']) && $this->CurrentUser->can('Delete Translation:'.$language->iso,'Admin::DbTranslations')) {
            if($this->Request->isPost()) {
                set_time_limit(0);
                if($language->destroy()) {
                    $this->flash['notice'] = $this->t('Language <strong>"'.$language->iso.'"</strong> has been deleted');
                } else {
                    $this->flash['error'] = $this->t('Language <strong>"'.$language->iso.'"</strong> could not be deleted');
                }
                return $this->redirectTo(array('action'=>'languages','lang'=>AK_FRAMEWORK_LANGUAGE));
            }
            $this->Language=$language;
        } else {
            $this->flash['error'] = $this->t('Language not found or insufficient permission');
        }
    }
    function translate()
    {
        $this->controller_menu_options['Translate <strong>'.@$this->params['id'].'</strong> in language '.@$this->params['lang']]=array('id' => 'languages','url'=>array('controller'=>'db_translations','action'=>'translate','lang'=>@$this->params['lang'],'id'=>@$this->params['id']));
        $this->Namespace=@$this->params['id'];
        if($this->CurrentUser->can('Translate:'.$this->params['lang'],'Admin::DbTranslations')) {

            $this->languages=$this->DbTranslationLanguage->findAll();
            $this->allowed_languages=array();
            foreach((array)$this->languages as $lang) {
                if($this->CurrentUser->can('Translate:'.$lang->iso,'Admin::DbTranslations')) {
                    $this->allowed_languages[]=$lang;
                }
            }
            $this->params['id']=str_replace('-',DS,@$this->params['id']);
            $this->DbTranslationLanguage=&$this->DbTranslationLanguage->findFirstBy('iso',$this->params['lang']);

            $this->search  = !empty($this->params['q'])?$this->params['q']:false;
            if(empty($this->search)) {
                $this->params['q']=null;
            }
            if(empty($this->params['page'])) {
                $this->params['page']=null;
            }
            if(@$this->params['id']=='_core_') {
                if(!empty($this->params['changed'])) {
                    $count_conditions='namespace IS NULL and has_changed_original = '.($this->params['changed']?1:0).' and db_translation_language_id='.$this->DbTranslationLanguage->id;
                    $translation_conditions=array('conditions'=>array('namespace IS NULL AND has_changed_original = ?',$this->params['changed']));
                } else {
                    $count_conditions='namespace IS NULL and db_translation_language_id='.$this->DbTranslationLanguage->id;
                    $translation_conditions=array('conditions'=>array('namespace IS NULL'));
                }
                if(!empty($this->search)) {
                    $count_conditions.=' AND (identifier LIKE '.$this->DbTranslation->_db->quote_string('%'.$this->search.'%').' OR translation LIKE '.$this->DbTranslation->_db->quote_string('%'.$this->search.'%').')';
                    $translation_conditions['conditions'][0].=' AND ( identifier LIKE ? OR translation LIKE ?)';
                    $translation_conditions['conditions'][]='%'.$this->search.'%';
                    $translation_conditions['conditions'][]='%'.$this->search.'%';
                }
            } else {
                if(!empty($this->params['changed'])) {
                    $count_conditions='namespace = '.$this->DbTranslation->_db->quote_string($this->params['id']).' and has_changed_original = '.($this->params['changed']?1:0).' and db_translation_language_id='.$this->DbTranslationLanguage->id;
                    $translation_conditions=array('conditions'=>array('namespace=? AND has_changed_original = ?',$this->params['id'],$this->params['changed']));
                } else {
                    $count_conditions='namespace = '.$this->DbTranslation->_db->quote_string($this->params['id']).'  and db_translation_language_id='.$this->DbTranslationLanguage->id;
                    $translation_conditions=array('conditions'=>array('namespace=?',$this->params['id']));
                }
                if(!empty($this->search)) {
                    $count_conditions.=' AND (identifier LIKE '.$this->DbTranslation->_db->quote_string('%'.$this->search.'%').' OR translation LIKE '.$this->DbTranslation->_db->quote_string('%'.$this->search.'%').')';
                    $translation_conditions['conditions'][0].=' AND ( identifier LIKE ? OR translation LIKE ?)';
                    $translation_conditions['conditions'][]='%'.$this->search.'%';
                    $translation_conditions['conditions'][]='%'.$this->search.'%';
                }
            }
            $this->changed=!empty($this->params['changed'])?$this->params['changed']==1:false;
            $this->translation_pages = $this->pagination_helper->getPaginator($this->DbTranslation, array('items_per_page' => 100,'count_conditions'=>$count_conditions));
            $options = $this->pagination_helper->getFindOptions($this->DbTranslation);

            if(!empty($this->params['lang']) && $language=&$this->DbTranslationLanguage->findFirstBy('iso',$this->params['lang'],array('include'=>array('translations'=>$translation_conditions),'returns'=>'array','limit'=>$options['limit'],'offset'=>$options['offset']))) {
                $this->Language=$language;
                $this->DbTranslation->language->assign(new DbTranslationLanguage($language['id']));
                $this->Translations=$language['translations'];

                if($this->Request->isPost()) {
                    $Translations = (array)@$this->params['Translations'];
                    $Changed=(array)@$this->params['Changed'];
                    foreach($this->Translations as $idx=>$data) {
                        if(isset($Translations[$data['id']])) {
                            $Translations[$data['id']]=preg_replace("/\r\n/","\n",$Translations[$data['id']]);
                        }
                        if(@$Translations[$data['id']] != $data['translation'] || !empty($Changed[$data['id']])) {
                            $this->DbTranslation->original_translation = $data['translation'];
                            $data['translation']=@$Translations[$data['id']];
                            $data['load_associations']=true;

                            $this->DbTranslation->setAttributes(Ak::delete($data,array('created_at','updated_at')),true);

                            $this->DbTranslation->_newRecord=false;
                            $res=$this->DbTranslation->save();
                            $data['has_changed_original']=$this->DbTranslation->has_changed_original;
                            $this->Translations[$idx]=$data;
                        }
                    }
                    if($this->changed) {
                        return $this->redirectTo(array('action'=>'translate','changed'=>1,'id'=>$this->Namespace));
                    }
                }

            } else {
                if(!$this->changed && empty($this->search)) {
                    return $this->renderText('Language not found',404);
                } else {
                    $this->Language=&$this->DbTranslationLanguage->findFirstBy('iso',@$this->params['lang'],array('returns'=>'array'));
                }
            }
        } else {
            die('User cannot translate:'.$this->params['lang']);
        }
    }

    function configure()
    {
        if($this->CurrentUser->can('Configure:'.$this->params['lang'],'Admin::DbTranslations')) {

            if(!empty($this->params['lang']) && $language=$this->DbTranslationLanguage->findFirstBy('iso',$this->params['lang'],array('include'=>array('settings'),'returns'=>'array'))) {
                $this->Language=$language;
                $this->Settings=$language['settings'];
                Ak::trace($this->Settings);
                die;
                $this->Namespace=$this->params['id'];
                if($this->Request->isPost()) {
                    $Translations = (array)@$this->params['Translations'];
                    foreach($this->Translations as $idx=>$data) {
                        if($Translations[$data['id']] != $data['translation']) {
                            $data['translation']=$Translations[$data['id']];
                            $translation=new DbTranslation($data);
                            $translation->language->build($language,false);
                            $translation->save();
                            $this->Translations[$idx]=$data;
                        }
                    }
                }

            } else {
                return $this->renderText('Language not found',404);
            }
        }
    }
}
?>
