<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoCanonicalFrontend\Model;

use Hryvinskyi\SeoCanonicalApi\Api\CheckIsProductEnabledInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class CheckIsProductEnabled implements CheckIsProductEnabledInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritDoc
     */
    public function executeById(int $productId): bool
    {
        try {
            $product = $this->productRepository->getById(
                $productId,
                false,
                $this->storeManager->getStore()->getId(),
                false
            );
        } catch (NoSuchEntityException $e) {
            return false;
        }

        return $this->checkStatus($product);
    }

    /**
     * @inheritDoc
     */
    public function executeBySku(int $productSku): bool
    {
        try {
            $product = $this->productRepository->get(
                $productSku,
                false,
                $this->storeManager->getStore()->getId(),
                false
            );
        } catch (NoSuchEntityException $e) {
            return false;
        }

        return $this->checkStatus($product);
    }

    /**
     * @param ProductInterface $product
     *
     * @return bool
     */
    private function checkStatus(ProductInterface $product): bool
    {
        return $product->getStatus() === Status::STATUS_ENABLED;
    }
}
