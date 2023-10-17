<?php

namespace DevAll\RmaApi\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Mirasvit\Rma\Api\Data\RmaInterface;
use Mirasvit\Rma\Api\Data\RmaSearchResultsInterface;

interface RmaRequestRepositoryInterface
{
    /**
     * Get all RMA requests.
     *
     * @param SearchCriteriaInterface|null $searchCriteria
     * @return array
     */
    public function getList(SearchCriteriaInterface $searchCriteria = null);

    /**
     * Get a specific RMA request.
     *
     * @param int $id
     * @return RmaInterface
     */
    public function getById($id);


    /**
     * @param int $id
     * @return mixed
     */
    public function getStatusById($id);

    /**
     * @param int $id
     * @return mixed
     */
    public function getResolutionById($id);

    /**
     * @param int $id
     * @return mixed
     */
    public function getConditionsById($id);

    /**
     * @param int $id
     * @return mixed
     */
    public function getReasonsById($id);

    /**
     * @param int $id
     * @return mixed
     */
    public function getReturnAddressById($id);
}