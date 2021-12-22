<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoCanonicalFrontend\Model;

use Magento\Framework\App\HttpRequestInterface;

abstract class AbstractCanonicalUrlProcess implements CanonicalUrlProcessInterface
{
    /**
     * @var array
     */
    private $actions;

    /**
     * @param array $actions
     */
    public function __construct(array $actions = [])
    {
        $this->actions = $actions;
    }

    /**
     * @inheritDoc
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @param HttpRequestInterface $request
     * @return bool
     */
    public function isValid(HttpRequestInterface $request): bool
    {
        return in_array($request->getFullActionName(), $this->getActions(), true);
    }

    /**
     * @inheritDoc
     */
    public function setActions(array $actions): CanonicalUrlProcessInterface
    {
        $this->actions = $actions;

        return $this;
    }
}
