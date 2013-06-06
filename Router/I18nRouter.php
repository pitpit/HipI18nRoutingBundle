<?php
namespace Hip\I18nRoutingBundle;

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
        // TODO: Implement getRouteCollection() method.
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
        // TODO: Implement match() method.
    }
	
	/**
     * {@inheritdoc}
     */
    public function supports($name)
    {
        // TODO: Implement supports() method.
    }

	/**
     * {@inheritdoc}
     */
    public function getRouteDebugMessage($name, array $parameters = array())
    {
        // TODO: Implement getRouteDebugMessage() method.
    }
}