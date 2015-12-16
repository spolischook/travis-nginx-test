<?php

namespace OroB2B\Bundle\PricingBundle\Filter;

use Doctrine\ORM\Query\Expr\Join;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\NumberRangeFilter;

use OroB2B\Bundle\PricingBundle\Form\Type\Filter\ProductPriceFilterType;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;

class ProductPriceFilter extends NumberRangeFilter
{
    /**
     * @var ProductUnitLabelFormatter
     */
    protected $formatter;

    /**
     * @var PriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * @param FormFactoryInterface $factory
     * @param FilterUtility $util
     * @param ProductUnitLabelFormatter $formatter
     * @param PriceListRequestHandler $priceListRequestHandler
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        ProductUnitLabelFormatter $formatter,
        PriceListRequestHandler $priceListRequestHandler
    ) {
        parent::__construct($factory, $util);
        $this->formatter = $formatter;
        $this->priceListRequestHandler = $priceListRequestHandler;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return ProductPriceFilterType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data || !($ds instanceof OrmFilterDatasourceAdapter)) {
            return false;
        }

        $this->qbPrepare($ds, $data['unit']);

        $joinAlias = $this->getJoinAlias();

        $this->applyFilterToClause(
            $ds,
            $this->buildRangeComparisonExpr(
                $ds,
                $data['type'],
                $joinAlias . '.value',
                $data['value'],
                $data['value_end']
            )
        );

        return true;
    }

    /**
     * @return string
     */
    protected function getJoinAlias()
    {
        return 'product_price_' . $this->get('data_name');
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     * @param string $unit
     */
    protected function qbPrepare(OrmFilterDatasourceAdapter $ds, $unit)
    {
        $qb = $ds->getQueryBuilder();

        $rootAliasCollection = $qb->getRootAliases();
        $rootAlias = reset($rootAliasCollection);
        $joinAlias = $this->getJoinAlias();

        $currency = $this->get('data_name');

        $qb->innerJoin(
            'OroB2BPricingBundle:ProductPrice',
            $joinAlias,
            Join::WITH,
            $rootAlias . '.id = IDENTITY(' . $joinAlias . '.product)'
        );

        $this->addEqExpr(
            $ds,
            $joinAlias . '.priceList',
            $ds->generateParameterName('priceList'),
            $this->priceListRequestHandler->getPriceList()
        );
        $this->addEqExpr($ds, $joinAlias . '.currency', $ds->generateParameterName('currency'), $currency);
        $this->addEqExpr($ds, $joinAlias . '.quantity', $ds->generateParameterName('quantity'), 1);
        $this->addEqExpr($ds, 'IDENTITY(' . $joinAlias . '.unit)', $ds->generateParameterName('unit'), $unit);
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param string $fieldName
     * @param string $parameterName
     * @param mixed $parameterValue
     */
    protected function addEqExpr(FilterDatasourceAdapterInterface $ds, $fieldName, $parameterName, $parameterValue)
    {
        $this->applyFilterToClause($ds, $ds->expr()->eq($fieldName, $parameterName, true));
        $ds->setParameter($parameterName, $parameterValue);
    }

    /**
     * @param mixed $data
     *
     * @return array|bool
     */
    public function parseData($data)
    {
        if (false === ($data = parent::parseData($data))) {
            return false;
        }

        if (empty($data['unit'])) {
            return false;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();
        $metadata['unitChoices'] = [];

        $unitChoices = $this->getForm()->createView()['unit']->vars['choices'];
        foreach ($unitChoices as $choice) {
            $metadata['unitChoices'][] = [
                'data' => $choice->data,
                'value' => $choice->value,
                'label' => $choice->label,
                'shortLabel' => $this->formatter->format($choice->value, true),
            ];
        }

        return $metadata;
    }
}
