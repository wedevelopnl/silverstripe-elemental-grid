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
    <div class="container<% if $IsFluid %> $FluidContainerClass<% end_if %>">
    <div class="$RowClasses">
<% end_if %>
