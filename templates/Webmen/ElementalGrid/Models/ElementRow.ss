<% if not $Controller.IsFirstRow %>
    </div>
    </div>
    </section>
<% end_if %>
<% if not $Controller.IsLastRow %>
    <section class="section"
        <% if $RowID %>
             id="$RowID"
        <% end_if %>>
    <div class="container">
    <div class="$RowClasses">
<% end_if %>
