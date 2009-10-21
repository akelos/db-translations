<%= render :partial => 'content_menu' %>
<div class="content">
{?allowed_languages}
<div class="listing">
  <table cellspacing="0" summary="_{Available Languages}">
  <tr class="multiple">
  <th>_{Language iso}</th>
  <th>_{Language name}</th>
  <th></th>
  </tr>
{loop allowed_languages as language}

  <tr {?language_odd_position}class="odd"{end}>
  <td>{language.iso?}</td>
  <td>{language.name?}</td>
  <td><?php if($CurrentUser->can('Translate:'.$language->iso,'Admin::DbTranslations')) {?><%= link_to _('Translate'), :action=>'namespaces',:lang=>language.iso %><?php }?>
<?php if($CurrentUser->can('Delete Translation:'.$language->iso,'Admin::DbTranslations')) {?> <%= link_to _('Delete'), :action=>'delete_language',:id=>language.id %> <?php }?>
<?php if($CurrentUser->can('Rebuild translations','Admin::DbTranslations')) {?><%= link_to _('Rebuild all translation files from db'), :controller => 'db_translations', :action => 'rebuild', :lang=>language.iso %><?php } ?></td>
  </tr>
  
{end}
</table>
</div>
{end}
</div>