# Social Core

Core module for the Open Social distribution providing shared utilities and
behaviors used across the platform.

## JavaScript Libraries

### ajax-submit-guard (`social_core/ajax-submit-guard`)

A client-side workaround for [Drupal core issue #1736308](https://www.drupal.org/project/drupal/issues/1736308).

#### Problem

When a form element uses `#ajax` with `'event' => 'blur'`, Drupal's AJAX system
temporarily disables the triggering element while the request is in flight. If
the user clicks **Submit** at exactly that moment, the disabled field's value is
excluded from the POST data and silently lost.

#### How it works

The guard listens for jQuery global AJAX events (`ajaxSend` / `ajaxComplete`)
scoped to the guarded form. While an AJAX request is in progress:

1. Any `submit` event on the form is intercepted in the **capture phase**
   (`preventDefault` + `stopImmediatePropagation`).
2. The clicked submit button is stored.
3. Once `ajaxComplete` fires, the guard replays the deferred submit via
   `requestAnimationFrame` so Drupal's AJAX cleanup (re-enabling fields,
   removing throbbers) finishes first.

The guard matches the completing request by **`jqXHR` identity** rather than by
DOM lookup or trigger name, so it correctly resets even when the AJAX response
removes the triggering element from the page (e.g. Inline Entity Form replacing
its "Create media item" button with the saved entity row) and cannot be falsely
released by an unrelated request that happens to share the same triggering
element name.

When the guard activates, it logs a message to the browser console:
```
[ajax-guard] Form submit deferred - AJAX request in progress for: <element_name>
```

#### Usage

In a form alter or form builder, add the `data-ajax-guard` attribute and attach
the library:

```php
$form['#attributes']['data-ajax-guard'] = TRUE;
$form['#attached']['library'][] = 'social_core/ajax-submit-guard';
```

Then add `#ajax` callbacks with `'event' => 'blur'` to any form elements that
need it. The guard will automatically protect the form's submit buttons.

#### Current usage

The library is attached by `EventOnline::meetingApiEntityScheduler()` on the
event node form, where date and max-attendees fields use blur-triggered AJAX to
refresh the meeting scheduler widget.
