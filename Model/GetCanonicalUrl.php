<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoCanonicalFrontend\Model;

use Hryvinskyi\SeoApi\Api\CheckPatternInterface;
use Hryvinskyi\SeoApi\Api\GetBaseUrlInterface;
use Hryvinskyi\SeoCanonicalApi\Api\ConfigInterface;
use Hryvinskyi\SeoCanonicalApi\Api\DeleteDoubleSlashInterface;
use Hryvinskyi\SeoCanonicalApi\Api\GetCanonicalUrlInterface;
use Magento\Framework\App\HttpRequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class GetCanonicalUrl implements GetCanonicalUrlInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var CheckPatternInterface
     */
    private $checkPattern;

    /**
     * @var GetBaseUrlInterface
     */
    private $getBaseUrl;

    /**
     * @var CanonicalUrlProcessor
     */
    private $canonicalUrlProcessor;


    /**
     * @var DeleteDoubleSlashInterface
     */
    private $deleteDoubleSlash;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ConfigInterface $config
     * @param CheckPatternInterface $checkPattern
     * @param GetBaseUrlInterface $getBaseUrl
     * @param CanonicalUrlProcessor $canonicalUrlProcessor
     * @param DeleteDoubleSlashInterface $deleteDoubleSlash
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ConfigInterface $config,
        CheckPatternInterface $checkPattern,
        GetBaseUrlInterface $getBaseUrl,
        CanonicalUrlProcessor $canonicalUrlProcessor,
        DeleteDoubleSlashInterface $deleteDoubleSlash,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->checkPattern = $checkPattern;
        $this->getBaseUrl = $getBaseUrl;
        $this->deleteDoubleSlash = $deleteDoubleSlash;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->canonicalUrlProcessor = $canonicalUrlProcessor;
    }

    /**
     * @inheritDoc
     */
    public function execute(HttpRequestInterface $request): ?string
    {
        if ($this->config->isEnabled() === false || $this->isIgnoredCanonical($request) === true) {
            return null;
        }

        try {
            return $this->processCanonical($request);
        } /** @noinspection PhpMultipleClassDeclarationsInspection */
        catch (Throwable $e) {
            $this->logger->critical($e->getMessage() . ' | ' . $request->getFullActionName());
            return null;
        }
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function processCanonical(HttpRequestInterface $request): ?string
    {
        $canonicalUrl = $this->canonicalUrlProcessor->execute($request);

        if ($canonicalUrl === null) {
            return null;
        }

        if ($this->config->isCanonicalStoreWithoutStoreCode($this->storeManager->getStore()->getId())) {
            $storeCode = $this->storeManager->getStore()->getCode();
            $canonicalUrl = str_replace('/' . $storeCode . '/', '/', $canonicalUrl);
        } elseif ($crossDomainStore = $this->config->getCrossdomain($this->storeManager->getStore()->getId())) {
            $mainBaseUrl = $this->storeManager->getStore($crossDomainStore)->getBaseUrl();
            $currentBaseUrl = $this->storeManager->getStore()->getBaseUrl();
            $canonicalUrl = str_replace($currentBaseUrl, $mainBaseUrl, $canonicalUrl);

            $mainSecureBaseUrl = $this->storeManager->getStore($crossDomainStore)
                ->getBaseUrl(UrlInterface::URL_TYPE_WEB, true);

            if ($this->storeManager->getStore()->isCurrentlySecure()
                || ($this->config->isCrossdomainPreferHttps()
                    && strpos($mainSecureBaseUrl, 'https://') !== false)
            ) {
                $canonicalUrl = str_replace('http://', 'https://', $canonicalUrl);
            }
        }

        if ($this->isEndsWith($canonicalUrl, '/index.php')) {
            $canonicalUrl = substr($canonicalUrl, 0, strlen($canonicalUrl) - strlen('index.php'));
        }

        $canonicalUrl = $this->deleteDoubleSlash->execute($canonicalUrl);

        $page = (int)$request->getParam('p');

        if ($page > 1 && $this->config->isPaginatedCanonical()) {
            $canonicalUrl .= "?p=$page";
        }

        return $canonicalUrl;
    }

    /**
     * @param HttpRequestInterface $request
     *
     * @return bool
     */
    private function isIgnoredCanonical(HttpRequestInterface $request): bool
    {
        if (empty($request->getModuleName()) || empty($request->getControllerName())
            || empty($request->getActionName())
        ) {
            return true;
        }

        $pages = array_map('trim', explode("\n", $this->config->getIgnoreUrls()));

        foreach ($pages as $page) {
            if ($this->checkPattern->execute($request->getFullActionName(), $page)
                || $this->checkPattern->execute($this->getBaseUrl->execute(), $page)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    private function isEndsWith(string $haystack, string $needle): bool
    {
        $length = strlen($needle);
        if ($length === 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }
}
