This module provides the ability to convert address field values into a set of
coordinates that are stored with the entity. This enables the content to be 
found in search based on location.

## Google Maps API Key
This module uses the Google Maps API to transform address strings into 
lattitude/longtitude pairs. For all server side requests no Google Maps API
key is needed, however, rate limiting may apply. For the client side requests
which include the proximity filter and map blocks, a valid maps API key must
be entered on the geolocation's configuration page.

You can generate a key here:
https://console.cloud.google.com/google/maps-apis/api-list?project=social-local-171213&organizationId=841499249988

The project and key in the Behat tests is owned by Jaap Jan. It can be used on all domain(s).

The following services are activated:
- Geocoding API
- Geolocation API
- Maps Javascript API
- Places API for Web