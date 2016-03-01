<?php

namespace CommerceGuys\Zone\Tests;

use CommerceGuys\Zone\PostalCodeHelper;

/**
 * @coversDefaultClass \CommerceGuys\Zone\PostalCodeHelper
 */
class PostalCodeHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::match
     * @covers ::matchRule
     * @covers ::buildList
     */
    public function testMatch()
    {
        // Test empty rules.
        $this->assertEquals(true, PostalCodeHelper::match('123', null, null));

        // Test regular expressions.
        $includeRule = '/(20)[0-9]{1}/';
        $excludeRule = '/(20)[0-2]{1}/';
        $this->assertEquals(true, PostalCodeHelper::match('203', $includeRule, $excludeRule));
        $this->assertEquals(false, PostalCodeHelper::match('202', $includeRule, $excludeRule));

        // Test lists
        $includeRule = '10, 20, 30:40';
        $excludeRule = '35';
        $this->assertEquals(true, PostalCodeHelper::match('34', $includeRule, $excludeRule));
        $this->assertEquals(false, PostalCodeHelper::match('35', $includeRule, $excludeRule));
    }

    /**
     * Returns a mock address with the provided postal code.
     *
     * @param string $postalCode The postal code.
     *
     * @return \CommerceGuys\Addressing\Model\Address
     */
    protected function getAddress($postalCode = null)
    {
        $address = $this
            ->getMockBuilder('CommerceGuys\Addressing\Model\Address')
            ->getMock();
        if ($postalCode) {
            $address
                ->expects($this->any())
                ->method('getPostalCode')
                ->will($this->returnValue($postalCode));
        }

        return $address;
    }
}
