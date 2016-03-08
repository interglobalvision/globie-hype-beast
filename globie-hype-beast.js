console.log('Globie Hype Beast Initiated');

var GlobieHypeBeast = {
  urlLoaded: function(url) {
    var data = {
      'action': 'incr_page_views',
      'permalink': url
    };

    jQuery.post(IGV_Hype.ajaxurl, data);
  }
}

jQuery(document).ready(function($) {
  if( $('body').hasClass('single') ) {
    GlobieHypeBeast.urlLoaded(document.location.href);
  }
});