<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoCanonicalFrontend\Model;

use Magento\Framework\App\HttpRequestInterface;

interface CanonicalUrlProcessInterface
{
    /**
     * @param HttpRequestInterface $request
     * @return bool
     */
    public function isValid(HttpRequestInterface $request): bool;

    /**
     * Return canonical URL
     *
     * @param HttpRequestInterface $request
     * @return string|null
     */
    public function execute(HttpRequestInterface $request): ?string;
}
