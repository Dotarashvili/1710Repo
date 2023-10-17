<?php
declare(strict_types=1);

namespace DevAll\SkuCategoryUpdater\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class DeleteButton extends Field
{
    const BUTTON_TEMPLATE = 'system/config/delete.phtml';

    /**
     * Set template to itself
     *
     * @return $this
     */
    protected function _prepareLayout(): DeleteButton
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::BUTTON_TEMPLATE);
        }
        return $this;
    }

    /**
     * Render button
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $this->addData(
            [
                'button_label' => 'Delete Products',
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $this->_urlBuilder->getUrl('skucategoryupdater/index/delete'),
            ]
        );
        return $this->_toHtml();
    }
}
