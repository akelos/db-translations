<?php
require_once(AK_LIB_DIR.DS.'AkLocaleManager.php');
require_once(AK_BASE_DIR.DS.'app'.DS.'vendor'.DS.'plugins'.DS.'db_translations'.DS.'lib'.DS.'DbLocaleManager.php');

class DbTranslationsTest extends AkUnitTest
{
    function test_start()
    {
        $this->uninstallAndInstallMigration('DbTranslationsPlugin');
        $this->includeAndInstatiateModels('DbTranslationLanguage,DbTranslation,DbTranslationLanguageSetting');
    }
    
    function test_create_new_language_with_empty_iso()
    {
        $language=&$this->DbTranslationLanguage->create(array('name'=>'Empty'));
        $this->assertTrue($language->hasErrors());
        $errorsOnIso=$language->getErrorsOn('iso');
        $this->assertTrue(!empty($errorsOnIso));
        
        $language=&$this->DbTranslationLanguage->create(array('name'=>'Empty','iso'=>'  '));
        $this->assertTrue($language->hasErrors());
        $errorsOnIso=$language->getErrorsOn('iso');
        $this->assertTrue(!empty($errorsOnIso));
    }
    
    function test_create_and_delete_language()
    {
        $language=&$this->DbTranslationLanguage->create(array('name'=>'Empty','iso'=>'ii'));
        $this->assertFalse($language->hasErrors());
        $this->assertFalse($language->isNewRecord());
        clearstatcache();
        $this->assertTrue(file_exists(AK_APP_DIR.DS.'locales'.DS.'db_translation'.DS.'ii.php'));
        $this->assertTrue(file_exists(AK_CONFIG_DIR.DS.'locales'.DS.'ii.php'));
        
        $this->assertTrue($language->destroy());
        clearstatcache();
        $this->assertFalse(file_exists(AK_APP_DIR.DS.'locales'.DS.'db_translation'.DS.'ii.php'));
        $this->assertFalse(file_exists(AK_CONFIG_DIR.DS.'locales'.DS.'ii.php'));
    }
    
    function test_locale_setting()
    {
        $language=&$this->DbTranslationLanguage->create(array('name'=>'AA','iso'=>'aa'));
        $this->assertTrue($language);
        
        $this->assertFalse($language->isNewRecord());
        $setting=&$language->setting->create(array('name'=>'test','value'=>1));
        $this->assertTrue($setting);
        $this->assertFalse($setting->isNewRecord());
        $this->assertEqual($setting->value,1);
        list($locales,$core_dict)=AkLocaleManager::getCoreDictionary('aa');
        $this->assertEqual($locales[$setting->name],$setting->value);
        $setting2=&$language->setting->create(array('name'=>'test2','value'=>array('test','test2',3)));
        $this->assertTrue($setting2);
        $this->assertFalse($setting2->isNewRecord());
        $this->assertEqual($setting2->value,array('test','test2',3));
        list($locales,$core_dict)=AkLocaleManager::getCoreDictionary('aa');
        $this->assertEqual($locales[$setting->name],$setting->value);
        $this->assertEqual($locales[$setting2->name],$setting2->value);
        
        //$this->assertTrue($setting->destroy());
        //$this->assertTrue($setting2->destroy());
        $this->assertTrue($language->destroy());
    }
}
?>