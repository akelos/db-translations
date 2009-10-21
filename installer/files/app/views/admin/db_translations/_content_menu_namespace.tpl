<div id="content_menu">
    <ul class="menu">
    <li>_{Show:}
    <ul>
    <li>{!changed}_{All}{else}<%= link_to _('All'),:lang=>Language-iso,:action=>'translate',:id=>Namespace %>{end}</li>
    <li>{!changed}<%= link_to _('Only changed'),:changed=>1,:action=>'translate',:id=>Namespace %>{else}_{Only changed}{end}</li>
    </ul>
    </li>
    <li>_{Search:}
    <?php $changed_param=$changed?1:0;?>
    <%= start_form_tag({:action => 'translate', :id => Namespace, :lang=>Language-iso,:changed=>changed_param},{'accept-charset'=>"UTF-8",'method'=>'GET'}) %>
    <ul>
    <li><input type="text" name="q" /></li>
    <li><input type="submit" value="_{Search}" /></li>
    </ul>
    </form>
    </li>
    <li>_{Languages}
    <ul>
    {loop allowed_languages as language}
    <li><%= link_to_unless_current language.name, :lang=>language.iso,:action=>'translate',:id=>Namespace,:q=>params-q,:page=>params-page%></li>
    {end}
    </ul>
    </li>
    <?php if($CurrentUser->can('Translate:'.$Language['iso'],'Admin::DbTranslations')) {?><li><%= link_to _('Create new Translation'), :controller => 'db_translations', :action => 'create_translation',:id=>Namespace %></li><?php } ?>
        <?php if($CurrentUser->can('Create new language','Admin::DbTranslations')) {?><li><%= link_to _('Create new Language'), :controller => 'db_translations', :action => 'create_language' %></li><?php } ?>
        <?php if($CurrentUser->can('Rebuild translations','Admin::DbTranslations')) {?><li><%= link_to _('Rebuild namespace in %lang',{'%lang'=>params-lang}), :controller => 'db_translations', :action => 'rebuild_namespace', :lang=>Language-iso,:id=>Namespace %></li><?php } ?>
        </ul>
   </div>