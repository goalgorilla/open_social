/**
 * @file
 * Service worker implementation for the Open Social distribution.
 */

/**
 * Install the Service Worker.
 *
 * This event runs only once for the entire life of the active SW. It will run
 * again when the contents of this file change in any way.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerGlobalScope/install_event
 */
self.addEventListener(
  'install',
  _ => self.skipWaiting()
);

/**
 * Handle actions after the app is installed.
 *
 * When the user (re)installs the app, we need to remove the prompt as
 * the push subscription is not valid anymore and needs to pop-up again.
 */
self.addEventListener('appinstalled', function(e) {
  localStorage.removeItem('prompt');
});

/**
 * Called when the service worker is installed.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerGlobalScope/activate_event
 */
self.addEventListener(
  'activate',
  // Normally service workers are only switched when a page is reloaded, by
  // calling `self.clients.claim()` we force all open windows to use this
  // service worker immediately.
  event => event.waitUntil(self.clients.claim())
);

/**
 * Functions that handle push messages.
 *
 * An object where the key is the type of a push message as sent from the server
 * and the value is a function that receives the data attached to the push
 * message.
 *
 * Push handler functions should be async or return a promise manually.
 */
let pushHandlers = {};

/**
 * Functions that handle clicks on notifications.
 *
 * An object where the key is the type of click event as determined by the
 * pushHandler and the value is a function that receives the data attached with
 * the notification and the notification click event.
 *
 * Notification click handlers do not need to close the notification themselves.
 *
 * Notification handler functions should be async or return a promise manually.
 */
let notificationClickHandlers = {};

/**
 * Handle push data from the server.
 *
 * If the user has given permission to trigger push notifications then this will
 * delegate the push data to a registered pushHandler for the attached type.
 */
self.addEventListener('push', function (event) {
  if (!(self.Notification && self.Notification.permission === 'granted')) {
    return;
  }

  if (!event.data) {
    return;
  }

  try {
    // A proper push notification object has only `type` and `data` attributes.
    const { type, data } = event.data.json();
    if (typeof type !== "string") {
      console.warn("Received push notification without type string.", event.data.json());
      return;
    }

    // We allow a special type to be sent that does nothing. Service Workers
    // check for updates when they receive push notifications (max. once per 24
    // hours), so this provides a remote update method without requiring users
    // to visit the site.
    if (type === '_sw_update') {
      return;
    }

    if (typeof pushHandlers[type] !== "function") {
      console.warn("No function push handler for type", type);
      return;
    }

    event.waitUntil(pushHandlers[type](data));
  }
  // Don't crash the service worker on errors.
  catch (e) {
    console.error("There was an error processing the push notification with data.", event.data.text(), e);
  }
});

/**
 * Handle a user clicking on a notification.
 *
 * This will call the click handler for the type that was attached to the
 * notification.
 */
self.addEventListener('notificationclick', function (event) {
  // Close the notification when the user clicks it.
  event.notification.close();

  if (typeof event.notification.data === "undefined") {
    return;
  }

  const { type, data } = event.notification.data;

  if (typeof type !== "string") {
    return;
  }

  if (typeof notificationClickHandlers[type] !== "function") {
    console.warn("No function notification click handler for type", type);
    return;
  }

  event.waitUntil(notificationClickHandlers[type](data, event));
});

/**
 * Service worker fetch handler.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerGlobalScope/onfetch
 */
self.addEventListener('fetch', function (event) {
  // This event listener exists as a requirement for the
  // "Add to Homepage" function to work.
  // See: https://developers.google.com/web/fundamentals/app-install-banners
  // At this point it's been intentionally left blank
  // because caching the homepage does not sufficiently
  // differentiate between the AN and LU states.
});

/**
 * Legacy push handler implementation.
 *
 * Takes a set amount of data and opens a URL on click.
 */
pushHandlers.legacy = async function (pushData) {
  // We require at least a site name and a message to show.
  if (!pushData.site_name || !pushData.message) {
    return;
  }

  const url = typeof pushData.url !== "undefined" ? pushData.url : '/'

  // Retrieve a list of the clients of this service worker.
  const clientList = await self.clients.matchAll();

  // If a client has focus then we don't show the notification.
  if (clientList.some(client => client.focused)) {
    return;
  }

  return self.registration.showNotification(pushData.site_name, {
    body: pushData.message,
    icon: pushData.icon,
    data: { type: 'open_url', data: { url } }
  });
};

/**
 * Open url click handler.
 *
 * Opens a URL when a user clicks on the push notification with this handler.
 */
notificationClickHandlers.open_url = async function ({ url }) {
  const clientList = await self.clients.matchAll();

  const hadWindowToFocus = clientList.some((windowClient) =>
    windowClient.url === url
      ? (windowClient.focus(), true)
      : false,
  );
  // Otherwise, open a new tab to the applicable URL and focus it.
  if (!hadWindowToFocus)
    self.clients
    .openWindow(url)
    .then((windowClient) => (windowClient ? windowClient.focus() : null));
}
