(function (Drupal, once) {
  function setCommentPagerPage(form) {
    const input = form.querySelector('input[name="comment_pager_page"]') ||
      form.querySelector('input[name*="comment_pager_page"]');
    if (!input) {
      return;
    }
    const params = new URLSearchParams(window.location.search);
    if (params.has('page')) {
      input.value = params.get('page');
    }
  }

  Drupal.behaviors.socialAjaxCommentsPagerPage = {
    attach(context) {
      once('social-ajax-comments-pager-page', 'form[id^="comment"], form[id*="comment-form"]', context).forEach(setCommentPagerPage);
    },
  };
})(Drupal, once);
