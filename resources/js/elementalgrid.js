(function ($) {
  $.entwine('ss', function ($) {

    /**
     * Add row class to the grid table
     */
    $('.grideditor tbody').entwine({
      onmatch: function () {
        this.addClass('row');
      }
    });

    /**
     * Types
     */
    $('.col-BlockType').entwine({
      onmatch: function () {
        this.closest('tr').addClass('block-type-' + this.find('input').val());
      }
    });

    /**
     * Handle col size classes
     */
    $('select.grideditor-sizefield, select.grideditor-offsetfield').entwine({
      onmatch: function () {
        this.trigger('change');
        var _this = $(this);
        var _tr = _this.closest('tr');
        var _sizeField = _tr.find('select.grideditor-sizefield');
        if(_sizeField.data('title')){
          _sizeField.closest('td').addClass('col-sizefield').prepend('<span class="grideditor-field-label">'+_sizeField.data('title')+'</span>');
          _sizeField.data('title', false);
        }
        var _offsetField = _tr.find('select.grideditor-offsetfield');
        if(_offsetField.data('title')){
          _offsetField.closest('td').addClass('col-offsetfield').prepend('<span class="grideditor-field-label">'+_offsetField.data('title')+'</span>');
          _offsetField.data('title', false);
        }
      },

      onchange: function () {
        var _this = $(this);
        var _tr = _this.closest('tr');
        var _size = _tr.find('select.grideditor-sizefield').val();
        var _offset = _tr.find('select.grideditor-offsetfield').val();
        if (_tr.find('.col-BlockType input').val() == 'full-width') {
          _tr.addClass('col-md-12');
        } else {
          //Remove old col class
          _tr.removeClass(function (index, className) {
            return (className.match(/(^|\s)col-md-\S+/g) || []).join(' ');
          });
          _tr.removeClass(function (index, className) {
            return (className.match(/(^|\s)offset-md-\S+/g) || []).join(' ');
          });
          // _tr[0].className = _tr[0].className.replace(/\bcol-md-.*?\b/g, '');
          // _tr[0].className = _tr[0].className.replace(/\boffset-md-.*?\b/g, '');
          //Add new col class
          _tr.addClass('col-md-' + _size);
          if (_offset && _offset != '0') {
            _tr.addClass('offset-md-' + _offset);
          }
        }
      }
    });

  });
})(jQuery);
