<?php

namespace OroCRMPro\Bundle\FusionChartsBundle\Model\Data\Transformer;

use Oro\Bundle\ChartBundle\Model\Data\ArrayData;
use Oro\Bundle\ChartBundle\Model\Data\DataInterface;
use Oro\Bundle\ChartBundle\Model\Data\Transformer\TransformerInterface;

class MultiSetDataTransformer implements TransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform(DataInterface $data, array $chartOptions)
    {
        $data = $data->toArray();
        if (!$data) {
            return new ArrayData([]);
        }

        $result = [
            'categories' => ['category' => []],
            'dataset' => [],
        ];

        $categorySet = [];

        foreach ($data as $lineName => $lineData) {
            $setData = [];

            foreach ($lineData as $lineItem) {
                $label = $lineItem['label'];
                $value = $lineItem['value'];

                if (!isset($categorySet[$label])) {
                    $categorySet[$label] = true;
                }

                $setData[] = ['value' => $value];
            }

            $result['dataset'][] = ['seriesname' => $lineName, 'data' => $setData];
        }

        foreach ($categorySet as $label => $value) {
            $result['categories']['category'][] = ['label' => $label];
        }

        return new ArrayData($result);
    }
}
