jQuery(document).ready(function($) {
  $('tr.active[data-plugin*="loco-automatic-translate-addon-pro"]').each(function() {
    var $currentRow = $(this);
    var $nextUpdateRow = $currentRow.nextAll('tr.plugin-update-tr.active.atlt-pro').first();

    if ($nextUpdateRow.length > 0) {
      $currentRow.addClass('update');
    }
  });
});