jQuery(document).ready(function($){

  'use strict';

  $('.site-logo').click(function(event){
    e.preventDefault();
    $("html, body").animate({ scrollTop: 0 });
  });

  new UISearch( document.getElementById( 'search-wrapper' ) );

});
