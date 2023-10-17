<?php
declare(strict_types=1);

namespace DevAll\Short\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Address extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Curl $curl
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Curl $curl,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->curl = $curl;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * Execute the request
     *
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $shortAddress = $this->getRequest()->getParam('shortaddress');

        $apiUrl = $this->scopeConfig->getValue('short_address/api_settings/api_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $apiKey = $this->scopeConfig->getValue('short_address/api_settings/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if ($shortAddress && $apiUrl && $apiKey) {
            $fullApiUrl = $apiUrl . "?format=json&language=e&page=1&encode=utf8&api_key=" . $apiKey . "&shortaddress=" . $shortAddress;
            $this->curl->get($fullApiUrl);
            $response = json_decode($this->curl->getBody(), true);
            return $result->setData($response);
        }

        return $result->setData(['message' => 'No short address provided.']);
    }
}