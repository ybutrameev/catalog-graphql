<?php
/**
 * ScandiPWA_CatalogGraphQl
 *
 * @category    ScandiPWA
 * @package     ScandiPWA_CatalogGraphQl
 * @author      <info@scandiweb.com>
 * @copyright   Copyright (c) 2018 Scandiweb, Ltd (https://scandiweb.com)
 */

declare(strict_types=1);

namespace ScandiPWA\CatalogGraphQl\Model\Resolver\Products\DataProvider;

use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\CollectionFactory as FulltextCollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessorInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Product field data provider, used for GraphQL resolver processing.
 */
class Product extends \Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    
    /**
     * @var ProductSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;
    
    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;
    
    /**
     * @var Visibility
     */
    private $visibility;
    
    /**
     * @param CollectionFactory                    $collectionFactory
     * @param ProductSearchResultsInterfaceFactory $searchResultsFactory
     * @param Visibility                           $visibility
     * @param CollectionProcessorInterface         $collectionProcessor
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ProductSearchResultsInterfaceFactory $searchResultsFactory,
        Visibility $visibility,
        CollectionProcessorInterface $collectionProcessor,
        FulltextCollectionFactory $ftc
    )
    {
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->visibility = $visibility;
        $this->collectionProcessor = $collectionProcessor;
    }
    
    /**
     * Gets list of product data with full data set. Adds eav attributes to result set from passed in array
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @param string[]                $attributes
     * @param bool                    $isSearch
     * @param bool                    $isChildSearch
     * @return SearchResultsInterface
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria,
        array $attributes = [],
        bool $isSearch = false,
        bool $isChildSearch = false
    ): SearchResultsInterface
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        /**
         * In some scenarios SearchCriteria is not resolved over DI (WTF?),
         * using builder name to determine object instance, therefore we
         * must check for the instance type.
         */
        $t = 't';
        $ftc = ObjectManager::getInstance()->get(FulltextCollectionFactory::class);
        if (method_exists($searchCriteria, 'isLayered') && $searchCriteria->isLayered()) {
            $newCollection = ObjectManager::getInstance()->get(Collection::class);
            $collection = clone $newCollection;
            $collection->clear();
        } else {
            $collection = $this->collectionFactory->create();
        }
        
        $this->collectionProcessor->process($collection, $searchCriteria, $attributes);
        
        if (!$isChildSearch) {
            $visibilityIds = $isSearch
                ? $this->visibility->getVisibleInSearchIds()
                : $this->visibility->getVisibleInCatalogIds();
            $collection->setVisibility($visibilityIds);
        }
        $collection->load();
        
        // Methods that perform extra fetches post-load
        if (in_array('media_gallery_entries', $attributes)) {
            $collection->addMediaGalleryData();
        }
        if (in_array('options', $attributes)) {
            $collection->addOptionsToResult();
        }
        
        $searchResult = $this->searchResultsFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());
        return $searchResult;
    }
}
