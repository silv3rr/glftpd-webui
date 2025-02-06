<?php

namespace Xanpena\SVGChartBuilder\Svg;

class HorizontalBarChartBuilder extends BaseChartBuilder {

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
        $this->height = max($this->height, 100 + (count($this->data) * 30));

        $this->openSvgTag()
            ->drawGraphData()
            ->drawSeries()
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
    private function drawAxis()
    {
        $this->svg .= '<line x1="100" y1="' . ($this->height - 20) . '" x2="100" y2="20" stroke="'. $this->getAxisColor('y') .'"></line>';
        $this->svg .= '<line x1="100" y1="' . ($this->height - 20) . '" x2="' . ($this->width + 20) . '" y2="' . ($this->height - 20) . '" stroke="'. $this->getAxisColor('x') .'"></line>';

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
        $availableVerticalSpace = $this->height - 40;

        $totalSpace = $availableVerticalSpace - ($numData * 10);
        $heightRatio = $totalSpace / $numData;

        $baseX = 100;
        $baseY = $this->height - 20;

        $maxValue = max($this->data);
        $maxValue = (($maxValue != 0) ? $maxValue : 1);

        $counter = 0;
        $y = $baseY - $heightRatio;

        foreach ($this->data as $key => $data) {
            if ($counter >= count($this->colors)) {
                $counter = 0;
            }

            $proportion = $data / $maxValue;
            $barHeight = $proportion * ($this->width - 120);
            $x = $baseX;

            $this->svg .= '<rect x="'.$x.'" y="'.$y.'" width="'.$barHeight.'" height="'.$heightRatio.'" fill="'.$this->colors[$counter].'"/>';

            $textX = $x + $barHeight / 2;
            $textY = $y + $heightRatio / 2;
            $this->svg .= '<text x="'.$textX.'" y="'.$textY.'" font-family="Arial" font-size="12" fill="'. $this->dataColor .'" text-anchor="middle" dominant-baseline="middle">'.$data.'</text>';

            $y -= $heightRatio + 10;
            $counter++;
        }

        return $this;
    }

    /**
     * Generate the labels below the Y axis of the bar chart.
     *
     * @return $this
     */
    protected function drawLabels()
    {
        $numData = count($this->data);
        $availableVerticalSpace = $this->height - 40;

        $totalSpace = $availableVerticalSpace - ($numData * 10);
        $heightRatio = $totalSpace / $numData;

        $baseX = 95;
        $baseY = $this->height - 20;

        $x = $baseX;
        $y = $baseY - $heightRatio;

        foreach ($this->labels as $key => $label) {
            $textY = $y + $heightRatio / 2;

            $this->svg .= '<text x="'.$x.'" y="'.$textY.'" font-family="Arial" font-size="14" fill="'.$this->colors[$key].'" text-anchor="end" dominant-baseline="middle">'.$label.'</text>';

            $y -= $heightRatio + 10;
        }

        return $this;
    }

    /**
     * Generate the X axis labels.
     *
     * @return $this
     */
    protected function drawSeries()
    {
        $numTicks = $this->calculateNumTicks();
        $maxValue = max($this->data);
        $maxValue = (($maxValue != 0) ? $maxValue : 1);
        $interval = $maxValue / ($numTicks - 1);

        $baseX = 100;
        $baseY = $this->height - 20;

        $y = $baseY + 20;

        for ($i = 0; $i < $numTicks; $i++) {
            $tickValue = $i * $interval;

            if ($tickValue != (int) $tickValue) {
                $tickValue = round($tickValue, 2);
            }

            $barWidth = ($tickValue / $maxValue) * ($this->width - 120);
            $x = $baseX + $barWidth;

            $this->svg .= '<line x1="'.$x.'" y1="'.$baseY.'" x2="'.$x.'" y2="'.($baseY + 10.5).'" stroke="'. $this->getAxisColor('x') .'" stroke-width="0.5" />';

            $labelX = $x;
            $this->svg .= '<text x="'.$labelX.'" y="'.$y.'" font-family="Arial" font-size="12" fill="'. $this->labelsColor .'" text-anchor="middle" dominant-baseline="text-before-edge" transform="rotate(-45, '.$x.', '.$y.')">'.$tickValue.'</text>';
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
