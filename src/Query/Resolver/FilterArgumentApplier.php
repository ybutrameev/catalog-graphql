<?php

declare(strict_types=1);


namespace ScandiPWA\CatalogGraphQl\Query\Resolver;


use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\GraphQl\Query\Resolver\Argument\AstConverter;
use Magento\Framework\GraphQl\Query\Resolver\Argument\Filter\ConnectiveFactory;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\ArgumentApplierInterface;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\FilterGroupFactory;

class FilterArgumentApplier implements ArgumentApplierInterface
{
    const ARGUMENT_NAME = 'filter';
    
    /**
     * @var FilterGroupFactory
     */
    private $filterGroupFactory;
    
    /**
     * @var AstConverter
     */
    private $astConverter;
    
    /**
     * @var ConnectiveFactory
     */
    private $connectiveFactory;
    
    /**
     * @param AstConverter $astConverter
     * @param FilterGroupFactory $filterGroupFactory
     * @param ConnectiveFactory $connectiveFactory
     */
    public function __construct(
        AstConverter $astConverter,
        FilterGroupFactory $filterGroupFactory,
        ConnectiveFactory $connectiveFactory
    ) {
        $this->astConverter = $astConverter;
        $this->filterGroupFactory = $filterGroupFactory;
        $this->connectiveFactory = $connectiveFactory;
    }
    
    /**
     * {@inheritdoc}
     */
    public function applyArgument(
        SearchCriteriaInterface $searchCriteria,
        string $fieldName,
        string $argumentName,
        array $argument
    ) : SearchCriteriaInterface {
        $filters = $this->astConverter->getClausesFromAst($fieldName, $argument);
        $configurable = ['size', 'color', 'shoes_size'];
        foreach ($filters as $filter) {
            $fieldName = $filter->getFieldName();
            if (in_array($fieldName, $configurable)) {
                $searchCriteria->setIsLayered(true);
            }
        }
        $filtersForGroup = $this->connectiveFactory->create($filters);
        $filterGroups = $searchCriteria->getFilterGroups();
        $filterGroups = array_merge($filterGroups, $this->filterGroupFactory->create($filtersForGroup));
        $searchCriteria->setFilterGroups($filterGroups);
        return $searchCriteria;
    }
}