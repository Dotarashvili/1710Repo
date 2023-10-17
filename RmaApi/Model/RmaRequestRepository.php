<?php
declare(strict_types=1);

namespace DevAll\RmaApi\Model;

use DevAll\RmaApi\Api\RmaRequestRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\User\Model\UserFactory;
use Mirasvit\Rma\Api\Config\AttachmentConfigInterface;
use Mirasvit\Rma\Api\Data\ItemInterface;
use Mirasvit\Rma\Api\Repository\AddressRepositoryInterface;
use Mirasvit\Rma\Api\Repository\ConditionRepositoryInterface;
use Mirasvit\Rma\Api\Repository\MessageRepositoryInterface;
use Mirasvit\Rma\Api\Repository\ReasonRepositoryInterface;
use Mirasvit\Rma\Api\Repository\ResolutionRepositoryInterface;
use Mirasvit\Rma\Api\Repository\RmaRepositoryInterface;
use Mirasvit\Rma\Api\Repository\StatusRepositoryInterface;
use Mirasvit\Rma\Api\Service\Attachment\AttachmentManagementInterface;
use Mirasvit\Rma\Api\Service\Item\ItemManagement\QuantityInterface;
use Mirasvit\Rma\Api\Service\Rma\RmaManagementInterface;
use Mirasvit\Rma\Helper\Rma\Url;
use Mirasvit\Rma\Model\ResourceModel\Rma\CollectionFactory;

class RmaRequestRepository implements RmaRequestRepositoryInterface
{
    /**
     * @var RmaRepositoryInterface
     */
    protected $rmaRepository;

    /**
     * @var CollectionFactory
     */
    protected $rmaCollectionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var UserFactory
     */
    private $userFactory;
    /**
     * @var StatusRepositoryInterface
     */
    private $statusRepository;
    /**
     * @var Url
     */
    private $rmaUrl;
    /**
     * @var RmaManagementInterface
     */
    private $rmaManagement;
    /**
     * @var ItemInterface
     */
    private $itemInterface;
    /**
     * @var MessageRepositoryInterface
     */
    private $messageRepository;
    /**
     * @var AttachmentManagementInterface
     */
    private $attachmentManagement;
    /**
     * @var QuantityInterface
     */
    private $quantity;
    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;
    /**
     * @var ConditionRepositoryInterface
     */
    private $conditionRepository;
    /**
     * @var ReasonRepositoryInterface
     */
    private $reasonRepository;
    /**
     * @var ResolutionRepositoryInterface
     */
    private $resolutionRepository;

    /**
     * Class Constructor
     *
     * @param RmaRepositoryInterface $rmaRepository
     * @param CollectionFactory $rmaCollectionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param UserFactory $userFactory
     * @param StatusRepositoryInterface $statusRepository
     * @param Url $rmaUrl
     * @param RmaManagementInterface $rmaManagement
     * @param ItemInterface $itemInterface
     * @param MessageRepositoryInterface $messageRepository
     * @param AttachmentManagementInterface $attachmentManagement
     * @param QuantityInterface $quantity
     * @param AddressRepositoryInterface $addressRepository
     * @param ConditionRepositoryInterface $conditionRepository
     * @param ReasonRepositoryInterface $reasonRepository
     * @param ResolutionRepositoryInterface $resolutionRepository
     */
    public function __construct(
        RmaRepositoryInterface        $rmaRepository,
        CollectionFactory             $rmaCollectionFactory,
        SearchCriteriaBuilder         $searchCriteriaBuilder,
        UserFactory                   $userFactory,
        StatusRepositoryInterface     $statusRepository,
        Url                           $rmaUrl,
        RmaManagementInterface        $rmaManagement,
        ItemInterface                 $itemInterface,
        MessageRepositoryInterface    $messageRepository,
        AttachmentManagementInterface $attachmentManagement,
        QuantityInterface             $quantity,
        AddressRepositoryInterface    $addressRepository,
        ConditionRepositoryInterface  $conditionRepository,
        ReasonRepositoryInterface     $reasonRepository,
        ResolutionRepositoryInterface $resolutionRepository
    )
    {
        $this->rmaRepository = $rmaRepository;
        $this->rmaCollectionFactory = $rmaCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->userFactory = $userFactory;
        $this->statusRepository = $statusRepository;
        $this->rmaUrl = $rmaUrl;
        $this->rmaManagement = $rmaManagement;
        $this->itemInterface = $itemInterface;
        $this->messageRepository = $messageRepository;
        $this->attachmentManagement = $attachmentManagement;
        $this->quantity = $quantity;
        $this->addressRepository = $addressRepository;
        $this->conditionRepository = $conditionRepository;
        $this->reasonRepository = $reasonRepository;
        $this->resolutionRepository = $resolutionRepository;
    }

    /**
     * Get full list of RMA requests
     *
     * @param SearchCriteriaInterface|null $searchCriteria
     * @return array
     * @throws NoSuchEntityException
     */
    public function getList(SearchCriteriaInterface $searchCriteria = null): array
    {
        if ($searchCriteria === null) {
            $searchCriteria = $this->searchCriteriaBuilder->create();
        }
        $rmalist = $this->rmaRepository->getList($searchCriteria);

        $rmas = [];
        foreach ($rmalist->getItems() as $rma) {
            $rmaItem = $this->rmaRepository->getCollection()
                ->addFieldToFilter('rma_id', $rma['rma_id'])
                ->getFirstItem();

            $rmaArray = $this->getRmaData($rmaItem);
            $rmas[] = $rmaArray;
        }
        return $rmas;
    }

    /**
     * Get RMA request by ID
     *
     * @param $id
     * @return array
     * @throws NoSuchEntityException
     */
    public function getById($id): array
    {
        $rma = $this->rmaRepository->getCollection()
            ->addFieldToFilter('rma_id', $id)
            ->getFirstItem();
        return $this->getRmaData($rma);
    }

    /**
     * Get RMA status by ID
     *
     * @param $id
     * @return array
     * @throws NoSuchEntityException
     */
    public function getStatusById($id): array
    {
        $rmaStatus  = $this->statusRepository->get($id);
        $statusRma = [];
        $statusRma['status']['id'] = $rmaStatus->getId();
        $statusRma['status']['name'] = $rmaStatus->getName();
        $statusRma['status']['description'] = $rmaStatus->getHistoryMessage();
        return $statusRma;
    }

    /**
     * Get RMA resolution by ID
     *
     * @param $id
     * @return array
     * @throws NoSuchEntityException
     */
    public function getResolutionById($id): array
    {
        $rmaResolution = $this->resolutionRepository->get($id);
        $resolutionRma = [];
        $resolutionRma['resolution']['id'] = $rmaResolution->getId();
        $resolutionRma['resolution']['name'] = $rmaResolution->getName();
        return $resolutionRma;
    }

    /**
     * Get RMA condition by ID
     *
     * @param $id
     * @return array
     * @throws NoSuchEntityException
     */
    public function getConditionsById($id): array
    {
        $rmaConditions = $this->conditionRepository->get($id);
        $conditionsRma = [];
        $conditionsRma['condition']['id'] = $rmaConditions->getId();
        $conditionsRma['condition']['name'] = $rmaConditions->getName();
        return $conditionsRma;
    }

    /**
     * Get RMA reason by ID
     *
     * @param $id
     * @return array
     * @throws NoSuchEntityException
     */
    public function getReasonsById($id): array
    {
        $rmaReasons = $this->reasonRepository->get($id);
        $reasonsRma = [];
        $reasonsRma['reason']['id'] = $rmaReasons->getId();
        $reasonsRma['reason']['name'] = $rmaReasons->getName();
        return $reasonsRma;
    }

    /**
     * Get RMA return address by ID
     *
     * @param $id
     * @return array
     * @throws NoSuchEntityException
     */
    public function getReturnAddressById($id): array
    {
        $rmaReturn  = $this->addressRepository->get($id);
        $returnRma = [];
        $returnRma['return_address']['id'] = $rmaReturn->getId();
        $returnRma['return_address']['name'] = $rmaReturn->getName();
        $returnRma['return_address']['address'] = $rmaReturn->getAddress();
        return $returnRma;
    }

    /**
     * Get RMA data
     *
     * @param $rma
     * @return array
     * @throws NoSuchEntityException
     */
    public function getRmaData($rma): array
    {
        $returnLabelUrl = '';

        $rmaData = $rma->getData();

        $ownerName = $this->userFactory->create()->load($rma->getUserId())->getName();
        $status = $this->statusRepository->get($rma->getStatusId())->getId();
        $externalLink = $this->rmaUrl->getGuestUrl($rma);
        if (!empty($this->attachmentManagement->getAttachment(
            AttachmentConfigInterface::ATTACHMENT_ITEM_RETURN_LABEL, $rma->getId()
        ))) {
            $returnLabel = $this->attachmentManagement->getAttachment(
                AttachmentConfigInterface::ATTACHMENT_ITEM_RETURN_LABEL, $rma->getId()
            );
            $returnLabelUrl = $this->attachmentManagement->getUrl($returnLabel);
        }
        $returnAddress = $this->getReturnAddressIdsByRma($rma->getId());
        $orderId = $this->rmaManagement->getOrder($rma)->getIncrementId();
        $orderItems = $this->rmaManagement->getOrder($rma)->getItems();
        $message = $this->messageRepository->create();
        $messageId = $message->getCollection()->addFieldToFilter('rma_id', $rma->getId())->getFirstItem()->getId();
        $messageText = $this->messageRepository->get($messageId)->getText();

        $groupedItems = [];
        foreach ($orderItems as $item) {
            $parentId = $item->getParentItemId();
            if ($parentId) {
                $groupedItems[$parentId]['configurations'][] = $item;
            } else {
                $groupedItems[$item->getId()]['product'] = $item;
            }
        }

        $items = $this->getGroupedItemsData($groupedItems);

        $rmaArray = [];
        $rmaArray['rma']['increment_id'] = $rmaData['increment_id'];
        $rmaArray['rma']['firstname'] = $rmaData['firstname'];
        $rmaArray['rma']['lastname'] = $rmaData['lastname'];
        $rmaArray['rma']['email'] = $rmaData['email'];
        $rmaArray['rma']['telephone'] = $rmaData['telephone'];
        $rmaArray['rma']['company'] = $rmaData['company'];
        $rmaArray['rma']['owner_name'] = !empty($ownerName) ? $ownerName : 'RMA Owner Not Set';
        $rmaArray['rma']['status'] = $status;
        $rmaArray['rma']['external_link'] = $externalLink;
        $rmaArray['rma']['return_label'] = !empty($returnLabelUrl) ? $returnLabelUrl : 'Return Label Not Set';
        $rmaArray['rma']['return_address'] = !empty($returnAddress) ? $returnAddress : 'Return Address Not Set';
        $rmaArray['rma']['order_id'] = $orderId;
        $rmaArray['rma']['message'] = $messageText;
        $rmaArray['rma']['created_at'] = $rmaData['created_at'];
        $rmaArray['rma']['updated_at'] = $rmaData['updated_at'];
        $rmaArray['rma']['items'] = $items;

        return $rmaArray;
    }

    /**
     * Get RMA items data
     *
     * @param $item
     * @return array
     */
    private function getItemData($item): array
    {
        $itemData = [];
        if($this->itemInterface->load($item->getItemId(), 'order_item_id')->getQtyRequested()) {
            $itemData = [
                'item' => $item->getName(),
                'sku' => $item->getSku(),
                'qty' => $item->getQtyOrdered(),
                'price' => $item->getPrice(),
                'condition' => $this->itemInterface->getConditionId(),
                'resolution' => $this->itemInterface->getResolutionId(),
                'reason' => $this->itemInterface->getReasonId(),
                'qty_to_return' => $this->itemInterface->load($item->getItemId(), 'order_item_id')->getQtyRequested(),
                'qty_ordered' => $this->quantity->getQtyOrdered($this->itemInterface->load($item->getItemId(), 'order_item_id')),
                'qty_available' => $this->quantity->getQtyAvailable($this->itemInterface->load($item->getItemId(), 'order_item_id')),
                'qty_stock' => $this->quantity->getQtyStock($this->itemInterface->load($item->getItemId(), 'order_item_id')->getId()),
                'qty_returned' => $item->getQtyRefunded()
            ];
        }

        return $itemData;
    }

    /**
     * Get grouped items data
     *
     * @param $groupedItems
     * @return array
     */
    private function getGroupedItemsData($groupedItems): array
    {
        $items = [];

        foreach ($groupedItems as $groupId => $group) {
            $product = $group['product'];
            $itemData = $this->getItemData($product);

            if (isset($group['configurations'])) {
                foreach ($group['configurations'] as $configItem) {
                    $configData = $this->getItemData($configItem);
                    $mergedData = array_merge($itemData, $configData);

                    if (!empty($mergedData)) {
                        $items[] = $mergedData;
                    }
                }
            } else {
                if (!empty($itemData)) {
                    $items[] = $itemData;
                }
            }
        }

        return $items;
    }

    /**
     * Get return address ids by rma
     *
     * @param $rmaId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getReturnAddressIdsByRma($rmaId): array
    {
        $rma = $this->rmaRepository->get($rmaId);
        $returnAddressName = $rma->getReturnAddress();

        $searchCriteria = $this->searchCriteriaBuilder->addFilter('address', $returnAddressName)
            ->create();

        $addressSearchResults = $this->addressRepository->getList($searchCriteria);
        $returnAddresses = $addressSearchResults->getItems();

        $returnAddressIds = [];
        foreach ($returnAddresses as $returnAddress) {
            $returnAddressIds = $returnAddress->getId();
        }

        return $returnAddressIds;
    }
}