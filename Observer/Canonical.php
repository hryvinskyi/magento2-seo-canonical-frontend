<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoCanonicalFrontend\Observer;

use Hryvinskyi\SeoCanonicalApi\Api\AddCanonicalLinkInterface;
use Hryvinskyi\SeoCanonicalApi\Api\ConfigInterface;
use Hryvinskyi\SeoCanonicalApi\Api\GetCanonicalUrlInterface;
use Magento\Framework\App\HttpRequestInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Page\Config as PageConfig;

class Canonical implements ObserverInterface
{
    /**
     * @var AddCanonicalLinkInterface
     */
    private $addCanonicalLink;

    /**
     * @var GetCanonicalUrlInterface
     */
    private $getCanonicalUrl;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var PageConfig
     */
    private $pageConfig;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var array
     */
    private $systemPages;

    /**
     * @var array
     */
    private $systemRoutes;

    /**
     * @param AddCanonicalLinkInterface $addCanonicalLink
     * @param GetCanonicalUrlInterface $getCanonicalUrl
     * @param ConfigInterface $config
     * @param PageConfig $pageConfig
     * @param RequestInterface $request
     */
    public function __construct(
        AddCanonicalLinkInterface $addCanonicalLink,
        GetCanonicalUrlInterface $getCanonicalUrl,
        ConfigInterface $config,
        PageConfig $pageConfig,
        RequestInterface $request,
        array $systemPages = [],
        array $systemRoutes = []
    ) {
        $this->addCanonicalLink = $addCanonicalLink;
        $this->getCanonicalUrl = $getCanonicalUrl;
        $this->config = $config;
        $this->pageConfig = $pageConfig;
        $this->systemPages = $systemPages;
        $this->systemRoutes = $systemRoutes;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        if (!$this->request instanceof HttpRequestInterface
            || $this->config->isEnabled() === false
            || in_array($this->request->getFullActionName(), $this->systemPages, true)
            || in_array($this->request->getRouteName(), $this->systemRoutes, true)
        ) {
            return;
        }

        if ($canonicalUrl = $this->getCanonicalUrl->execute($this->request)) {
            $this->addCanonicalLink->execute($this->pageConfig, $canonicalUrl);
        }
    }
}
