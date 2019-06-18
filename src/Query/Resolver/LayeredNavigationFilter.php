<?php


namespace ScandiPWA\CatalogGraphQl\Query\Resolver;


use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\ArgumentApplierInterface;

class LayeredNavigationFilter implements ArgumentApplierInterface
{
    /**
     * {@inheritdoc}
     */
    public function applyArgument(
        SearchCriteriaInterface $searchCriteria,
        string $fieldName,
        string $argumentName,
        array $argument
    ) : SearchCriteriaInterface {
        if ($fieldName === 'products' && reset($argument)) {
            $searchCriteria->setIsLayered(true);
        }
        return $searchCriteria;
    }
}