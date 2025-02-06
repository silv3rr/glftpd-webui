<?php

/*--------------------------------------------------------------------------*
 *   SHIT:FRAMEWORK graphs
 *--------------------------------------------------------------------------*/

//namespace Xanpena\SVGChartBuilder;

use Xanpena\SVGChartBuilder\SVGChartBuilder;

require_once 'lib/svg-chart-builder/src/SVGChartBuilder.php';
require_once 'lib/svg-chart-builder/src/Svg/BaseChartBuilder.php';
require_once 'lib/svg-chart-builder/src/Svg/BarChartBuilder.php';
require_once 'lib/svg-chart-builder/src/Svg/PieChartBuilder.php';
require_once 'lib/svg-chart-builder/src/Svg/DoughnutChartBuilder.php';

// EXXAMPLE
/* 
$data = [
    16,
    18,
    40,
    // ... other data ...
];

$options = [
    'labels' => [
        'math',
        'literature',
        'english',
        // ... other data ...
    ],
    'colors' => [
        '#CDDC39',
        '#00BCD4',
        '#9E9E9E',
        // ... other data ...
    ],
    'axisColors' => [
        'x' => 'red',
        'y' => 'blue'
    ],
    'labelsColor' => 'orange',
    'dataColor' => 'white',
];
*/  

function create_svg ($type, $data = [], $labels = [], $palette) {
    $check_data=0;
    for ($i = 1; $i < count($data); $i++) {
      $check_data += $data[$i];
    };
    if ($check_data > 0)  {
        if (empty($palette)) {
            $palette = [
                '#CDDC39',
                '#00BCD4',
                '#9E9E9E', 
            ];
        }
        $n = 0;
        for ($i = 0; $i < count($data); $i++) {
            if ($i % count($palette) == 0) {
                $n = 0;
            }
            $colors[$i] = $palette[$n];
            $n++;
        };
        if (!empty($type) && !empty($data) && !empty($labels) && !empty($colors)) {
            $options = [
                'labels' => $labels,
                'labelsColor' => '#333',
                'dataColor' => '#111',
                'height' => 280,
                'width' => 280,
                'colors' => $colors,
            ];
            $chartBuilder = new SVGChartBuilder($type, $data, $options);
            return $chartBuilder->create();
        }
    } else {
        return('
            <svg viewBox="0 0 100 100" height="300" width="300" xmlns="http://www.w3.org/2000/svg">
                <text x="35" y="40" fill="gray" font-size="0.30em">' . $labels[0] . ' (' . $data[0] . ')' . '</text>
                <text x="36" y="55" fill="gray" font-size="0.30em">&lt;no data&gt;</text>
                <circle r="45" cx="50" cy="50" fill="' . $palette[0] . '" stroke="gray" stroke-width="2" opacity="0.2"/>
            </svg>
        ');
    }
    return false;
}
