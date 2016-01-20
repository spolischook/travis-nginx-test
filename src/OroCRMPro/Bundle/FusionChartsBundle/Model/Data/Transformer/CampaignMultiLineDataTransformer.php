<?php

namespace OroCRMPro\Bundle\FusionChartsBundle\Model\Data\Transformer;

use Oro\Component\PhpUtils\ArrayUtil;

use Oro\Bundle\ChartBundle\Model\Data\ArrayData;
use Oro\Bundle\ChartBundle\Model\Data\DataInterface;
use OroCRM\Bundle\CampaignBundle\Model\Data\Transformer\MultiLineDataTransformer as BaseTransformer;

class CampaignMultiLineDataTransformer extends BaseTransformer
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

        if (!$data->toArray()) {
            return new ArrayData([]);
        }

        $dataSet = [];
        $labels  = $this->getLabels();
        $keys    = array_unique(ArrayUtil::arrayColumn($this->sourceData, $this->groupingOption));
        foreach ($keys as $key) {
            $data = array_map(
                function ($label) use ($key) {
                    $counts = array_map(
                        function ($item) use ($label, $key) {
                            if ($item[$this->groupingOption] == $key && $item[$this->labelKey] == $label) {
                                return $item[$this->valueKey];
                            }

                            return 0;
                        },
                        $this->sourceData
                    );

                    return ['value' => array_sum($counts)];
                },
                $labels
            );

            $dataSet[] = [
                'seriesname' => $key,
                'data'       => $data
            ];
        }

        $result = [
            'categories' => [
                'category' => array_map(
                    function ($item) {
                        return ['label' => $item];
                    },
                    $labels
                )
            ],
            'dataset'    => $dataSet
        ];

        return new ArrayData($result);
    }
}
