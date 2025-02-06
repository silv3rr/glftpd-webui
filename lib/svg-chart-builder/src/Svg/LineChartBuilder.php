<?php

namespace Xanpena\SVGChartBuilder\Svg;

class LineChartBuilder extends BaseChartBuilder {

    protected int $width = 400;
    protected int $height = 500;
    protected array $axisColors = [];
    protected array $dataText = [];
    protected array $pointsColor = [];
    protected bool $bannerInfo = true;

    /**
     * Generate the SVG representation of the chart.
     *
     * @return string The SVG representation of the chart.
     */
    public function makeSvg()
    {
        $countData = count(array_values($this->data)[0]);
        $this->width = max($this->width, 100 + ($countData * 60));

        $this->openSvgTag()
            ->drawAxis()
            ->drawSeries()
            ->drawGraphData()
            ->drawLabels()
            ->drawCirclesWithText()
            ->closeSvgTag();

        return $this->svg;
    }

    /**
     * Generate the X and Y axes of the bar chart.
     *
     * @return $this
     */
    private function drawAxis()
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
        $maxValue = $this->getMaxValue();
        $availableWidth = $this->width - 100;
        $countData = (count(array_values($this->data)[0]));
        $divisor = ($countData > 1) ? ($countData - 1) : $countData;
        $widthRatio = $availableWidth / $divisor;

        $baseX = 50;
        $baseY = 250;

        $counter = 0;
        $pointAndText = '';

        foreach ($this->data as $key => $datas) {
            $x = $baseX;
            $previousY = null;

            if ($counter >= count($this->colors)) {
                $counter = 0;
            }

            foreach ($datas as $index => $data) {
                $isFirst = ($index === array_key_first($datas));

                $proportion = $data / $maxValue;
                $y = $baseY - $proportion * ($baseY - 50);

                if ($previousY !== null) {
                    $this->svg .= '<line x1="' . $previousX . '" y1="' . $previousY . '" x2="' . $x . '" y2="' . $y . '" stroke="'.$this->colors[$counter].'" stroke-width="3"/>';
                }

                $previousX = $x;
                $previousY = $y;

                $pointColor = ((array_key_exists($key, $this->pointsColor) && array_key_exists($index, $this->pointsColor[$key])) ? $this->pointsColor[$key][$index] : $this->colors[$counter]);

                $pointAndText .= '<circle cx="' . $x . '" cy="' . $y . '" r="4" fill="'.$pointColor.'" />';

                $text = ((array_key_exists($key, $this->dataText) && array_key_exists($index, $this->dataText[$key])) ? $this->dataText[$key][$index] : $data);

                $pointAndText .= '<text x="' . $x + (($isFirst) ? 8 : 0) . '" y="' . ($y - 10) . '"
                font-family="Arial"
                font-size="12"
                font-weight="bold"
                text-anchor="middle"
                fill="'.$this->colors[$counter].'">' . $text . '</text>';

                $x += $widthRatio;
            }

            $counter++;
        }

        $this->svg .= $pointAndText;

        return $this;
    }

    /**
     * Get Max Value
     *
     * @return int $maxValue
     */
    protected function getMaxValue() {
        $maxValue = 0;

        foreach ($this->data as $labels) {
            foreach ($labels as $data) {
                if ($maxValue === null || $data > $maxValue) {
                    $maxValue = $data;
                }
            }
        }

        return $maxValue;
    }

    /**
     * Generate the labels below the X axis of the chart.
     *
     * @return $this
     */
    protected function drawLabels()
    {
        $availableWidth = $this->width - 100;

        $baseX = 32;
        $baseY = 260;

        $rotation = -45;
        $verticalOffset = (10 * abs(sin(deg2rad($rotation)))) + 30;

        foreach ($this->labels as $index => $label) {
            $countLabels = count($this->labels);
            $divisor = ($countLabels > 1) ? ($countLabels - 1) : $countLabels;
            $proportion = $index / $divisor;
            $labelX = $baseX + $proportion * $availableWidth;

            $this->svg .= '<text transform="rotate('.$rotation.', '.$labelX.', '.$baseY.')" x="'.$labelX.'" y="'.($baseY + $verticalOffset).'" font-family="Arial" font-size="14" text-anchor="middle" fill="'. $this->labelsColor .'">'.$label.'</text>';
        }

        return $this;
    }


    protected function drawCirclesWithText()
    {
        if($this->bannerInfo) {
            $labels = array_keys($this->data);
            $availableWidth = $this->width - 100;

            $baseX = 50;
            $baseY = 250;

            foreach ($labels as $index => $label) {
                if (is_string($label)) {
                    $proportion = $index / (count($labels) - 1);
                    $x = $baseX + $proportion * $availableWidth;

                    $circleY = $baseY + 90;
                    $circleColor = $this->colors[$index];

                    $this->svg .= '<circle cx="' . $x . '" cy="' . $circleY . '" r="5" fill="' . $circleColor . '" />';
                    $this->svg .= '<text x="' . $x . '" y="' . ($circleY + 20) . '" font-family="Arial" font-size="12" text-anchor="middle" fill="' . $circleColor . '">' . $label . '</text>';
                }
            }
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
        $maxValue = $this->getMaxValue();
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
        $maxValue = $this->getMaxValue();
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
