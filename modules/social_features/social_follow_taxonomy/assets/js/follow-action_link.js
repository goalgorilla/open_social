(function (Drupal) {
  Drupal.behaviors.flagAttach = {
    attach: function attach(context) {
      var links = context.querySelectorAll('.flag-follow-term a');
      links.forEach(function (link) {
        return link.addEventListener('click', function (event) {

          if(event.target.closest('.flag-follow-term').classList.contains('action-flag')) {
            event.target.closest('.group-action').querySelector('a.btn-action__term').classList.add('term-followed');
          }
          if(event.target.closest('.flag-follow-term').classList.contains('action-unflag')) {
            event.target.closest('.group-action').querySelector('a.btn-action__term').classList.remove('term-followed');
          }
        });
      });
    }
  };
})(Drupal);