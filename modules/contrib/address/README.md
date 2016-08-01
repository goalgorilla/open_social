Address
=======
[![Build Status](https://travis-ci.org/bojanz/address.svg?branch=8.x-1.x)](https://travis-ci.org/bojanz/address)

Provides functionality for storing, validating and displaying international postal addresses.
The Drupal 8 heir to the addressfield module, powered by the [commerceguys/addressing](https://github.com/commerceguys/addressing) and [commerceguys/zone](https://github.com/commerceguys/zone) libraries.

Installation
-------------
This module needs to be installed via Composer, which will download the required libraries.

1. Add the Drupal Packagist repository

    ```sh
    composer config repositories.drupal composer https://packagist.drupal-composer.org
    ```
This allows Composer to find Address and the other Drupal modules.

2. Download Address

   ```sh
   composer require "drupal/address ~8.1"
   ```
This will download the latest release of Address.
Use 8.1.x-dev instead of ~8.1 to get the -dev release instead.

See https://www.drupal.org/node/2404989 for more information.
