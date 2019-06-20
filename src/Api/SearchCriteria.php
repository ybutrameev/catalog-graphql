<?php


namespace ScandiPWA\CatalogGraphQl\Api;


use Magento\Framework\Api\Search\SearchCriteria as CoreSearchCriteria;

class SearchCriteria extends CoreSearchCriteria
{
    public const LAYERED = 'layered';
    
    /**
     * @param bool $bool
     * @return SearchCriteria
     */
    public function setIsLayered(bool $bool): self
    {
        $this->setData(self::LAYERED, $bool);
        
        return $this;
    }
    
    /**
     * @return bool
     */
    public function isLayered(): bool
    {
        return !!$this->_get(self::LAYERED);
    }
}