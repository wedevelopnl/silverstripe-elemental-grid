(function($) {
  $.entwine('ss', function($) {

    $('.grideditor').entwine({
      onmatch: function () {
        this.find('tbody').addClass('row');
      }
    });

    $('select.grideditor-sizefield').entwine({
      onmatch: function () {
        this.trigger('change');
      },

      onchange: function () {
        var _this = $(this);
        var _val = _this.val();
        var _tr = _this.closest('tr');
        _tr[0].className = _tr[0].className.replace(/\bcol-md-.*?\b/g, '');
        _tr.addClass('col-md-' + _val);
      }
    });

  });
})(jQuery);
