<?php
namespace Hip\I18nRoutingBundle\Router;

use Symfony\Cmf\Component\Routing\ChainedRouterInterface;
use Symfony\Cmf\Component\Routing\VersatileGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class I18nRouter implements ChainedRouterInterface
{
    /**
     * @var RequestContext
     */
    protected $context;


    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection()
    {
        return new RouteCollection();
    }
	
	/**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        // TODO: Implement generate() method.
    }

	/**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {

        return array (
            '_controller' => 'Hip\I18nRoutingBundle\Controller\RedirectController::redirectAction',
            //'path' => $url,
            //'host' => $host,
            'permanent' => true,
            'scheme' => $this->context->getScheme(),
            'httpPort' => $this->context->getHttpPort(),
            'httpsPort' => $this->context->getHttpsPort(),
            '_route' => ''//$params['_route']
        );
    }
	
	/**
     * {@inheritdoc}
     */
    public function supports($name)
    {
        return true;
    }

	/**
     * {@inheritdoc}
     */
    public function getRouteDebugMessage($name, array $parameters = array())
    {
        return "Route '$name' not found";
    }
}