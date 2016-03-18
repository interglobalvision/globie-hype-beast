console.log('Globie Hype Beast Initiated');

var GlobieHypeBeast = {
  urlLoaded: function(url) {
    var data = {
      'action': 'incr_page_views',
      'permalink': url
    };

    jQuery.post(IGV_Hype_Vars.ajaxurl, data);
  }
}

jQuery(document).ready(function($) {
  if( $('body').hasClass('single') && !IGV_Hype_Vars.isAdmin ) {
    GlobieHypeBeast.urlLoaded(document.location.href);
  }
});