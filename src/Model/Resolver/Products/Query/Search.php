<?php
/**
 * ScandiPWA_CatalogGraphQl
 *
 * @category    ScandiPWA
 * @package     ScandiPWA_CatalogGraphQl
 * @author      Raivis Dejus <info@scandiweb.com>
 * @copyright   Copyright (c) 2019 Scandiweb, Ltd (https://scandiweb.com)
 */
declare(strict_types=1);

namespace ScandiPWA\CatalogGraphQl\Model\Resolver\Products\Query;

use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\SearchCriteria\Helper\Filter as FilterHelper;
use Magento\Search\Model\Search\PageSizeProvider;
use ScandiPWA\CatalogGraphQl\Model\Resolver\Products\SearchResult;
use ScandiPWA\CatalogGraphQl\Model\Resolver\Products\SearchResultFactory;
use Magento\Search\Api\SearchInterface;

/**
 * Full text search for catalog using given search criteria.
 * Adds support for min and manx price values
 */
class Search
{
    /**
     * @var SearchInterface
     */
    private $search;

    /**
     * @var FilterHelper
     */
    private $filterHelper;

    /**
     * @var Filter
     */
    private $filterQuery;

    /**
     * @var SearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var PageSizeProvider
     */
    private $pageSizeProvider;

    /**
     * @param SearchInterface $search
     * @param FilterHelper $filterHelper
     * @param Filter $filterQuery
     * @param SearchResultFactory $searchResultFactory
     * @param MetadataPool $metadataPool
     * @param PageSizeProvider $pageSize
     */
    public function __construct(
        SearchInterface $search,
        FilterHelper $filterHelper,
        Filter $filterQuery,
        SearchResultFactory $searchResultFactory,
        MetadataPool $metadataPool,
        PageSizeProvider $pageSize
    ) {
        $this->search = $search;
        $this->filterHelper = $filterHelper;
        $this->filterQuery = $filterQuery;
        $this->searchResultFactory = $searchResultFactory;
        $this->metadataPool = $metadataPool;
        $this->pageSizeProvider = $pageSize;
    }

    /**
     * Return results of full text catalog search of given term, and will return filtered results if filter is specified
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @param ResolveInfo $info
     * @return SearchResult
     * @throws \Exception
     */
    public function getResult(
        SearchCriteriaInterface $searchCriteria,
        ResolveInfo $info,
        array $fields
    ) : SearchResult {
        $isReturnCount = in_array('total_count', $fields, true);
        $isReturnItems = in_array('items', $fields, true);
        $isReturnMinMax = count(array_intersect($fields, ['max_price', 'min_price'])) > 0;

        $idField = $this->metadataPool->getMetadata(
            \Magento\Catalog\Api\Data\ProductInterface::class
        )->getIdentifierField();

        $realPageSize = $searchCriteria->getPageSize();
        $realCurrentPage = $searchCriteria->getCurrentPage();

        // Current page must be set to 0 and page size to max for search to grab all ID's as temporary workaround
        $pageSize = $this->pageSizeProvider->getMaxPageSize();
        $searchCriteria->setPageSize($pageSize);
        $searchCriteria->setCurrentPage(0);

        $itemsResults = $this->search->search($searchCriteria);

        $ids = [];
        $searchIds = [];

        foreach ($itemsResults->getItems() as $item) {
            $ids[$item->getId()] = null;
            $searchIds[] = $item->getId();
        }

        // TODO: this is reported to cause wrong sorting of items
        $filter = $this->filterHelper->generate($idField, 'in', $searchIds);
        $searchCriteria = $this->filterHelper->remove($searchCriteria, 'search_term');
        $searchCriteria = $this->filterHelper->add($searchCriteria, $filter);
        $searchResult = $this->filterQuery->getResult($searchCriteria, $info, $fields, true);

        $searchCriteria->setPageSize($realPageSize);
        $searchCriteria->setCurrentPage($realCurrentPage);
        $paginatedProducts = $this->paginateList($searchResult, $searchCriteria);

        $products = [];
        if (!isset($searchCriteria->getSortOrders()[0])) {
            foreach ($paginatedProducts as $product) {
                if (in_array($product[$idField], $searchIds, true)) {
                    $ids[$product[$idField]] = $product;
                }
            }

            $products = array_filter($ids);
        } else {
            foreach ($paginatedProducts as $product) {
                $productId = $product['entity_id'] ?? $product[$idField];
                if (in_array($productId, $searchIds, true)) {
                    $products[] = $product;
                }
            }
        }

        return $this->searchResultFactory->create(
            $isReturnCount ? $searchResult->getTotalCount() : 0,
            $isReturnMinMax ? $searchResult->getMinPrice() : 0,
            $isReturnMinMax ? $searchResult->getMaxPrice() : 0,
            $isReturnItems ? $products : []
        );
    }


    /**
     * Paginate an array of Ids that get pulled back in search based off search criteria and total count.
     *
     * @param SearchResult $searchResult
     * @param SearchCriteriaInterface $searchCriteria
     * @return int[]
     */
    private function paginateList(SearchResult $searchResult, SearchCriteriaInterface $searchCriteria) : array
    {
        $length = $searchCriteria->getPageSize();
        // Search starts pages from 0
        $offset = $length * ($searchCriteria->getCurrentPage() - 1);

        $maxPages = 0;
        if ($searchCriteria->getPageSize()) {
            $maxPages = ceil($searchResult->getTotalCount() / $searchCriteria->getPageSize()) - 1;
        }

        if ($searchCriteria->getCurrentPage() > $maxPages && $searchResult->getTotalCount() > 0) {
            $offset = (int)$maxPages;
        }
        return array_slice($searchResult->getProductsSearchResult(), $offset, $length);
    }
}
