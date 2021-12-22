<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoCanonicalFrontend\Model;

use Hryvinskyi\SeoApi\Api\GetBaseUrlInterface;
use Hryvinskyi\SeoCanonicalApi\Api\ConfigInterface;
use Magento\Framework\App\HttpRequestInterface;
use Magento\Framework\UrlInterface;

class DefaultCanonicalUrlProcess implements CanonicalUrlProcessInterface
{
    /**
     * @var GetBaseUrlInterface
     */
    private $getBaseUrl;

    /**
     * @var ConfigInterface
     */
    private $config;


    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param GetBaseUrlInterface $getBaseUrl
     * @param ConfigInterface $config
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        GetBaseUrlInterface $getBaseUrl,
        ConfigInterface $config,
        UrlInterface $urlBuilder
    ) {
        $this->getBaseUrl = $getBaseUrl;
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @inheritDoc
     */
    public function isValid(HttpRequestInterface $request): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function execute(HttpRequestInterface $request): ?string
    {
        $canonicalUrl = $this->getBaseUrl->execute();
        $preparedCanonicalUrlParam = ($this->config->isAddStoreCodeToUrlsEnabled()
            && $request->getFullActionName() === 'cms_index_index') ? '' : ltrim($canonicalUrl, '/');
        $canonicalUrl = $this->urlBuilder->getUrl('', ['_direct' => $preparedCanonicalUrlParam]);
        return strtok($canonicalUrl, '?');
    }
}
