<?php

namespace CommerceGuys\Zone\Model;

use CommerceGuys\Addressing\Model\AddressInterface;
use CommerceGuys\Addressing\Model\SubdivisionInterface;
use CommerceGuys\Zone\PostalCodeHelper;

/**
 * Matches a country, its subdivisions and postal codes.
 */
class ZoneMemberCountry extends ZoneMember
{
    /**
     * The country code.
     *
     * @var string
     */
    protected $countryCode;

    /**
     * The administrative area.
     *
     * @var string
     */
    protected $administrativeArea;

    /**
     * The locality.
     *
     * @var string
     */
    protected $locality;

    /**
     * The dependent locality.
     *
     * @var string
     */
    protected $dependentLocality;

    /**
     * The included postal codes.
     *
     * Can be a regular expression ("/(35|38)[0-9]{3}/") or a comma-separated
     * list of postal codes, including ranges ("98, 100:200, 250").
     *
     * @var string
     */
    protected $includedPostalCodes;

    /**
     * The excluded postal codes.
     *
     * Can be a regular expression ("/(35|38)[0-9]{3}/") or a comma-separated
     * list of postal codes, including ranges ("98, 100:200, 250").
     *
     * @var string
     */
    protected $excludedPostalCodes;

    /**
     * Gets the country code.
     *
     * @return string The country code.
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * Sets the country code.
     *
     * @param string $countryCode The country code.
     *
     * @return self
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    /**
     * Gets the administrative area.
     *
     * @return string|null The administrative area, or null if all should match.
     */
    public function getAdministrativeArea()
    {
        return $this->administrativeArea;
    }

    /**
     * Sets the administrative area.
     *
     * @param string|null $administrativeArea The administrative area.
     *
     * @return self
     */
    public function setAdministrativeArea($administrativeArea = null)
    {
        $this->administrativeArea = $administrativeArea;

        return $this;
    }

    /**
     * Gets the locality.
     *
     * @return string|null The locality, or null if all should match.
     */
    public function getLocality()
    {
        return $this->locality;
    }

    /**
     * Sets the locality.
     *
     * @param string|null $locality The locality.
     *
     * @return self
     */
    public function setLocality($locality = null)
    {
        $this->locality = $locality;

        return $this;
    }

    /**
     * Gets the dependent locality.
     *
     * @return string|null The dependent locality, or null if all should match.
     */
    public function getDependentLocality()
    {
        return $this->dependentLocality;
    }

    /**
     * Sets the dependent locality.
     *
     * @param string|null $dependentLocality The dependent locality.
     *
     * @return self
     */
    public function setDependentLocality($dependentLocality = null)
    {
        $this->dependentLocality = $dependentLocality;

        return $this;
    }

    /**
     * Gets the included postal codes.
     *
     * @return string The included postal codes.
     */
    public function getIncludedPostalCodes()
    {
        return $this->includedPostalCodes;
    }

    /**
     * Sets the included postal codes.
     *
     * @param string $includedPostalCodes The included postal codes.
     *
     * @return self
     */
    public function setIncludedPostalCodes($includedPostalCodes)
    {
        $this->includedPostalCodes = $includedPostalCodes;

        return $this;
    }

    /**
     * Gets the excluded postal codes.
     *
     * @return string The excluded postal codes.
     */
    public function getExcludedPostalCodes()
    {
        return $this->excludedPostalCodes;
    }

    /**
     * Sets the excluded postal codes.
     *
     * @param string $excludedPostalCodes The excluded postal codes.
     *
     * @return self
     */
    public function setExcludedPostalCodes($excludedPostalCodes)
    {
        $this->excludedPostalCodes = $excludedPostalCodes;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function match(AddressInterface $address)
    {
        if ($address->getCountryCode() != $this->countryCode) {
            return false;
        }
        if ($this->administrativeArea && $this->administrativeArea != $address->getAdministrativeArea()) {
            return false;
        }
        if ($this->locality && $this->locality != $address->getLocality()) {
            return false;
        }
        if ($this->dependentLocality && $this->dependentLocality != $address->getDependentLocality()) {
            return false;
        }
        if (!PostalCodeHelper::match($address->getPostalCode(), $this->includedPostalCodes, $this->excludedPostalCodes)) {
            return false;
        }

        return true;
    }
}
