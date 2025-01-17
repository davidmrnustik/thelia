<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TheliaSmarty\Template\Plugins;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Thelia\Core\Controller\ControllerResolver;
use TheliaSmarty\Template\AbstractSmartyPlugin;
use TheliaSmarty\Template\Exception\SmartyPluginException;
use TheliaSmarty\Template\SmartyPluginDescriptor;

/**
 * Class Render.
 *
 * @author Benjamin Perche <bperche@openstudio.fr>
 */
class Render extends AbstractSmartyPlugin
{
    /** @var ControllerResolver */
    protected $controllerResolver;

    /** @var RequestStack */
    protected $requestStack;

    /** @var Container */
    protected $container;

    /** @var ArgumentResolverInterface */
    protected $argumentResolver;

    public function __construct(
        ControllerResolver $controllerResolver,
        RequestStack $requestStack,
        ContainerInterface $container,
        ArgumentResolverInterface $argumentResolver
    ) {
        $this->controllerResolver = $controllerResolver;
        $this->requestStack = $requestStack;
        $this->container = $container;
        $this->argumentResolver = $argumentResolver;
    }

    /**
     * @param $params
     *
     * @throws SmartyPluginException
     *
     * @return mixed|string
     */
    public function processRender($params)
    {
        if (null === $params['action']) {
            throw new SmartyPluginException(
                "You must declare the 'action' parameter in the 'render' smarty function"
            );
        }

        $request = $this->prepareRequest($params);

        $this->requestStack->push($request);

        $controller = $this->controllerResolver->getController($request);
        $arguments = $this->argumentResolver->getArguments($request, $controller);
        $response = \call_user_func_array($controller, $arguments);

        $this->requestStack->pop();

        if ($response instanceof Response) {
            return $response->getContent();
        }

        return $response;
    }

    protected function prepareRequest(array $params)
    {
        // Get action
        $action = $this->popParameter($params, 'action');

        // Then get and filter query, request and method
        $query = $this->popParameter($params, 'query');
        $query = $this->filterArrayStrParam($query);
        $request = $this->popParameter($params, 'request');
        $request = $this->filterArrayStrParam($request);
        $method = strtoupper($this->popParameter($params, 'method', 'GET'));

        // Then build the request
        $requestObject = clone $this->requestStack->getCurrentRequest();
        $requestObject->query = new ParameterBag($query);
        $requestObject->request = new ParameterBag($request);
        $requestObject->attributes = new ParameterBag(['_controller' => $action]);

        // Apply the method
        if (!empty($request) && 'GET' === $method) {
            $requestObject->setMethod('POST');
        } else {
            $requestObject->setMethod($method);
        }

        // Then all the attribute parameters
        foreach ($params as $key => $attribute) {
            $requestObject->attributes->set($key, $attribute);
        }

        return $requestObject;
    }

    /**
     * @param $param
     *
     * @return array
     *
     * If $param is an array, return it.
     * Else parser it to translate a=b&c=d&e[]=f&g[h]=i to
     * ["a"=>"b","c"=>"d","e"=>["f"],"g"=>["h"=>"i"]
     */
    protected function filterArrayStrParam($param)
    {
        if (\is_array($param)) {
            return $param;
        }

        parse_str($param, $param);

        if (false === $param) {
            return [];
        }

        return $param;
    }

    /**
     * @param $name
     * @param null $default
     *
     * @return mixed
     *
     * Get a parameter then unset it
     */
    protected function popParameter(array $params, $name, $default = null)
    {
        $param = $this->getParam($params, $name, $default);

        if (\array_key_exists($name, $params)) {
            unset($params[$name]);
        }

        return $param;
    }

    /**
     * @return SmartyPluginDescriptor[] an array of SmartyPluginDescriptor
     */
    public function getPluginDescriptors()
    {
        return [
            new SmartyPluginDescriptor('function', 'render', $this, 'processRender'),
        ];
    }
}
