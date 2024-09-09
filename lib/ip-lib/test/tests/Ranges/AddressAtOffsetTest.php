<?php

namespace IPLib\Test\Ranges;

use IPLib\Factory;
use IPLib\Test\TestCase;

class AddressAtOffsetTest extends TestCase
{
    public function ipProvider()
    {
        return array(
            array('127.0.0.0/16', 256, '127.0.1.0'),
            array('127.0.0.0/16', -1, '127.0.255.255'),
            array('127.0.0.0/24', 256, null),
            array('127.0.0.0/16', 0, '127.0.0.0'),
            array('::ff00/120', 0, '::ff00'),
            array('::ff00/120', 16, '::ff10'),
            array('::ff00/120', 100, '::ff64'),
            array('::ff00/120', 256, null),
            array('::ff00/120', -1, '::ffff'),
            array('::ff00/120', -16, '::fff0'),
            array('::ff00/120', -256, '::ff00'),
            array('::ff00/120', -257, null),
            array('255.255.255.255/32', 1, null),
            array('::ff00/120', '0', null),
            array('::ff00/120', 'invalid', null),
        );
    }

    /**
     * @dataProvider ipProvider
     *
     * @param string $rangeString
     * @param int|mixed $n
     * @param string|null $expected
     */
    public function testAddressAtOffset($rangeString, $n, $expected)
    {
        $range = Factory::rangeFromString($rangeString);

        $result = $range->getAddressAtOffset($n);
        if ($result !== null) {
            $result = $result->toString();
        }

        $expectedString = $expected;
        if ($expected === null) {
            $expectedString = 'NULL';
        }

        $this->assertSame($expected, $result, "'{$rangeString}' with offset {$n} must be '{$expectedString}'");
    }
}
