<?php
declare(strict_types=1);

namespace DevAll\SkuCategoryUpdater\Controller\Adminhtml\Index;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Psr\Log\LoggerInterface;

class Run extends Action
{
    CONST DEFAULT_CATEGORY = 'Default Category/';

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CategoryLinkManagementInterface
     */
    protected $categoryLinkManagement;

    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Class constructor.
     *
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryLinkManagementInterface $categoryLinkManagement
     * @param CollectionFactory $categoryCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        CategoryLinkManagementInterface $categoryLinkManagement,
        CollectionFactory $categoryCollectionFactory,
        ResourceConnection $resourceConnection,
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->logger = $logger;
    }

    /**
     * Add products to category.
     *
     * @return ResponseInterface|Json|(Json&ResultInterface)|ResultInterface
     * @throws Exception
     */
    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $skus = $this->getRequest()->getParam('skus');
        $categoryPath = self::DEFAULT_CATEGORY . $this->getRequest()->getParam('category');
        $categoryId = $this->getCategoryIdByPath($categoryPath);
        $failedSkus = [];
        $completedSkus = 0;
        $hasErrors = false;

        foreach ($skus as $sku) {
            try {
                $product = $this->productRepository->get($sku);
                $categories = $product->getCategoryIds();
                $categories[] = $categoryId;
                $checkProduct = $this->isProductInWebsite($product->getId());
                if ($checkProduct) {
                    $sourceItems = $this->getSourceItemsBySku($sku);
                    if (empty($sourceItems)) {
                        $failedSkus[] = ['sku' => $sku, 'error' => 'Product is not assigned to a source.'];
                        continue;
                    }

                    $this->categoryLinkManagement->assignProductToCategories(
                        $sku,
                        $categories
                    );
                }
                $completedSkus++;
            } catch (NoSuchEntityException $e) {
                $failedSkus[] = ['sku' => $sku, 'error' => $e->getMessage()];
            } catch (Exception $e) {
                $hasErrors = true;
                $failedSkus[] = ['sku' => $sku, 'error' => $e->getMessage()];
            }
        }

        if ($hasErrors) {
            return $resultJson->setData(['message' => __('There were errors during the operation.'), 'status' => 'error', 'completedSkus' => $completedSkus, 'failedSkus' => $failedSkus]);
        }

        return $resultJson->setData(['message' => __('Categories have been updated.'), 'status' => 'success', 'completedSkus' => $completedSkus, 'failedSkus' => $failedSkus]);
    }


    /**
     * Get category ID by path.
     *
     * @param $categoryPath
     * @return null
     * @throws LocalizedException|Exception
     */
    public function getCategoryIdByPath($categoryPath)
    {
        $categoryNames = explode('/', $categoryPath);
        $parentId = null;

        foreach ($categoryNames as $categoryName) {
            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToFilter('name', $categoryName);

            if ($parentId) {
                $collection->addAttributeToFilter('parent_id', $parentId);
            }

            $category = $collection->getFirstItem();
            if (!$category->getId()) {
                throw new Exception(__('Category with name %1 does not exist under parent ID %2.', $categoryName, $parentId));
            }

            $parentId = $category->getId();
        }

        return $parentId;
    }

    /**
     * Check if product is associated with any website.
     *
     * @param $productId
     * @return bool
     */
    public function isProductInWebsite($productId): bool
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('catalog_product_website');

        $select = $connection->select()->from($tableName)->where('product_id = ?', $productId);
        $result = $connection->fetchOne($select);

        return !empty($result);
    }

    /**
     * Get source items by SKU.
     *
     * @param string $sku
     * @return array
     */
    public function getSourceItemsBySku(string $sku): array
    {
        try {
            return $this->getSourceItemsBySku->execute($sku);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return [];
    }
}