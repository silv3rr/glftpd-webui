<?php

declare(strict_types=1);

namespace Xanpena\SVGChartBuilder;

use Xanpena\SVGChartBuilder\Svg\{BarChartBuilder,
    DoughnutChartBuilder,
    HorizontalBarChartBuilder,
    LineChartBuilder,
    PieChartBuilder};

class SVGChartBuilder
{
    const OPTION_WIDTH = 'width';
    const OPTION_HEIGHT = 'height';
    const OPTION_LABELS = 'labels';
    const OPTION_COLORS = 'colors';
    const OPTION_AXIS_COLORS = 'axisColors';
    const OPTION_LABELS_COLOR = 'labelsColor';
    const OPTION_DATA_COLOR = 'dataColor';
    const OPTION_BANNER_INFO = 'bannerInfo';
    const OPTION_DATA_TEXT = 'dataText';
    const OPTION_POINTS_COLOR = 'pointsColor';

    const OPTION_TYPES = [
        self::OPTION_WIDTH,
        self::OPTION_HEIGHT,
        self::OPTION_LABELS,
        self::OPTION_COLORS,
        self::OPTION_AXIS_COLORS,
        self::OPTION_LABELS_COLOR,
        self::OPTION_DATA_COLOR,
        self::OPTION_BANNER_INFO,
        self::OPTION_DATA_TEXT,
        self::OPTION_POINTS_COLOR,
    ];

    const CHART_TYPE_BAR = 'bar';
    const CHART_TYPE_DOUGHNUT = 'doughnut';
    const CHART_TYPE_HORIZONTALBAR = 'horizontal-bar';
    const CHART_TYPE_PIE = 'pie';
    const CHART_TYPE_LINE = 'line';

    protected $validChartTypes = [
        self::CHART_TYPE_BAR,
        self::CHART_TYPE_DOUGHNUT,
        self::CHART_TYPE_HORIZONTALBAR,
        self::CHART_TYPE_PIE,
        self::CHART_TYPE_LINE,
    ];

    protected $type;
    protected $data;
    protected $options;

    /**
     * Constructor.
     *
     * @param string $type Type of chart to create.
     * @param array $data Data for the chart.
     * @throws \InvalidArgumentException If the type or data is invalid.
     */
    public function __construct($type, $data, $options = [])
    {
        $this->validateType($type);
        $this->validateData($type, $data);
        $this->validateOptions($type, $data, $options);

        $this->type = $type;
        $this->data = $data;
        $this->options = $options;
    }

    /**
     * Create and return the SVG chart.
     *
     * @return string SVG representation of the chart.
     */
    public function create()
    {
        $chart = '';

        switch ($this->type) {
            case self::CHART_TYPE_BAR:
                $chart = (new BarChartBuilder($this->data, $this->options))->makeSvg();
                break;
            case self::CHART_TYPE_DOUGHNUT:
                $chart = (new DoughnutChartBuilder($this->data, $this->options))->makeSvg();
                break;
            case self::CHART_TYPE_HORIZONTALBAR:
                $chart = (new HorizontalBarChartBuilder($this->data, $this->options))->makeSvg();
                break;
            case self::CHART_TYPE_PIE:
                $chart = (new PieChartBuilder($this->data, $this->options))->makeSvg();
                break;
            case self::CHART_TYPE_LINE:
                $chart = (new LineChartBuilder($this->data, $this->options))->makeSvg();
                break;
        }

        return $chart;
    }

    /**
     * Validate the chart type.
     *
     * @param string $type Type of chart to validate.
     * @throws \InvalidArgumentException If the type is not valid.
     */
    protected function validateType($type)
    {
        if (!in_array($type, $this->validChartTypes)) {
            throw new \InvalidArgumentException("Invalid chart type: $type");
        }
    }

    /**
     * Validate the chart data.
     *
     * @param string $type Type of chart to validate.
     * @param array $data Data for the chart to validate.
     * @throws \InvalidArgumentException If the data is not valid.
     */
    protected function validateData($type, $data)
    {
        if (!is_array($data) || empty($data)) {
            throw new \InvalidArgumentException("Data must be a non-empty array");
        }

        switch ($type) {
            case self::CHART_TYPE_LINE:
                $this->validateDataLineGraph($data);
                break;
            default:
                $this->validateDataGenericGraph($data);
                break;
        }
    }


    protected function validateDataGenericGraph($data)
    {
        foreach ($data as $value) {
            if (!is_numeric($value)) {
                throw new \InvalidArgumentException("Each element of data must have a numeric value");
            }
        }
    }


    protected function validateDataLineGraph($data)
    {
        $firstNumElements = null;

        foreach ($data as $values) {
            if (!is_array($values)) {
                throw new \InvalidArgumentException("Each element of data must have an array of numerical values.");
            }

            $secondNumElements = count($values);
            if ($firstNumElements === null) {
                $firstNumElements = $secondNumElements;
            } else if ($firstNumElements !== $secondNumElements) {
                throw new \InvalidArgumentException("Each element of data must have the same number of elements.");
            }

            foreach ($values as $value) {
                if (!is_numeric($value)) {
                    throw new \InvalidArgumentException("Each element of data must have an array of numerical values.");
                }
            }
        }
    }

    protected function validateOptions($type, $data, $options)
    {
        if (empty($options) === false) {
            if (!is_array($options)) {
                throw new \InvalidArgumentException("Options must be a array");
            }

            foreach ($options as $option => $values) {
                switch ($option) {
                    case self::OPTION_WIDTH:
                    case self::OPTION_HEIGHT:
                        if (!is_numeric($values)) {
                            throw new \InvalidArgumentException("The $option option must be a numerical");
                        }
                        break;
                    case self::OPTION_LABELS:
                        switch ($type) {
                            case self::CHART_TYPE_LINE:
                                if (count(array_values($data)[0]) !== count($values)) {
                                    throw new \InvalidArgumentException("The $option option must have the same number of data elements");
                                }
                                break;
                            default:
                                if (count($data) !== count($values)) {
                                    throw new \InvalidArgumentException("The $option option must have the same number of data elements");
                                }
                                break;
                        }
                        break;
                    case self::OPTION_COLORS:
                        switch ($type) {
                            case self::CHART_TYPE_LINE:
                                if (count(array_keys($data)) !== count($values)) {
                                    throw new \InvalidArgumentException("The $option option must have the same number of data elements");
                                }
                                break;
                            default:
                                if (count($data) !== count($values)) {
                                    throw new \InvalidArgumentException("The $option option must have the same number of data elements");
                                }
                                break;
                        }
                        break;
                    case self::OPTION_AXIS_COLORS:
                        if (array_keys($values) !== ["x", "y"]) {
                            throw new \InvalidArgumentException("The $option must be an array with the keys x and y and their respective colors");
                        }
                        break;
                    case self::OPTION_BANNER_INFO:
                        if (!is_bool($values)) {
                            throw new \InvalidArgumentException("The $option option must be a boolean");
                        }
                        break;
                    case self::OPTION_POINTS_COLOR:
                    case self::OPTION_DATA_TEXT:
                        switch ($type) {
                            case self::CHART_TYPE_LINE:
                                if (!is_array($values)) {
                                    throw new \InvalidArgumentException("Each element of $option must have an array of values.");
                                } else if (count($data) !== count($values)) {
                                    throw new \InvalidArgumentException("Each element of $option must have the same number of elements than data");
                                }

                                foreach ($data as $key => $value) {
                                    if (array_key_exists($key, $values) === false) {
                                        throw new \InvalidArgumentException("Each element of $option must have the same keys than data");
                                    } else if (count($value) !== count($values[$key])) {
                                        throw new \InvalidArgumentException("Each element of $option must have the same number of elements than data");
                                    }
                                }
                                break;
                            default:
                                break;
                        }
                        break;
                    default:
                        if (!is_string($values) && !is_numeric($values)) {
                            throw new \InvalidArgumentException("The $option option must be a string or a numeric");
                        }
                        break;
                }
            }
        }
    }

}
