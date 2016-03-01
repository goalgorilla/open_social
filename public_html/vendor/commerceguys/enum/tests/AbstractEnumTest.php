<?php

namespace CommerceGuys\Enum\Tests;

/**
 * @coversDefaultClass \CommerceGuys\Enum\AbstractEnum
 */
class AbstractEnumTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::getAll
     */
    public function testGetAll()
    {
        $expectedValues = ['FIRST' => 'first', 'SECOND' => 'second'];
        $values = DummyEnum::getAll();
        $this->assertEquals($expectedValues, $values);
    }

    /**
     * @covers ::getKey
     * @uses \CommerceGuys\Enum\AbstractEnum::getAll
     */
    public function testGetKey()
    {
        $key = DummyEnum::getKey('first');
        $this->assertEquals('FIRST', $key);

        $key = DummyEnum::getKey('invalid');
        $this->assertEquals(false, $key);
    }

    /**
     * @covers ::exists
     * @uses \CommerceGuys\Enum\AbstractEnum::getAll
     */
    public function testExists()
    {
        $result = DummyEnum::exists('second');
        $this->assertEquals(true, $result);

        $result = DummyEnum::exists('invalid');
        $this->assertEquals(false, $result);
    }

    /**
     * @covers ::assertExists
     * @uses \CommerceGuys\Enum\AbstractEnum::getAll
     * @uses \CommerceGuys\Enum\AbstractEnum::exists
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage "invalid" is not a valid DummyEnum value.
     */
    public function testAssertExists()
    {
        $result = DummyEnum::assertExists('invalid');
    }

    /**
     * @covers ::assertAllExist
     * @uses \CommerceGuys\Enum\AbstractEnum::getAll
     * @uses \CommerceGuys\Enum\AbstractEnum::exists
     * @uses \CommerceGuys\Enum\AbstractEnum::assertExists
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage "invalid" is not a valid DummyEnum value.
     */
    public function testAssertAllExist()
    {
        $result = DummyEnum::assertAllExist(['second', 'invalid']);
    }

}
