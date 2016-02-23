enum
=====

[![Build Status](https://travis-ci.org/commerceguys/enum.svg?branch=master)](https://travis-ci.org/commerceguys/enum)

A PHP 5.4+ enumeration library.

Class constants are frequently used to denote sets of allowed values.
By grouping them in an enumeration class, we gain the ability to add helper methods,
list all possible values and validate values against them.

A [commerceguys/addressing](https://github.com/commerceguys/addressing) example:
```php
namespace CommerceGuys\Addressing\Enum;

use CommerceGuys\Enum\AbstractEnum;

/**
 * Enumerates available locality types.
 */
final class LocalityType extends AbstractEnum
{
    const CITY = 'city';
    const DISTRICT = 'district';

    // We can provide a getDefault() method here, or anything else.
}

LocalityType::getAll(); // ['CITY' => 'city', 'DISTRICT' => 'district']
LocalityType::getKey('city'); // 'CITY'
LocalityType::exists('city'); // true
LocalityType::assertExists('invalid value'); // InvalidArgumentException
LocalityType::assertAllExist(['district', 'invalid value']); // InvalidArgumentException
```

Meanwhile, on the AddressFormat:
```php
// The AddressFormatInterface is now free of LOCALITY_TYPE_ constants.
class AdressFormat implements AddressFormatInterface
{
    public function setLocalityType($localityType)
    {
        LocalityType::assertExists($localityType);
        $this->localityType = $localityType;
    }
}
```

The reason why this library was made instead of reusing [myclabs/php-enum](https://github.com/myclabs/php-enum)
was that we didn't want to allow enumerations to be instantiated.
