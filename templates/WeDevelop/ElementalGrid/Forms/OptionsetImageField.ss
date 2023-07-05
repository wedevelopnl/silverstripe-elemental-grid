<div $AttributesHTML <% include SilverStripe/Forms/AriaAttributes %>>
    <% loop $Options %>
        <div class="radio form-check $Class">
            <label class="form-check-label">
                <input class="form-check-input" id="$ID" name="$Name" type="radio" value="$Value"<% if $isChecked %> checked<% end_if %><% if $isDisabled %> disabled<% end_if %> <% if $Up.Required %>required<% end_if %> />
                <% if $Value %>$Value out of 12 cols<% else %>Full width<% end_if %><br />
                <img src="/_resources/vendor/wedevelopnl/silverstripe-elemental-grid/client/images/alignments/{$Title}" width="108" height="29" alt="<% if $Value %>$Value<% else %>default<% end_if %>" />
            </label>
        </div>
    <% end_loop %>
</div>
