<?php
declare(strict_types=1);

namespace DevAll\SkuCategoryUpdater\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Separator implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '1', 'label' => __('Comma (,)')],
            ['value' => '2', 'label' => __('New Line')]
        ];
    }
}