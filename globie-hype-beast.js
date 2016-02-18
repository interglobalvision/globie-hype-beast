console.log('Globie Hype Beast');
jQuery(document).ready(function($) {
  if( $('body').hasClass('single') ) {
    var data = {
      'action': 'incr_page_views',
      'permalink': document.location.href
    };
    // We can also pass the url value separately from ajaxurl for front end AJAX implementations
    jQuery.post(IGV_Hype.ajaxurl, data);
  }
});
