<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-zendrouter for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-zendrouter/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Router;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Zend\Expressive\Exception;
use Zend\Psr7Bridge\Psr7ServerRequest;
use Zend\Router\Http\TreeRouteStack;
use Zend\Router\RouteMatch;

/**
 * Router implementation that consumes zend-mvc TreeRouteStack.
 *
 * This router implementation consumes the TreeRouteStack from zend-mvc (the
 * default router implementation in a ZF2 application). The addRoute() method
 * injects segment routes into the TreeRouteStack. To manage 405 (Method Not
 * Allowed) errors, we inject a METHOD_NOT_ALLOWED_ROUTE route as a child
 * route, at a priority lower than method-specific routes. If the request
 * matches with this special route, we can send the HTTP allowed methods stored
 * for that path.
 */
class ZendRouter implements RouterInterface
{
    /**
     * Implicitly supported HTTP methods on any route.
     */
    const HTTP_METHODS_IMPLICIT = [
        RequestMethod::METHOD_HEAD,
        RequestMethod::METHOD_OPTIONS,
    ];

    const METHOD_NOT_ALLOWED_ROUTE = 'method_not_allowed';

    /**
     * Store the HTTP methods allowed for each path.
     *
     * @var array
     */
    private $allowedMethodsByPath = [];

    /**
     * Map a named route to a ZF2 route name to use for URI generation.
     *
     * @var array
     */
    private $routeNameMap = [];

    /**
     * @param Route[]
     */
    private $routes = [];

    /**
     * Routes aggregated to inject.
     *
     * @var Route[]
     */
    private $routesToInject = [];

    /**
     * @var TreeRouteStack
     */
    private $zendRouter;

    /**
     * Constructor.
     *
     * Lazy instantiates a TreeRouteStack if none is provided.
     *
     * @param null|TreeRouteStack $router
     */
    public function __construct(TreeRouteStack $router = null)
    {
        if (null === $router) {
            $router = $this->createRouter();
        }

        $this->zendRouter = $router;
    }

    /**
     * @inheritDoc
     */
    public function addRoute(Route $route)
    {
        $this->routesToInject[] = $route;
    }

    /**
     * @inheritDoc
     */
    public function match(PsrRequest $request)
    {
        // Must inject routes prior to matching.
        $this->injectRoutes();

        $match = $this->zendRouter->match(Psr7ServerRequest::toZend($request, true));

        if (null === $match) {
            return RouteResult::fromRouteFailure();
        }

        return $this->marshalSuccessResultFromRouteMatch($match, $request);
    }

    /**
     * {@inheritDoc}
     */
    public function generateUri($name, array $substitutions = [], array $options = [])
    {
        // Must inject routes prior to generating URIs.
        $this->injectRoutes();

        if (! $this->zendRouter->hasRoute($name)) {
            throw new Exception\RuntimeException(sprintf(
                'Cannot generate URI based on route "%s"; route not found',
                $name
            ));
        }

        $name = isset($this->routeNameMap[$name]) ? $this->routeNameMap[$name] : $name;

        $options = array_merge($options, [
            'name'             => $name,
            'only_return_path' => true,
        ]);

        return $this->zendRouter->assemble($substitutions, $options);
    }

    /**
     * @return TreeRouteStack
     */
    private function createRouter()
    {
        return new TreeRouteStack();
    }

    /**
     * Create a successful RouteResult from the given RouteMatch.
     *
     * @param RouteMatch $match
     * @param PsrRequest $request Current HTTP request
     * @return RouteResult
     */
    private function marshalSuccessResultFromRouteMatch(RouteMatch $match, PsrRequest $request)
    {
        $params = $match->getParams();

        if (array_key_exists(self::METHOD_NOT_ALLOWED_ROUTE, $params)) {
            return RouteResult::fromRouteFailure(
                $this->allowedMethodsByPath[$params[self::METHOD_NOT_ALLOWED_ROUTE]]
            );
        }

        $routeName = $this->getMatchedRouteName($match->getMatchedRouteName());

        $route = array_reduce($this->routes, function ($matched, $route) use ($routeName) {
            if ($matched) {
                return $matched;
            }

            // We store the route name already, so we can match on that
            if ($routeName === $route->getName()) {
                return $route;
            }

            return false;
        }, false);

        if (! $route) {
            // This should never happen, as Zend\Expressive\Router\Route always
            // ensures a non-empty route name. Marking as failed route to be
            // consistent with other implementations.
            return RouteResult::fromRouteFailure();
        }

        return RouteResult::fromRoute($route, $params);
    }

    /**
     * Create route configuration for matching one or more HTTP methods.
     *
     * @param Route $route
     * @return array
     */
    private function createHttpMethodRoute($route)
    {
        $methods = array_unique(array_merge($route->getAllowedMethods(), self::HTTP_METHODS_IMPLICIT));
        return [
            'type'    => 'method',
            'options' => [
                'verb'     => implode(',', $methods),
                'defaults' => [
                    'middleware' => $route->getMiddleware(),
                ],
            ],
        ];
    }

    /**
     * Create the configuration for the "method not allowed" route.
     *
     * The specification is used for routes that have HTTP method negotiation;
     * essentially, this is a route that will always match, but *after* the
     * HTTP method route has already failed. By checking for this route later,
     * we can return a 405 response with the allowed methods.
     *
     * @param string $path
     * @return array
     */
    private function createMethodNotAllowedRoute($path)
    {
        return [
            'type'     => 'regex',
            'priority' => -1,
            'options'  => [
                'regex'    => '',
                'defaults' => [
                    self::METHOD_NOT_ALLOWED_ROUTE => $path,
                ],
                'spec' => '',
            ],
        ];
    }

    /**
     * Calculate the route name.
     *
     * Routes will generally match the child HTTP method routes, which will not
     * match the names they were registered with; this method strips the method
     * route name if present.
     *
     * @param string $name
     * @return string
     */
    private function getMatchedRouteName($name)
    {
        // Check for <name>/GET:POST style route names; if so, strip off the
        // child route matching the method.
        if (preg_match('/(?P<name>.+)\/([!#$%&\'*+.^_`\|~0-9a-z-]+:?)+$/i', $name, $matches)) {
            return $matches['name'];
        }

        // Otherwise, just use the name.
        return $name;
    }

    /**
     * Inject any unprocessed routes into the underlying router implementation.
     */
    private function injectRoutes()
    {
        foreach ($this->routesToInject as $index => $route) {
            $this->injectRoute($route);
            $this->routes[] = $route;
            unset($this->routesToInject[$index]);
        }
    }

    /**
     * Inject route into the underlying router implemetation.
     *
     * @param Route $route
     */
    private function injectRoute(Route $route)
    {
        $name    = $route->getName();
        $path    = $route->getPath();
        $options = $route->getOptions();
        $options = array_replace_recursive($options, [
            'route'    => $path,
            'defaults' => [
                'middleware' => $route->getMiddleware(),
            ],
        ]);

        $allowedMethods = $route->getAllowedMethods();
        if (Route::HTTP_METHOD_ANY === $allowedMethods) {
            $this->zendRouter->addRoute($name, [
                'type'    => 'segment',
                'options' => $options,
            ]);
            $this->routeNameMap[$name] = $name;
            return;
        }

        // Remove the middleware from the segment route in favor of method route
        unset($options['defaults']['middleware']);
        if (empty($options['defaults'])) {
            unset($options['defaults']);
        }

        $httpMethodRouteName   = implode(':', $allowedMethods);
        $httpMethodRoute       = $this->createHttpMethodRoute($route);
        $methodNotAllowedRoute = $this->createMethodNotAllowedRoute($path);

        $spec = [
            'type'          => 'segment',
            'options'       => $options,
            'may_terminate' => false,
            'child_routes'  => [
                $httpMethodRouteName           => $httpMethodRoute,
                self::METHOD_NOT_ALLOWED_ROUTE => $methodNotAllowedRoute,
            ]
        ];

        if (array_key_exists($path, $this->allowedMethodsByPath)) {
            $allowedMethods = array_merge($this->allowedMethodsByPath[$path], $allowedMethods);
            // Remove the method not allowed route as it is already present for the path
            unset($spec['child_routes'][self::METHOD_NOT_ALLOWED_ROUTE]);
        }

        $this->zendRouter->addRoute($name, $spec);
        $this->allowedMethodsByPath[$path] = $allowedMethods;
        $this->routeNameMap[$name] = sprintf('%s/%s', $name, $httpMethodRouteName);
    }
}
