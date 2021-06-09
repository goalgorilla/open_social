(function (Drupal) {
  Drupal.behaviors.flagAttach = {
    attach: function attach(context) {
      var links = context.querySelectorAll('.flag-follow-term a');
      links.forEach(function (link) {
        return link.addEventListener('click', function (event) {

          if(event.target.closest('.flag-follow-term').classList.contains('action-flag')) {
            // Increase the number of followers when the user click on follow term button.
            event.target.closest('.teaser__content').querySelector('.teaser__followers-count').innerHTML =
              event.target.closest('.teaser__content').querySelector('.teaser__followers-count').innerHTML.replace(/[0-9]+/, function(n){ return ++n });

            event.target.closest('.group-action').querySelector('a.btn-action__term').classList.add('term-followed');
          }
          if(event.target.closest('.flag-follow-term').classList.contains('action-unflag')) {
            // Decrease the number of followers when the user click on unfollow term button.
            event.target.closest('.teaser__content').querySelector('.teaser__followers-count').innerHTML =
              event.target.closest('.teaser__content').querySelector('.teaser__followers-count').innerHTML.replace(/[0-9]+/, function (n) { return (n>0) ? --n : n; });

            event.target.closest('.group-action').querySelector('a.btn-action__term').classList.remove('term-followed');
          }
        });
      });
    }
  };
})(Drupal);