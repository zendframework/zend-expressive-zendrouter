<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-zendrouter for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-zendrouter/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Router\ZendRouter;

use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Router\ZendRouter;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies() : array
    {
        return [
            'aliases' => [
                RouterInterface::class => ZendRouter::class,
            ],
            'invokables' => [
                ZendRouter::class => ZendRouter::class,
            ],
        ];
    }
}
