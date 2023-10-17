<?php
declare(strict_types=1);

namespace DevAll\Short\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class SaveAddress extends Action
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * Class constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Execute the request.
     *
     * @return ResponseInterface|Json|ResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $response = ['success' => false];
        $region = $this->getRequest()->getParam('region');
        $regionCode = $this->getRequest()->getParam('region_code');

        $quote = $this->checkoutSession->getQuote();
        if ($quote && $quote->getId()) {
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setRegion($region);
            $shippingAddress->setRegionCode($regionCode);
            $shippingAddress->save();

            $response['success'] = true;
        }
        return $this->resultJsonFactory->create()->setData($response);
    }

}
