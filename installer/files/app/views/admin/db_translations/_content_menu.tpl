<div id="content_menu">
    <ul class="menu">
        <?php if($CurrentUser->can('Create new language','Admin::DbTranslations')) {?><li><%= link_to _('Create new Language'), :controller => 'db_translations', :action => 'create_language' %></li><?php } ?>
        <?php if($CurrentUser->can('Rebuild translations','Admin::DbTranslations')) {?><li><%= link_to _('Rebuild all translation files from db'), :controller => 'db_translations', :action => 'rebuild_everything' %></li><?php } ?>
        </ul>
   </div>