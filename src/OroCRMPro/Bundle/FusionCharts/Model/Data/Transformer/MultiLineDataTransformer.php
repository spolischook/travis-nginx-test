<?php

namespace OroCRMPro\Bundle\FusionCharts\Model\Data\Transformer;

use Oro\Bundle\ChartBundle\Model\Data\ArrayData;
use Oro\Bundle\ChartBundle\Model\Data\DataInterface;
use OroCRM\Bundle\CampaignBundle\Model\Data\Transformer\MultiLineDataTransformer as BaseTransformer;

class MultiLineDataTransformer extends BaseTransformer
{
    /**
     * @param DataInterface $data
     * @param array         $chartOptions
     *
     * @return DataInterface
     */
    public function transform(DataInterface $data, array $chartOptions)
    {
        $this->initialize($data, $chartOptions);

        $labels = $this->getLabels($this->sourceData, $this->labelKey);

        // create default values
        $values = array_fill(0, sizeof($labels), 0);
        $value  = array_combine($labels, $values);

        // set default values
        $values = [];
        foreach ($this->sourceData as $sourceDataValue) {
            $key = $sourceDataValue[$this->groupingOption];

            $values[$key] = $value;
        }

        // set values
        foreach ($this->sourceData as $sourceDataValue) {
            $key   = $sourceDataValue[$this->groupingOption];
            $label = $sourceDataValue[$this->labelKey];

            $values[$key][$label] = $sourceDataValue[$this->valueKey];
        }

        // set result data labels
        $labelSet = [];
        foreach ($labels as $label) {
            $labelSet[] = ['label' => $label];
        }

        // set result data values
        $dataSet = [];
        foreach ($values as $name => $dataSetValue) {
            $dataSetValues = [];

            foreach ($dataSetValue as $value) {
                $dataSetValues[] = ['value' => $value];
            }

            $dataSet[] = [
                'seriesname' => $name,
                'data'       => $dataSetValues
            ];
        }

        // create result
        $result = [
            'categories' => [
                'category' => $labelSet
            ],
            'dataset'    => $dataSet
        ];

        return new ArrayData($result);
    }
}
