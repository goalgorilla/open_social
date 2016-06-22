Address
=======
[![Build Status](https://travis-ci.org/bojanz/address.svg?branch=8.x-1.x)](https://travis-ci.org/bojanz/address)

Provides functionality for storing, validating and displaying international postal addresses.
The Drupal 8 heir to the addressfield module, powered by the [commerceguys/addressing](https://github.com/commerceguys/addressing) and [commerceguys/zone](https://github.com/commerceguys/zone) libraries.

Installation
-------------

1. Download [composer_manager](https://drupal.org/project/composer_manager) into your
   `modules` directory.

2. From the Drupal root directory, initialize composer_manager, and run it for the first time:

   ```sh
   php modules/composer_manager/scripts/init.php
   composer drupal-update
   ```
This will download the required libraries into the root vendor/ directory.

3. Enable the Address module.

Notes:
- * Find out more about composer_manager usage [here](https://www.drupal.org/node/2405811).
