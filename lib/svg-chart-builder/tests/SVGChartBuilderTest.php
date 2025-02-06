<?php

namespace Xanpena\SVGChartBuilder\Tests\SVGChartBuilder;

use PHPUnit\Framework\TestCase;
use Xanpena\SVGChartBuilder\SVGChartBuilder;

class SVGChartBuilderTest extends TestCase
{
    public function testCreateBarChart()
    {

        $data = [
            'matematicas' => 16,
            'literatura'  => 18,
            'inglés'      => 40,
        ];
        $chart = (new SVGChartBuilder('bar', $data))->create();

        $this->assertNotEmpty($chart);

        $this->assertStringContainsString('<svg', $chart);
        $this->assertStringContainsString('<line', $chart);
        $this->assertStringContainsString('<rect', $chart);
        $this->assertStringContainsString('<text', $chart);


    }

}
