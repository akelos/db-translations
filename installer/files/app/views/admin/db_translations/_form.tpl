    <style>
.changed-original {
background-color:orange !important;

}

.changed-original:before {
display:block;
content: 'original translation changed:';
    padding-right: 5px;
    font-style: italic;
    color: white;
    font-weight:bold;
}
    </style>
    {?search}
    <h1>_{Results for term "%search"}</h1>
    <h2><a href="<?= $url_helper->modify_current_url(array(),array('q')); ?>">_{Show all}</a></h2>
    {end}
    {?Translations}
    <div class="listing">
        <table cellspacing="0" summary="_{Listing available Translations}">

            <tr>
                <th scope="col">_{Original}</th>
                <th scope="col">_{Translation}</th>
                <th scope="col">_{Delete}</th>
            </tr>

            {loop Translations}
            <tr class="{?Translation_odd_position}odd{end}{?Translation-has_changed_original} changed-row{end}">
                <td class="field{?Translation-has_changed_original} changed-original{end}">
    <textarea disabled="disabled" rows="5" cols="70"><?=Ak::lang()==AK_FRAMEWORK_LANGUAGE?$Translation['identifier']:$DbTranslation->getOriginalTranslation(AK_FRAMEWORK_LANGUAGE,$Translation['identifier'],$Translation['namespace']);?></textarea>
    
    </td>
                <td class="field">
                    
                    <textarea name="Translations[{Translation-id}]" rows="5" cols="70">{Translation-translation?}</textarea>
                {?Translation-has_changed_original}<input type="checkbox" name="Changed[{Translation-id}]" />_{Does not need a changed translation}{end}
                </td>
                <td class="operation"><%= link_to _('Delete'),:action=>'delete',:id=>Translation-id %></td>
            </tr>
            {end}
        </table>
    </div>

    {?translation_pages.links}
    <div class="paginator">
        <div style="padding-bottom:20px;"><?php  echo translate('Showing page %page of %number_of_pages',array('%page'=>$translation_pages->getCurrentPage(),'%number_of_pages'=>$translation_pages->pages))?></div>
        {translation_pages.links?}
    </div>
    {end}

    {else}
        {?changed}
        <h1>_{No Translations have been changed in the original language.}</h1>
        {else}
        {?search}
        <h1>_{No Translations found with term "%search" available yet.}</h1>
        {else}
    <h1>_{No Translations available yet.}</h1>
    {end}
    {end}

    {end}
