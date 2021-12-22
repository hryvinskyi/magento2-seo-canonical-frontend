<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoCanonicalFrontend\Model;

use Hryvinskyi\SeoCanonicalApi\Api\AddCanonicalLinkInterface;
use Magento\Framework\View\Asset\GroupedCollection;
use Magento\Framework\View\Page\Config;

class AddCanonicalLink implements AddCanonicalLinkInterface
{
    /**
     * @inheritDoc
     */
    public function execute(Config $pageConfig, string $canonicalUrl): void
    {
        $assetCollection = $pageConfig->getAssetCollection();
        $canonicalAsset = $assetCollection->getGroupByContentType('canonical');

        if ($canonicalAsset !== false) {
            $canonicals = $canonicalAsset->getAll();
            $this->removeOldCanonicals($assetCollection, $canonicals);
        }

        $pageConfig->addRemotePageAsset(
            htmlentities($canonicalUrl),
            'canonical',
            ['attributes' => ['rel' => 'canonical']]
        );
    }

    /**
     * @param GroupedCollection $assetCollection
     * @param array $canonicals
     */
    private function removeOldCanonicals(GroupedCollection $assetCollection, array $canonicals): void
    {
        foreach ($canonicals as $canonicalUrl => $value) {
            $assetCollection->remove($canonicalUrl);
        }
    }
}

