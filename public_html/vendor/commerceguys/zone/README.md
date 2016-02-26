zone
=====

[![Build Status](https://travis-ci.org/commerceguys/zone.svg?branch=master)](https://travis-ci.org/commerceguys/zone)

A PHP 5.4+ zone management library. Requires [commerceguys/addressing](https://github.com/commerceguys/addressing).

Zones are territorial groupings mostly used for shipping or tax purposes.
For example, a set of shipping rates associated with a zone where the rates
become available only if the customer's address matches the zone.

A zone can match other zones, countries, subdivisions (states/provinces/municipalities), postal codes.
Postal codes can also be expressed using ranges or regular expressions.

Examples of zones:
- California and Nevada
- Belgium, Netherlands, Luxemburg
- European Union
- Germany and a set of Austrian postal codes (6691, 6991, 6992, 6993)
- Austria without specific postal codes (6691, 6991, 6992, 6993)

# Data model

Each [zone](https://github.com/commerceguys/zone/blob/master/src/Model/ZoneInterface.php) has [zone members](https://github.com/commerceguys/zone/blob/master/src/Model/ZoneMemberInterface.php).
A zone matches the provided address if one of its zone members matches the provided address.

The base interfaces don't impose setters, since they aren't needed by the service classes.
Extended interfaces ([ZoneEntityInterface](https://github.com/commerceguys/zone/blob/master/src/Model/ZoneEntityInterface.php), [ZoneMemberEntityInterface](https://github.com/commerceguys/zone/blob/master/src/Model/ZoneMemberEntityInterface.php)) are provided for that purpose,
as well as matching [Zone](https://github.com/commerceguys/zone/blob/master/src/Model/Zone.php) and [ZoneMember](https://github.com/commerceguys/zone/blob/master/src/Model/ZoneMember.php) classes that can be used as examples or mapped by Doctrine.

The library contains two types of zone members:
- [country](https://github.com/commerceguys/zone/blob/master/src/Model/ZoneMemberCountry.php) (matches a country, its subdivisions, included/excluded postal codes)
- [zone](https://github.com/commerceguys/zone/blob/master/src/Model/ZoneMemberZone.php) (matches a zone)

```php
use CommerceGuys\Addressing\Model\Address;
use CommerceGuys\Zone\Model\Zone;
use CommerceGuys\Zone\Model\ZoneMemberCountry;

$zone = new Zone();
$zone->setId('german_vat');
$zone->setName('German VAT');
$zone->setScope('tax');

// Create the German VAT zone (Germany and 4 Austrian postal codes).
$germanyZoneMember = new ZoneMemberCountry();
$germanyZoneMember->setCountryCode('DE');
$zone->addMember($germanyZoneMember);

$austriaZoneMember = new ZoneMemberCountry();
$austriaZoneMember->setCountryCode('AT');
$austriaZoneMember->setIncludedPostalCodes('6691, 6991:6993');
$zone->addMember($austriaZoneMember);

// Check if the provided austrian address matches the German VAT zone.
$austrianAddress = new Address();
$austrianAddress = $austrianAddress
    ->withCountryCode('AT')
    ->withPostalCode('6692');
echo $zone->match($austrianAddress); // true
```

# Matcher

A matcher class is provided for the use case where an address should be matched
against all zones in the system, with the matched zones ordered by priority.

```php
use CommerceGuys\Addressing\Model\Address;
use CommerceGuys\Zone\Matcher\ZoneMatcher;
use CommerceGuys\Zone\Repository\ZoneRepository;

// Initialize the default repository which loads zones from json files stored in
// resources/zone. A different repository might load them from the database, etc.
$repository = new ZoneRepository('resources/zone');
$matcher = new ZoneMatcher($repository);

$austrianAddress = new Address();
$austrianAddress = $austrianAddress
    ->withCountryCode('AT')
    ->withPostalCode('6692');

// Get all matching zones.
$zones = $matcher->matchAll($austrianAddress);
// Get all matching zones for the 'tax' scope.
$zones = $matcher->matchAll($austrianAddress, 'tax');

// Get the best matching zone.
$zone = $matcher->match($austrianAddress);
// Get the best matching zone for the 'shipping' scope.
$zone = $matcher->match($austrianAddress, 'shipping');
```
