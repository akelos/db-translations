<div class="content">
<h1>_{Deleting Language}</h1>
<p class="warning">_{Are you sure you want to delete this Language?}</p>

<%=  start_form_tag :action => 'delete_language', :id => Language.id %>

    <dl>
        <?php  $content_columns = array_keys($Language->getContentColumns()); ?>
        {loop content_columns}
          <dt><%= translate( titleize( content_column ) ) %>:</dt>
          <dd><?php  echo  $Language->get($content_column) ?>&nbsp;</dd>
        {end}
    </dl>

    <div id="operations">
        <input type="submit" value="_{Delete}" />
    </div>
  </form>
</div>