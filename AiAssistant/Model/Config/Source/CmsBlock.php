<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 *
 * @author      Bruno Gomes <bgomesweb@gmail.com>
 * @copyright   2025 Bruno Gomes (<https://www.bgomesweb.com.br/>)
 * @license     <https://www.bgomesweb.com.br> Copyright
 * @link        <https://www.bgomesweb.com.br/>
 */

declare(strict_types=1);

namespace Bgomesweb\AiAssistant\Model\Config\Source;

use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\OptionSourceInterface;

class CmsBlock implements OptionSourceInterface
{
    private ?array $options = null;
    private readonly CollectionFactory $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    )
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        if ($this->options === null) {
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('is_active', 1);
            $collection->setOrder('title', Collection::SORT_ORDER_ASC);

            $options = [];
            foreach ($collection as $block) {
                $options[] = [
                    'value' => $block->getIdentifier(),
                    'label' => $block->getTitle() . ' [ID: ' . $block->getId() . ']',
                ];
            }
            $this->options = $options;
        }

        return $this->options;
    }
}
