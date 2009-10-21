<%= start_form_tag {:action =>'create_language'}, :id => 'language_form' %>
    <div class="form">

<%= error_messages_for 'Language' %>

<fieldset>
    <label class="required" for="iso">_{Iso}</label>
    <%= input 'Language', 'iso', :tabindex => '1' %>
</fieldset>
<fieldset>
    <label class="required" for="name">_{Name}</label>
    <%= input 'Language', 'name', :tabindex => '2' %>
</fieldset>
<div id="operations">
        <input type="submit" value="_{Create}" />
    </div>
    </div>
</form>