<div class="$ElementClasses">
    <% if $MediaImage || $MediaVideoFullURL || $MediaVideoEmbeddedURL %>
        <div class="$MediaColumnClasses">
        <% if $MediaType == 'image' && $MediaImage %>
            <% if $MediaImage %>
                <% if $MediaCaption %><div class="captionImage"><% end_if %>
                <figure class="image<% if $MediaRatioClass %> $MediaRatioClass<% end_if %>">
                    <img src="$MediaImageSourceURL" alt="$MediaImage.Title" width="$MediaImageWidth" height="$MediaImageHeight" class="is-block" loading="lazy">
                </figure>
                <% if $MediaCaption %><p class="caption leftAlone">$MediaCaption</p><% end_if %>
                <% if $MediaCaption %></div><% end_if %>
            <% end_if %>
        <% else_if $MediaType == 'video' && $MediaVideoFullURL && $MediaVideoEmbeddedURL %>
            <% if $MediaCaption %><div class="captionImage mb-0"><% end_if %>
            <figure class="video image<% if $MediaRatioClass %> $MediaRatioClass<% end_if %>" data-video-embed-url="$MediaVideoEmbeddedURL" data-element-id="$ID" data-video-type="$MediaVideoProvider">
                <div class="video-thumbnail<% if $MediaVideoHasOverlay %> has-overlay<% end_if %>" id="playVideo-$ID" style="background-image: url('<% if $MediaVideoCustomThumbnail %>$MediaVideoCustomThumbnail.FocusFill(1440,800).URL<% else %>$MediaVideoEmbeddedThumbnail<% end_if %>')">
                    <span class="btn btn-primary btn-square button is-primary is-square">
                        <span class="svg-icon">
                            <% include Icons\Includes\PlayRegular %>
                        </span>
                    </span>
                </div>
                <div class="video-wrapper" id="player-$ID"></div>
            </figure>
            <% if $MediaCaption %><p class="caption leftAlone mb-0">$MediaCaption</p><% end_if %>
            <% if $MediaCaption %></div><% end_if %>
            <script type="application/ld+json">
            {
                "@context": "http://schema.org",
                "@type": "VideoObject",
                "playerType": "HTML5",
                "embedUrl": "$MediaVideoEmbeddedURL",
                "name": "$MediaVideoEmbeddedName",
                "description": "$MediaVideoEmbeddedDescription",
                "thumbnailUrl": "$MediaVideoEmbeddedThumbnail",
                "uploadDate": "$MediaVideoEmbeddedCreated"
            }
        </script>
        <% end_if %>
    </div>
    <% end_if %>
    <% if $Title || $HTML %>
        <div class="$ContentColumnClasses">
            <div class="$ContentClasses">
                <% if $ShowTitle && $Title %>
                    <$TitleTag class="$TitleSizeClass">$Title.RAW</$TitleTag>
                <% end_if %>
                $HTML
            </div>
        </div>
    <% end_if %>
</div>
