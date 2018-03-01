<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-zendrouter for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-zendrouter/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Router;

use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Router\Test\IntegrationTest as RouterIntegrationTest;
use Zend\Expressive\Router\ZendRouter;

class IntegrationTest extends RouterIntegrationTest
{
    public function getRouter() : RouterInterface
    {
        return new ZendRouter();
    }
}
