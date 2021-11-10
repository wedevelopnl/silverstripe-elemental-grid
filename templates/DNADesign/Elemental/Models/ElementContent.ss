<div class="content-element__content<% if $Style %> $StyleVariant<% end_if %>">
	<% if $ShowTitle %>
        <$TitleTag class="$TitleSizeClass content-element__title">$Title.RAW</$TitleTag>
    <% end_if %>
    $HTML
</div>
