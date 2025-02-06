<?php

namespace Xanpena\SVGChartBuilder\Tests\SVGChartBuilder;

use PHPUnit\Framework\TestCase;
use Xanpena\SVGChartBuilder\SVGChartBuilder;

class BarChartBuilderTest extends TestCase
{
    public function testCreateBarChart()
    {
        $chartBuilder =
        $data = [
            'matematicas' => 16,
            'literatura'  => 18,
            'inglés'      => 40,
            // ... más datos de prueba ...
        ];

        $chart = (new SVGChartBuilder('bar', $data))->create();

        $this->assertNotEmpty($chart);
    }
}
