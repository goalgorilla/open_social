/*
 * jQuery dropdown: A simple dropdown plugin
 *
 * Inspired by Bootstrap: http://twitter.github.com/bootstrap/javascript.html#dropdowns
 * Copyright 2013 Cory LaViska for A Beautiful Site, LLC. (http://abeautifulsite.net/)
 * Dual licensed under the MIT / GPL Version 2 licenses
 *
*/
if(jQuery) (function($) {

    $.extend($.fn, {
        panel: function(method, data) {

            switch( method ) {
                case 'hide':
                    hide();
                    return $(this);
                case 'attach':
                    console('test');
                    return $(this).attr('data-panel', data);
                case 'detach':
                    hide();
                    return $(this).removeAttr('data-panel');
                case 'disable':
                    return $(this).addClass('panel-disabled');
                case 'enable':
                    hide();
                    return $(this).removeClass('panel-disabled');
            }

        }
    });

    function show(event) {

        var trigger = $(this),
            panel = $(trigger.attr('data-panel')),
            isOpen = trigger.hasClass('panel-open');

        // In some cases we don't want to show it
        if( trigger !== event.target && $(event.target).hasClass('panel-ignore') ) return;

        event.preventDefault();
        event.stopPropagation();
        hide();

        if( isOpen || trigger.hasClass('panel-disabled') ) return;

        // Show it
        trigger.addClass('panel-open');
        panel
            .data('panel-trigger', trigger)
            .addClass('show');

        // Trigger the show callback
        panel
            .trigger('show', {
                panel: panel,
                trigger: trigger
            });

    }

    function hide(event) {

        // In some cases we don't hide them
        var targetGroup = event ? $(event.target).parents().addBack() : null;

        // Are we clicking anywhere in a panel?
        if( targetGroup && targetGroup.is('.panel') ) {
            // Is it a panel menu?
            if( targetGroup.is('.panel-list') ) {
                // Did we click on an option? If so close it.
                if( !targetGroup.is('a') ) return;
            } else {
                // Nope, it's a panel. Leave it open.
                return;
            }
        }

        // Hide any panel that may be showing
        $(document).find('.panel:visible').each( function() {
            var panel = $(this);
            panel
                .removeClass('show')
                .removeData('panel-trigger')
                .trigger('hide', { panel: panel });
        });

        // Remove all panel-open classes
        $(document).find('.panel-open').removeClass('panel-open');

    }


    $(document).on('click.panel', '[data-panel]', show);
    $(document).on('click.panel', hide);

})(jQuery);
