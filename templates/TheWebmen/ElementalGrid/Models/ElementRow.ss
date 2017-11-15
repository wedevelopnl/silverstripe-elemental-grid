<% if not $Controller.IsFirstRow %>
    </div>
<% end_if %>
<% if not $Controller.IsLastRow %>
<div class="row<% if $ExtraClass %> $ExtraClass<% end_if %>">
<% end_if %>
