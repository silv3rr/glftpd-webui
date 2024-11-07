<?php

namespace IPLib\Test\Ranges;

use IPLib\Factory;
use IPLib\Test\TestCase;

class RangeSizeTest extends TestCase
{
    public function rangesProvider()
    {
        return array(
            array('8.8.8.8', 1),
            array('2001:0db8:85a3:0000:0000:8a2e:0370:7334', 1),

            array('8.8.8.8/31', 2),
            array('0.0.0.0/0', 4294967296),
            array('100::/64', 1.8446744073709552E+19),
            array('::/0', 3.402823669209385E+38),

            array('172.16.0.*', 256),
            array('172.*.*.*', 16777216),
            array('*.*.*.*', 4294967296),
            array('2001:0db8:85a3:0000:0000:8a2e:0370:*', 65536),
            array('2001:0db8:85a3:0000:0000:*:*:*', 281474976710656),
            array('*:*:*:*:*:*:*:*', 3.402823669209385E+38),
        );
    }

    /**
     * @dataProvider rangesProvider
     *
     * @param string $addressRange
     * @param int|float $size
     */
    public function testSize($addressRange, $size)
    {
        $range = Factory::rangeFromString($addressRange);
        $actualSize = $range->getSize();
        $this->assertSame($size, $actualSize);
    }
}
