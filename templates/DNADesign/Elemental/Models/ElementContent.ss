<div class="$ElementClasses">
    <% if $MediaImage %>
        <div class="$MediaColumnClasses">
            <% if $MediaCaption %><div class="captionImage"><% end_if %>
            <figure class="image">
                <% if $ColSize > 10 %>
                    <img src="$MediaImage.ScaleMaxWidth(1440).URL" alt="$MediaImage.Title" width="1440" height="$MediaImage.ScaleMaxWidth(1440).Height" class="is-block" loading="lazy">
                <% else_if $ColSize > 6 %>
                    <img src="$MediaImage.ScaleMaxWidth(1200).URL" alt="$MediaImage.Title" width="1200" height="$MediaImage.ScaleMaxWidth(1200).Height" class="is-block" loading="lazy">
                <% else %>
                    <img src="$MediaImage.ScaleMaxWidth(720).URL" alt="$MediaImage.Title" width="720" height="$MediaImage.ScaleMaxWidth(720).Height" class="is-block" loading="lazy">
                <% end_if %>
            </figure>
            <% if $MediaCaption %><p class="caption leftAlone">$MediaCaption</p><% end_if %>
            <% if $MediaCaption %></div><% end_if %>
        </div>
    <% end_if %>
    <div class="$ContentColumnClasses">
        <div class="content{$MarginStyles}">
            <% if $ShowTitle %>
                <$TitleTag class="$TitleSizeClass">$Title.RAW</$TitleTag>
            <% end_if %>
            $HTML
        </div>
    </div>
</div>
