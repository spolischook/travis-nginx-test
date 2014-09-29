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

        foreach ($data as $lineName => $lineData) {
            $setData = [];
            $fillCategories = empty($result['categories']['category']);

            foreach ($lineData as $lineItem) {
                $label = $lineItem['label'];
                $value = $lineItem['value'];

                if ($fillCategories) {
                    $result['categories']['category'][] = ['label' => $label];
                }

                $setData[] = ['value' => $value];
            }

            $result['dataset'][] = ['seriesname' => $lineName, 'data' => $setData];
        }

        return new ArrayData($result);
    }
}
