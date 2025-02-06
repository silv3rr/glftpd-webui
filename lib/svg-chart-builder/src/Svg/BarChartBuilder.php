<?php

namespace Xanpena\SVGChartBuilder\Svg;

class BarChartBuilder extends BaseChartBuilder {

    protected int $width = 400;
    protected int $height = 300;
    protected array $axisColors = [];
    protected string $dataColor = '#000000';

    /**
     * Generate the SVG representation of the chart.
     *
     * @return string The SVG representation of the chart.
     */
    public function makeSvg()
    {
        $this->width = max($this->width, 100 + (count($this->data) * 30));

        $this->openSvgTag()
            ->drawSeries()
            ->drawGraphData()
            ->drawLabels()
            ->drawAxis()
            ->closeSvgTag();

        return $this->svg;
    }

    /**
     * Generate the X and Y axes of the bar chart.
     *
     * @return $this
     */
    protected function drawAxis()
    {
        $this->svg .= '<line x1="50" y1="250" x2="' . ($this->width + 20) . '" y2="250" stroke="'. $this->getAxisColor('x') .'" />';
        $this->svg .= '<line x1="50" y1="250" x2="50" y2="50" stroke="'. $this->getAxisColor('y') .'" />';

        return $this;
    }

    /**
     * Generate the chart on SVG canvas.
     *
     * @return $this
     */
    protected function drawGraphData()
    {
        $numData = count($this->data);
        $availableWidth = $this->width - 100;
        $widthRatio = $availableWidth / $numData;
        $spaceRatio = max(5, min(10, 40 / $numData));

        $baseX = 50;
        $baseY = 250;

        $maxValue = max($this->data);
        $maxValue = ($maxValue != 0) ? $maxValue : 1;

        $counter = 0;
        $x = $baseX;

        foreach ($this->data as $data) {
            if ($counter >= count($this->colors)) {
                $counter = 0;
            }

            $proportion = $data / $maxValue;
            $barHeight = $proportion * ($baseY - 50);
            $y = $baseY - $barHeight;

            $this->svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $widthRatio . '" height="' . $barHeight . '" fill="' . $this->colors[$counter] . '"/>';

            $valueX = $x + $widthRatio / 2;
            $valueY = $y + $barHeight / 2;

            $this->svg .= '<text x="' . $valueX . '" y="' . $valueY . '" font-family="Arial" font-size="14" fill="' . $this->dataColor . '" text-anchor="middle" dominant-baseline="middle">' . $data . '</text>';

            $x += $widthRatio + $spaceRatio;
            $counter++;
        }

        return $this;
    }

    /**
     * Generate the labels below the X axis of the chart.
     *
     * @return $this
     */
    protected function drawLabels()
    {
        $numData = count($this->data);
        $availableWidth = $this->width - 100;
        $widthRatio = $availableWidth / $numData;
        $spaceRatio = max(5, min(10, 40 / $numData));

        $baseX = 50;
        $baseY = 250;

        $x = $baseX + $widthRatio / 2;
        $y = $baseY + 30;

        $rotation = -45;
        $verticalOffset = 10 * abs(sin(deg2rad($rotation)));
        $horizontalOffset = -($widthRatio / 4);

        foreach ($this->labels as $key => $label) {
            if ($key >= count($this->colors)) {
                $key = 0;
            }

            $this->svg .= '<text transform="rotate('.$rotation.', '.$x.', '.$y.')" x="'.($x + $horizontalOffset).'" y="'.($y + $verticalOffset).'" font-family="Arial" font-size="14" fill="'.$this->colors[$key].'" text-anchor="middle">'.$label.'</text>';

            $x += $widthRatio + $spaceRatio;
        }

        return $this;
    }

    /**
     * Generate the Y axis labels.
     *
     * @return $this
     */
    protected function drawSeries()
    {
        $numTicks = $this->calculateNumTicks();
        $maxValue = max($this->data);
        $maxValue = (($maxValue != 0) ? $maxValue : 1);
        $interval = $maxValue / ($numTicks - 1);

        $baseX = 40;
        $baseY = 250;

        $x = $baseX - 10;

        for ($i = 0; $i < $numTicks; $i++) {
            $tickValue = $i * $interval;

            if ($tickValue != (int) $tickValue) {
                $tickValue = round($tickValue, 2);
            }

            $barHeight = ($tickValue / $maxValue) * ($baseY - 50);
            $y = $baseY - $barHeight;

            $this->svg .= '<line x1="'.$baseX.'" y1="'.$y.'" x2="'.($baseX + 10.5).'" y2="'.$y.'" stroke="'. $this->getAxisColor('y') .'" stroke-width="0.5" />';

            $labelY = $y + 5;
            $this->svg .= '<text x="'.$x.'" y="'.$labelY.'" font-family="Arial" font-size="12" fill="'. $this->labelsColor .'" text-anchor="end">'.$tickValue.'</text>';
        }

        return $this;
    }

    protected function calculateNumTicks()
    {
        $maxValue = max($this->data);
        $minValue = 0;
        $range = $maxValue - $minValue;
        $minInterval = 1;

        $numTicks = max(2, ceil($range / $minInterval) + 1);
        $numTicks = min($numTicks, 11);

        return $numTicks;
    }

    protected function getAxisColor($axis)
    {
        if (empty($this->axisColors) === false && array_key_exists($axis, $this->axisColors)) {
            return $this->axisColors[$axis];
        }

        return '#000000';
    }

}
