<h1>_{Create a new Translation entry in language %Language.iso}</h1>
<%= start_form_tag {:action =>'create_translation'}, :id => 'create_form' %>
    <div class="form">

<%= error_messages_for 'Translation' %>
<fieldset>
<label class="required" for="namespace">_{Namespace}</label>
<?= $form_options_helper->select('Translation','namespace',$Namespaces,array(),array('prompt'=>true))?>
</fieldset>
<fieldset>
    <label class="required" for="iso">_{Identifier}</label>
    <%= input 'Translation', 'identifier', :tabindex => '1' %>
</fieldset>
<fieldset>
    <label class="required" for="name">_{Translation}</label>
    <%= input 'Translation', 'translation', :tabindex => '2' %>
</fieldset>
<div id="operations">
        <input type="submit" value="_{Create}" />
    </div>
    </div>
</form>