<?php
declare(strict_types=1);

namespace DevAll\SkuCategoryUpdater\Controller\Adminhtml\Index;

use Exception;
use Magento\Backend\App\Action;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Delete extends Action
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
     * @var Run
     */
    private $run;

    /**
     * Class constructor.
     *
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryLinkManagementInterface $categoryLinkManagement
     * @param Run $run
     */
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        CategoryLinkManagementInterface $categoryLinkManagement,
        Run $run
    ) {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->run = $run;
    }

    /**
     * Delete products from category.
     *
     * @return ResponseInterface|Json|(Json&ResultInterface)|ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $skus = $this->getRequest()->getParam('skus');
        $categoryPath = self::DEFAULT_CATEGORY . $this->getRequest()->getParam('category');
        $failedSkus = [];
        $completedSkus = 0;

        foreach ($skus as $sku) {
            try {
                $product = $this->productRepository->get($sku);
                $existingCategories = $product->getCategoryIds();

                $categoryId = $this->run->getCategoryIdByPath($categoryPath);

                if (($key = array_search($categoryId, $existingCategories)) !== false) {
                    unset($existingCategories[$key]);
                    try {
                        $this->categoryLinkManagement->assignProductToCategories($sku, $existingCategories);
                        $completedSkus++;
                    } catch (Exception $e) {
                        $failedSkus[] = ['sku' => $sku, 'error' => $e->getMessage()];
                    }
                }
            } catch (NoSuchEntityException $e) {
                $failedSkus[] = ['sku' => $sku, 'error' => $e->getMessage()];
            } catch (Exception $e) {
                return $resultJson->setData(['message' => __('An error occurred during the operation: %1', $e->getMessage()), 'status' => 'error']);
            }
        }

        return $resultJson->setData(['message' => __('Categories have been updated.'), 'status' => 'success', 'completedSkus' => $completedSkus, 'failedSkus' => $failedSkus]);
    }
}