<?php

namespace Xanpena\SVGChartBuilder\Svg;

class PieChartBuilder extends BaseChartBuilder {

    protected int $width = 400;
    protected int $height = 400;

    /**
     * Generate the SVG representation of the chart.
     *
     * @return string The SVG representation of the chart.
     */
    public function makeSvg()
    {
        $this->openSvgTag()
            ->drawGraphData()
            ->drawLabels()
            ->closeSvgTag();

        return $this->svg;
    }

    /**
     * Draw the slices of the pie chart based on the data.
     *
     * @return $this
     */
    protected function drawGraphData()
    {
        $totalValue = array_sum($this->data);
        $startAngle = 0;
        $counter = 0;

        foreach ($this->data as $value) {
            if ($value <= 0) {
                continue;
            }

            if ($counter >= count($this->colors)) {
                $counter = 0;
            }

            $proportion = $value / $totalValue;
            $endAngle = $startAngle + ($proportion * 360);

            $startX = $this->width / 2;
            $startY = $this->height / 2;

            $endX1 = $startX + cos(deg2rad($startAngle)) * ($this->width / 2);
            $endY1 = $startY + sin(deg2rad($startAngle)) * ($this->height / 2);

            $endX2 = $startX + cos(deg2rad($endAngle)) * ($this->width / 2);
            $endY2 = $startY + sin(deg2rad($endAngle)) * ($this->height / 2);

            $largeArcFlag = $proportion > 0.5 ? 1 : 0;

            $this->svg .= '<path d="M'.$startX.','.$startY.' L'.$endX1.','.$endY1.' A'.($this->width / 2).','.($this->height / 2).' 0 '.$largeArcFlag.',1 '.$endX2.','.$endY2.' Z" fill="'.$this->colors[$counter].'"/>';

            $startAngle = $endAngle;
            $counter++;
        }

        return $this;
    }

    /**
     * Draw the labels for each slice on the chart.
     *
     * @return $this
     */
    protected function drawLabels()
    {
        $totalValue = array_sum($this->data);
        $startAngle = 0;

        foreach ($this->data as $index => $value) {
            if ($value <= 0) {
                continue;
            }

            $label = $value;
            if (array_key_exists($index, $this->labels)) {
                $label = $this->labels[$index] . ' ('.$value.')';
            }

            $proportion = $value / $totalValue;
            $endAngle = $startAngle + ($proportion * 360);

            $midAngle = $startAngle + ($endAngle - $startAngle) / 2;

            $labelX = $this->width / 2 + cos(deg2rad($midAngle)) * ($this->width / 4);
            $labelY = $this->height / 2 + sin(deg2rad($midAngle)) * ($this->height / 4);

            $this->svg .= '<text x="'.$labelX.'" y="'.$labelY.'" font-family="Arial" font-size="14" fill="'. $this->labelsColor .'" text-anchor="middle" dominant-baseline="middle">'.$label.'</text>';

            $startAngle = $endAngle;
        }

        return $this;
    }
}
