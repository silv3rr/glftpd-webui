# SVGChartBuilder

SVGChartBuilder is a PHP library that allows you to generate SVG-based charts for use in your backend applications.

### Requirements

PHP 8.0 or later

## Installation

You can install SVGChartBuilder via Composer:

To install using Composer, run the following command in your terminal:

```bash
    composer require xanpena/svg-chart-builder
```

## Usage

SVGChartBuilder provides several types of charts that you can create:

### Example

To create a bar chart, use the following code:

- Tipo Bar chart
```php
use Xanpena\SVGChartBuilder\SVGChartBuilder;

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

$chartBuilder = new SVGChartBuilder(SVGChartBuilder::CHART_TYPE_BAR, $data, $options);
$svg = $chartBuilder->create();
echo $svg;
```

- Tipo Horizontal bar chart
```php
use Xanpena\SVGChartBuilder\SVGChartBuilder;

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

$chartBuilder = new SVGChartBuilder(SVGChartBuilder::CHART_TYPE_HORIZONTALBAR, $data, $options);
$svg = $chartBuilder->create();
echo $svg;
```

- Tipo Doughnut chart
```php
use Xanpena\SVGChartBuilder\SVGChartBuilder;

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
    'labelsColor' => 'white'
];

$chartBuilder = new SVGChartBuilder(SVGChartBuilder::CHART_TYPE_DOUGHNUT, $data, $options);
$svg = $chartBuilder->create();
echo $svg;
```

- Tipo Pie chart
```php
use Xanpena\SVGChartBuilder\SVGChartBuilder;

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
    'labelsColor' => 'white'
];

$chartBuilder = new SVGChartBuilder(SVGChartBuilder::CHART_TYPE_PIE, $data, $options);
$svg = $chartBuilder->create();
echo $svg;
```

- Tipo Line chart
```php
use Xanpena\SVGChartBuilder\SVGChartBuilder;

$data = [
    'math' => [
        11,
        17,
        15,
        // ... other data ...
    ],
    'literature' => [
        21,
        21,
        23,
        // ... other data ...
    ],
    'english' => [
        14,
        9,
        18,
        // ... other data ...
    ]
    // ... other data ...
];

$options = [
    'labels' => [
        '2020/2021',
        '2021/2022',
        '2023/2024',
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
];

$chartBuilder = new SVGChartBuilder(SVGChartBuilder::CHART_TYPE_LINE, $data, $options);
$svg = $chartBuilder->create();
echo $svg;
```

### Chart Types
SVGChartBuilder supports the following chart types:

SVGChartBuilder::BAR_CHART: Bar chart<br>
SVGChartBuilder::HORIZONTALBAR_CHART: Horizontal bar chart<br>
SVGChartBuilder::DOUGHNUT_CHART: Doughnut chart<br>
SVGChartBuilder::PIE_CHART: Pie chart<br>
SVGChartBuilder::CHART_TYPE_LINE: Line chart<br>


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please using the issue tracker.

## Credits

- [Xan Pena](https://github.com/xanpena)
- [Francisco Prieto](https://github.com/fjpj2310)
