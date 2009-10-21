<%= render :partial => 'content_menu_namespaces' %>
<div class="content">
    {?Namespaces}
    <h1>_{Listing available Namespaces in <strong>%Language.name</strong>}</h1>
    
    
    <div class="listing">
        <table cellspacing="0" summary="_{Listing available Namespaces}">

            <tr>
                <th scope="col">_{Namespace}</th>
                <th scope="col"><span class="auraltext">_{Item actions}</span></th>
            </tr>

            {loop Namespaces}
            <?php $Namespace=empty($Namespace)?'_core_':$Namespace;
            if($Namespace=='_core_' && !$CurrentUser->can('Translate Core:'.$Language->iso,'Admin::DbTranslations')) continue;
            $Namespace=str_replace(DS,'-',$Namespace);
            ?>
            <tr {?Translation_odd_position}class="odd"{end}>
            
                <td class="field">{Namespace}</td>
                <td class="operation">
                {?changed}
                <%= link_to _('Translate'),:controller=>'db_translations',:action=>'translate',:lang=>Language.iso,:id=>Namespace,:changed=>changed %>
                {else}
                <%= link_to _('Translate'),:controller=>'db_translations',:action=>'translate',:lang=>Language.iso,:id=>Namespace %>
                {end}    
            <?php if($CurrentUser->can('Rebuild translations','Admin::DbTranslations')) {?><%= link_to _('Rebuild'), :controller => 'db_translations', :action => 'rebuild_namespace', :lang=>Language.iso,:id=>Namespace %><?php } ?>
                </td>
            </tr>
            {end}
        </table>
    </div>


    {else}

     {?changed}
        <h1>_{No Translations have been changed in the original language.}</h1>
        {else}
    <h1>_{No Namespaces available yet.}</h1>
    {end}

    {end}

</div>












