<div id="content_menu">
    <ul class="menu">
    <li>_{Show:}
    <ul>
    <li>{!changed}_{All}{else}<%= link_to _('All'),:action=>'namespaces' %>{end}</li>
    <li>{!changed}<%= link_to_unless_current _('Only changed'),:changed=>1,:action=>'namespaces' %>{else}_{Only changed}{end}</li>
    </ul>
    </li>
    <li>_{Languages}
    <ul>
    {loop allowed_languages as language}
    <li><%= link_to_unless_current language.name, :lang=>language.iso,:action=>'namespaces'%></li>
    {end}
    </ul>
    </li>
    <?php if($CurrentUser->can('Translate:'.$Language->iso,'Admin::DbTranslations')) {?><li><%= link_to _('Create new Translation'), :controller => 'db_translations', :action => 'create_translation'%></li><?php } ?>
        <?php if($CurrentUser->can('Create new language','Admin::DbTranslations')) {?><li><%= link_to _('Create new Language'), :controller => 'db_translations', :action => 'create_language' %></li><?php } ?>
        <?php if($CurrentUser->can('Rebuild translations','Admin::DbTranslations')) {?><li><%= link_to _('Rebuild all namespaces in %lang',{'%lang'=>params-lang}), :controller => 'db_translations', :action => 'rebuild_all', :lang=>Language.iso %></li><?php } ?>
        </ul>
   </div>