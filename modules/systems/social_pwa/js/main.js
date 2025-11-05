/**
 * @file
 * Service worker and push notification tracker.
 *
 *  - Registers the Service Worker. (See /social_pwa/js/sw.js)
 *  - Subscribes the user.
 *  - Saves the user subscription object.
 *
 * This file contains code that will not execute in IE 11 as IE 11 also does not
 * support push notifications or service workers.
 */

(function ($, Drupal, once) {

  'use strict';

  /**
   * Register the service worker and prompt users for push notification.
   *
   * This is what takes care of registering the service worker script in the
   * user's browser. This is also what checks if the user has already seen the
   * "Enable push notifications?" dialog and prompts them if needed.
   */
  Drupal.behaviors.serviceWorkerLoad = {
    attach: function (context, settings) {
      // If the current device doesn't support push notifications there's
      // nothing for us to do.
      if (!supportsPush()) {
        return;
      }

      // Check that the module provided us with settings to work.
      if (!settings.social_pwa) {
        return;
      }

      // Ensure this is only executed once.
      if (once('social_pwa-attach', 'body').length !== 1) {
        return;
      }

      const applicationServerKey = urlBase64ToUint8Array(settings.social_pwa.vapidPublicKey);
      const pushOptions = { userVisibleOnly: true, applicationServerKey: applicationServerKey };

      navigator.serviceWorker.register(settings.social_pwa.serviceWorkerUrl)
        .then(function (swRegistration) {
          // If we're not configured to prompt a user to enable push
          // notifications then we're done here.
          if (typeof settings.social_pwa.pushNotificationPromptTime === "undefined") {
            return;
          }

          // If the localstorage has been set then use that one.
          if (localStorage.getItem('prompt') !== null) {
            return;
          }

          swRegistration.pushManager
            .getSubscription()
            .then(hasStoredSubscription.bind(null, settings.social_pwa.pushNotificationSubscriptions))
            .then(
              requestSubscriptionIfUnsubscribed.bind(
                null,
                settings.social_pwa.subscriptionAddUrl,
                settings.social_pwa.pushNotificationPromptTime,
                pushOptions,
                swRegistration
              )
            );
        })
        .catch(function (error) {
          console.error('Service Worker error: ', error);
        });
    }
  }

  /**
   * Handles the current device push notification setting on the user form.
   *
   * This ensures that the toggle on the user setting page matches the current
   * status for the browser. It also prompts the user to accept push
   * notifications if they change the toggle.
   */
  Drupal.behaviors.pushNotificationCurrentDeviceForm = {
    attach: function (context, settings) {
      // Check that we have the settings object we need.
      if (!settings.social_pwa) {
        return;
      }

      let $currentDeviceForm = $('#edit-push-notifications-pwa-push', context);

      // If our context doesn't contain the push form then we're done.
      if (!$currentDeviceForm.length) {
        return;
      }

      // Create a callback for the current device scope, this makes it easy to
      // pass to the promise resolution of permissionState.
      let updateCurrentDeviceForm = updatePushFormStatus.bind(null, $currentDeviceForm);

      // If service workers aren't supported then we let the user know and stop
      // doing anything.
      if (!supportsPush()) {
        updateCurrentDeviceForm('unsupported');
        return;
      }

      const applicationServerKey = urlBase64ToUint8Array(settings.social_pwa.vapidPublicKey);
      const pushOptions = { userVisibleOnly: true, applicationServerKey: applicationServerKey };

      // Find the current state of the user's push notification status. Service
      // worker registration is performed elsewhere.
      getSwRegistration()
        .then(registration => {
          registration.pushManager.permissionState(pushOptions)
            .then(state =>
              // If the user has granted permission we check if the subscription is
              // registered.
              state === 'granted'
                ? registration.pushManager
                    .getSubscription()
                    .then(subscription =>
                      hasStoredSubscription(settings.social_pwa.pushNotificationSubscriptions, subscription)
                        ? updateCurrentDeviceForm('granted')
                        : updateCurrentDeviceForm('prompt')
                    )
                // Otherwise we display whatever the user has configured.
                : updateCurrentDeviceForm(state)
            )
        });


      const $toggleElement = findCurrentDeviceToggle($currentDeviceForm);

      $toggleElement.on('click', event => {
        event.preventDefault();

        $toggleElement.prop('disabled', true);
        if ($toggleElement[0].checked) {
          // Subscribe the user and update the toggle element to show whether it
          // was successful or not, then make the toggle functional again.
          getSwRegistration()
            .then(swRegistration => swRegistration.pushManager.subscribe(pushOptions))
            .then(subscription => {
              updateSubscriptionOnServer(settings.social_pwa.subscriptionAddUrl, subscription)
                .then(_ => updateCurrentDeviceForm('granted'))
                .catch(function (error) {
                  console.log('Failed to subscribe the user: ', error);
                  updateCurrentDeviceForm('error');
                })
            })
            .catch(_ => updateCurrentDeviceForm('denied'))
        }
        else {
          getSwRegistration()
            .then(swRegistration => swRegistration.pushManager.getSubscription())
            .then(subscription =>
              subscription !== null
                ? removeSubscriptionFromServer(settings.social_pwa.subscriptionRemoveUrl, arrayBufferToBase64(subscription.getKey('p256dh')))
                : null
            )
            .then(_ => updateCurrentDeviceForm('prompt'))
            .catch(_ => updateCurrentDeviceForm('granted'))
        }
      })

    }
  }

  // Everything needed to assemble the prompt for users to enable push
  // notifications.
  const promptTitle = Drupal.t('Would you like to enable <strong>push notifications</strong>?');
  const promptCta = Drupal.t('Choose enable to receive important updates straight away!');
  const promptHint = Drupal.t('These can always be disabled in <strong>Settings</strong>.');
  const promptDecline = Drupal.t('Not now');
  const promptAccept = Drupal.t('Enable');
  const promptAcceptId = "prompt-accept";
  const promptDeclineId = "prompt-defer";
  const $pushNotificationPrompt = $(`
    <div id="social_pwa--prompt">
      <h3 class="ui-dialog-message-title">${promptTitle}</h3>
      <p>${promptCta}</p>
      <small>${promptHint}</small>
      <div class="buttons">
        <button id="${promptDeclineId}" class="btn btn-default">
          ${promptDecline}
        </button>
        <button id="${promptAcceptId}" class="btn btn-primary">
          ${promptAccept}
        </button>
      </div>
    </div>
  `);

  /**
   * Promised based setTimeout implementation.
   */
  function after(s) {
    return new Promise((resolve, _) => setTimeout(resolve, s));
  }

  /**
   * Get the current Service Worker registration.
   *
   * @return {Promise<ServiceWorkerRegistration>}
   */
  function getSwRegistration() {
    return navigator.serviceWorker.ready;
  }

  /**
   * Prompt the user to enable push notifications.
   *
   * @return {Promise<boolean>}
   *   A promise that resolves to a boolean indicating whether the user has
   *   accepted the prompt.
   */
  function promptUserForPushNotifications() {
    return new Promise((resolve, _) => {
      // Add the prompt to the DOM so the Drupal.dialog can process it.
      $(document.body).append($pushNotificationPrompt);

      // Turn our prompt into a dialog.
      const pushNotificationsDialog = Drupal.dialog($pushNotificationPrompt, {
        dialogClass: 'ui-dialog_push-notification',
        modal: true,
        width: 'auto'
      });

      // Wire up the accept button.
      $pushNotificationPrompt.find('#' + promptAcceptId).on('click', (e) => {
        e.preventDefault();

        pushNotificationsDialog.close();
        $pushNotificationPrompt.remove();

        resolve(true);
      });

      // Wire up the decline button.
      $pushNotificationPrompt.find('#' + promptDeclineId).on('click', (e) => {
        e.preventDefault();

        pushNotificationsDialog.close();
        $pushNotificationPrompt.remove();

        resolve(false);
      });

      // Show the dialog.
      pushNotificationsDialog.showModal();
    });
  }

  /**
   * Whether the user's browser supports push notifications.
   *
   * @return {boolean}
   */
  function supportsPush() {
    return 'serviceWorker' in navigator && 'PushManager' in window;
  }

  /**
   * Request a subscription if the user is not already subscribed.
   *
   * @param {string} subscriptionAddUrl
   *   The URL to which new push subscriptions should be sent.
   * @param {int} promptAfter
   *   The number of seconds to wait before prompting the user to subscribe to
   *   push notifications.
   * @param {PushSubscriptionOptionsInit} pushOptions
   *   The options for the permission request.
   * @param {ServiceWorkerRegistration} swRegistration
   *   The active service worker registration.
   * @param {bool} isSubscribed
   *   Whether the user is subscribed.
   */
  function requestSubscriptionIfUnsubscribed(subscriptionAddUrl, promptAfter, pushOptions, swRegistration, isSubscribed) {
    // If the user is already subscribed there's nothing to do.
    if (isSubscribed) {
      return;
    }

    // If the user hasn't subscribed to push notifications then we check
    // whether they already made an active choice.
    swRegistration.pushManager.permissionState(pushOptions)
      .then(state => {
        // If the user hasn't made a choice yet then we ask them if our
        // module is configured to do so.
        if (state === "prompt") {
          // Prompt the user after the configured number of seconds.
          after(promptAfter * 1000)
            .then(promptUserForPushNotifications)
            .then(
              accepted =>
                accepted
                  ? swRegistration.pushManager.subscribe(pushOptions)
                      .then(updateSubscriptionOnServer.bind(null, subscriptionAddUrl))
                      // Map the jQuery success to a boolean.
                      .then(_ => true)
                      .catch(function (error) {
                        // Delete the overlay since the user has denied.
                        console.log('Failed to subscribe the user: ', error);

                        return false;
                      })
                  : Promise.resolve(false)
            )
            .then(storeUserPromptResult);
        }
      })
  }

  /**
   * Register that user saw the push notification prompt to the localstorage.
   */
  function storeUserPromptResult(_userChoice) {
    const timestamp = Math.floor(new Date().getTime() / 1000);
    localStorage.setItem('prompt', timestamp);
  }

  /**
   * Update the subscription to the database through a callback.
   *
   * @param subscriptionAddUrl
   *   The URL at which new subscriptions should be registered.
   * @param subscription
   *   The subscription to registered.
   *
   * @return {Promise<*>}
   *   A promise that contains the result of the POST request.
   */
  function updateSubscriptionOnServer(subscriptionAddUrl, subscription) {
    const subscriptionKey = subscription.getKey('p256dh') ? arrayBufferToBase64(subscription.getKey('p256dh')) : null;
    const token = subscription.getKey('auth');

    const subscriptionData = JSON.stringify({
      'endpoint': subscription.endpoint,
      'key': subscriptionKey,
      'token': token ? arrayBufferToBase64(token) : null
    });

    return new Promise(
      (resolve, reject) => $.ajax({
        url: subscriptionAddUrl,
        type: 'POST',
        data: subscriptionData,
        contentType: 'application/json;charset=utf-8',
        async: true,
        success: resolve,
        error: reject,
      })
    );
  }

  /**
   * Update the subscription to the database through a callback.
   *
   * TODO: The hardcoded URL is problematic.
   */
  function removeSubscriptionFromServer(subscriptionRemoveUrl, key) {
    const subscriptionData = JSON.stringify({
      'key': key
    });

    return new Promise(
      (resolve, reject) => $.ajax({
        url: subscriptionRemoveUrl,
        type: 'POST',
        data: subscriptionData,
        contentType: 'application/json;charset=utf-8',
        async: true,
        success: resolve,
        error: reject,
      })
    );
  }

  /**
   * Check whether the user has a notification subscription stored.
   *
   * @param {array} subscriptions
   *   The subscriptions stored on the server.
   * @param {PushSubscription|null} subscription
   *   The subscription as provided by the pushManager.
   *
   * @return {boolean}
   *   True if the key for the subscription was already stored in the
   *   database. False otherwise.
   */
  function hasStoredSubscription(subscriptions, subscription) {
    if (subscription === null) {
      return false;
    }

    let subscriptionKey = arrayBufferToBase64(subscription.getKey('p256dh'));
    return subscriptions.includes(subscriptionKey);
  }

  /**
   * Get the toggleElement for the current device push status.
   *
   * @param $currentDeviceForm
   *   The jQuery wrapped section of the form that contains the checkbox and
   *   feedback elements.
   */
  function findCurrentDeviceToggle($currentDeviceForm) {
    return $currentDeviceForm.find('input[type="checkbox"]').first();
  }

  /**
   * Update the state of the checkbox based on the permissionState.
   *
   * @param $currentDeviceForm
   *   The jQuery wrapped section of the form that contains the checkbox and
   *   feedback elements.
   * @param {string} state
   *   One of 'granted', 'denied', 'prompt' as per the
   *   PushManager.permissionState API or 'unsupported' in case the current
   *   browser does not support push notifications.
   */
  function updatePushFormStatus($currentDeviceForm, state) {
    // This form section should only have a single checkbox for the current
    // device.
    let $toggleElement = findCurrentDeviceToggle($currentDeviceForm);
    let $blockedNotice = $currentDeviceForm.find('.blocked-notice').first();

    switch (state) {
      case 'granted':
        $toggleElement.prop('checked', true);
        $toggleElement.prop('disabled', false);
        $blockedNotice.addClass('hide');
        break;

      case 'denied':
        $toggleElement.prop('checked', false);
        $toggleElement.prop('disabled', true);
        $blockedNotice.removeClass('hide');
        break;

      case 'prompt':
        $toggleElement.prop('checked', false);
        $toggleElement.prop('disabled', false);
        $blockedNotice.addClass('hide');
        break;

      case 'unsupported':
        $toggleElement.prop('checked', false);
        $toggleElement.prop('disabled', true);
        $blockedNotice.removeClass('hide');
        $blockedNotice.html(Drupal.t('Your browser does not support push notifications.'));
        break;

      case 'error':
        $toggleElement.prop('checked', false);
        $toggleElement.prop('disabled', false);
        $blockedNotice.removeClass('hide');
        $blockedNotice.html(Drupal.t("There was an error processing your push notification settings."));
        break;

      // The above states should be all of them, but who knows what
      // browsers do in the future.
      default:
        $toggleElement.prop('checked', false);
        console.warn(`Unexpected new push notification state ${state}`);
    }
  }

  /**
   * Convert a Base64 encoded string to an ArrayBuffer.
   *
   * @param base64String
   *   A Base64 encoded string.
   * @returns {Uint8Array}
   *   The converted ArrayBuffer.
   */
  function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
      .replace(/\-/g, '+')
      .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (var i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
  }

  /**
   * Creates a Base64 encoded string from an ArrayBuffer.
   *
   * @param buffer
   *   The ArrayBuffer.
   * @returns {string}
   *   A Base64 encoded string.
   */
  function arrayBufferToBase64(buffer) {
    var binary = '';
    var bytes = new Uint8Array(buffer);
    var len = bytes.byteLength;
    for (var i = 0; i < len; i++) {
      binary += String.fromCharCode(bytes[i]);
    }
    return window.btoa(binary);
  }

})(jQuery, Drupal, once);
