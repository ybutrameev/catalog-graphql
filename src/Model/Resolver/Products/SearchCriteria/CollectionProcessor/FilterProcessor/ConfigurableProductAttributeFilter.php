<?php
/**
 * ScandiPWA_CatalogGraphQl
 *
 * @category    ScandiPWA
 * @package     ScandiPWA_CatalogGraphQl
 * @author      Viktors Pliska <info@scandiweb.com>
 * @copyright   Copyright (c) 2018 Scandiweb, Ltd (https://scandiweb.com)
 */

declare(strict_types=1);

namespace ScandiPWA\CatalogGraphQl\Model\Resolver\Products\SearchCriteria\CollectionProcessor\FilterProcessor;

use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor\CustomFilterInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Api\Filter;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;

/**
 * Category filter allows to filter products collection using custom defined filters from search criteria.
 */
class ConfigurableProductAttributeFilter implements CustomFilterInterface
{
    protected $configurable;
    protected $collectionFactory;

    public function __construct(
        Configurable $configurable,
        CollectionFactory $collectionFactory
    )
    {
        $this->configurable = $configurable;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param Filter $filter
     * @param AbstractDb $collection
     * @return bool
     */
    public function apply(Filter $filter, AbstractDb $collection)
    {
        $attributeName = $filter->getField();
        $attributeValue = $filter->getValue();
        $collection->addFieldToFilter($attributeName, $attributeValue);

        return true;
    }
}
