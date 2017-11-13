(function ($) {
  $.entwine('ss', function ($) {

    /**
     * Add row class to the grid table
     */
    $('.grideditor').entwine({
      onmatch: function () {
        this.find('tbody').addClass('row');
      }
    });

    /**
     * Handle col size classes
     */
    $('select.grideditor-sizefield').entwine({
      onmatch: function () {
        this.trigger('change');
      },

      onchange: function () {
        var _this = $(this);
        var _val = _this.val();
        var _tr = _this.closest('tr');
        if (_tr.data('class').indexOf('ElementRow') != -1) {
          _tr.addClass('col-md-12');
        } else {
          //Remove old col class
          _tr[0].className = _tr[0].className.replace(/\bcol-md-.*?\b/g, '');
          //Add new col class
          _tr.addClass('col-md-' + _val);
        }
      }
    });

  });
})(jQuery);
