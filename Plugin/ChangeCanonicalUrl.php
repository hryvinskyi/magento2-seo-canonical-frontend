<?php
/**
 * Copyright (c) 2021-2025. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoCanonicalFrontend\Plugin;

use Hryvinskyi\SeoCanonicalApi\Api\AddCanonicalLinkInterface;
use Hryvinskyi\SeoCanonicalApi\Api\ConfigInterface;
use Hryvinskyi\SeoCanonicalApi\Api\GetCanonicalUrlInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\HttpRequestInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\View\Result\Page;

class ChangeCanonicalUrl
{
    private AddCanonicalLinkInterface $addCanonicalLink;
    private GetCanonicalUrlInterface $getCanonicalUrl;
    private ConfigInterface $config;
    private RequestInterface $request;
    private EventManagerInterface $eventManager;
    private array $systemPages;
    private array $systemRoutes;

    public function __construct(
        AddCanonicalLinkInterface $addCanonicalLink,
        GetCanonicalUrlInterface $getCanonicalUrl,
        ConfigInterface $config,
        RequestInterface $request,
        EventManagerInterface $eventManager,
        array $systemPages = [],
        array $systemRoutes = []
    ) {
        $this->addCanonicalLink = $addCanonicalLink;
        $this->getCanonicalUrl = $getCanonicalUrl;
        $this->config = $config;
        $this->request = $request;
        $this->eventManager = $eventManager;
        $this->systemPages = $systemPages;
        $this->systemRoutes = $systemRoutes;
    }

    /**
     * Add canonical URL to page config after controller execution
     *
     * @param ActionInterface $action
     * @param mixed $result
     * @return mixed
     */
    public function afterExecute(ActionInterface $action, $result)
    {
        if (!$this->shouldAddCanonicalUrl($action, $result)) {
            return $result;
        }

        $canonicalUrl = $this->getCanonicalUrl->execute($this->request);
        if ($canonicalUrl) {
            $this->addCanonicalLink->execute($result->getConfig(), $canonicalUrl);
        }

        return $result;
    }


    /**
     * Check if canonical URL should be added to the page
     *
     * @param ActionInterface $action
     * @param mixed $result
     * @return bool
     */
    private function shouldAddCanonicalUrl(ActionInterface $action, $result): bool
    {
        $shouldAdd = true;

        if (!$result instanceof Page) {
            $shouldAdd = false;
        }

        if ($shouldAdd && !$this->request instanceof HttpRequestInterface) {
            $shouldAdd = false;
        }

        if ($shouldAdd && !$this->config->isEnabled()) {
            $shouldAdd = false;
        }

        if ($shouldAdd && in_array($this->request->getFullActionName(), $this->systemPages, true)) {
            $shouldAdd = false;
        }

        if ($shouldAdd && in_array($this->request->getRouteName(), $this->systemRoutes, true)) {
            $shouldAdd = false;
        }

        // Allow other modules to modify the decision through events
        $transportObject = new DataObject([
            'should_add_canonical' => $shouldAdd,
            'result' => $result,
            'action' => $action
        ]);

        $this->eventManager->dispatch(
            'hryvinskyi_seo_canonical_should_add_canonical',
            ['transport' => $transportObject]
        );

        return (bool)$transportObject->getData('should_add_canonical');
    }
}
