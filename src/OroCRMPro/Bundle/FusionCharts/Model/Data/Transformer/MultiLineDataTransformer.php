<?php

namespace OroCRMPro\Bundle\FusionCharts\Model\Data\Transformer;

use Oro\Bundle\ChartBundle\Model\Data\ArrayData;
use Oro\Bundle\ChartBundle\Model\Data\DataInterface;
use Oro\Bundle\ChartBundle\Model\Data\MappedData;
use Oro\Bundle\ChartBundle\Model\Data\Transformer\TransformerInterface;

class MultiLineDataTransformer implements TransformerInterface
{
    /**
     * @param DataInterface $data
     * @param array         $chartOptions
     *
     * @return DataInterface
     */
    public function transform(DataInterface $data, array $chartOptions)
    {
        if (empty($chartOptions['default_settings']['groupingOption'])) {
            return new ArrayData($data->toArray());
        }

        /** @var MappedData $data */
        $sourceData     = $data->getSourceData()->toArray();
        $labelKey       = $chartOptions['data_schema']['label']['field_name'];
        $valueKey       = $chartOptions['data_schema']['value']['field_name'];
        $groupingOption = $chartOptions['default_settings']['groupingOption'];

        // get labels
        $labels = [];
        foreach ($sourceData as $sourceDataValue) {
            $labels[] = $sourceDataValue[$labelKey];
        }
        asort($labels);
        $labels = array_unique($labels);

        // create default values
        $values = array_fill(0, sizeof($labels), 0);
        $value  = array_combine($labels, $values);

        // set default values
        $values = [];
        foreach ($sourceData as $sourceDataValue) {
            $key = $sourceDataValue[$groupingOption];

            $values[$key] = $value;
        }

        // set values
        foreach ($sourceData as $sourceDataValue) {
            $key   = $sourceDataValue[$groupingOption];
            $label = $sourceDataValue[$labelKey];

            $values[$key][$label] = $sourceDataValue[$valueKey];
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
