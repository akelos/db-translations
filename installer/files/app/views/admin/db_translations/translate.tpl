<%= error_messages_for 'DbTranslation' %>
<%= render :partial => 'content_menu_namespace' %>
<div id="content">
  <h1>_{Editing Translations for %Language-name}</h1>
  
{?changed}
  <%= start_form_tag({:action => 'translate', :id => Namespace, :lang=>Language-iso,:changed=>1,:page=>params-page ,:q => params-q },{'accept-charset'=>"UTF-8"}) %>
{else}
  <%= start_form_tag({:action => 'translate', :id => Namespace, :lang=>Language-iso,:page=>params-page , :q => params-q},{'accept-charset'=>"UTF-8"}) %>
{end}


  <div class="form">
    <%= render :partial => 'form' %>
  </div>

    <div id="operations">
      <input type="submit" value="_{Save}" /> _{or}  <%= link_to _('Cancel'),:action=>'namespaces' %>
    </div>

  </form>
</div>