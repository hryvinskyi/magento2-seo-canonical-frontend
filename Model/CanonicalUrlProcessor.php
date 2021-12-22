<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoCanonicalFrontend\Model;

use Hryvinskyi\SeoCanonicalApi\Api\CanonicalUrl\ProcessInterface;
use Magento\Framework\App\HttpRequestInterface;

class CanonicalUrlProcessor
{
    /**
     * @var CanonicalUrlProcessInterface[]
     */
    private $items;

    /**
     * @param array $items
     */
    public function __construct(array $items)
    {
        foreach ($items as $item) {
            if (isset($item['object']) === false) {
                throw new \InvalidArgumentException('The item object empty');
            }
            if (!$item['object'] instanceof CanonicalUrlProcessInterface) {
                throw new \InvalidArgumentException('The item should be implemented of ' . ProcessInterface::class);
            }
        }

        usort($items, static function ($a, $b) {
            if (isset($a['sortOrder']) === false || isset($b['sortOrder']) === false) {
                return true;
            }

            return $a['sortOrder'] <=> $b['sortOrder'];
        });

        $this->items = [];
        foreach ($items as $item) {
            $this->items[] = $item['object'];
        }
    }

    /**
     * @param HttpRequestInterface $request
     * @return string
     */
    public function execute(HttpRequestInterface $request): ?string
    {
        foreach ($this->items as $item) {
            if ($item->isValid($request)) {
                return $item->execute($request);
            }
        }

        return null;
    }
}
