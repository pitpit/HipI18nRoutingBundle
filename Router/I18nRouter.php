<?php
namespace Hip\I18nRoutingBundle\Router;

use Symfony\Cmf\Component\Routing\ChainedRouterInterface;
use Symfony\Cmf\Component\Routing\VersatileGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\ConfigurableRequirementsInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Config\ConfigCache;
use Psr\Log\LoggerInterface;


class I18nRouter implements ChainedRouterInterface
{
    const ROUTING_PREFIX = '__HIP__';

    /**
     * @var RequestContext
     */
    protected $context;

    /**
     * @var UrlMatcherInterface|null
     */
    protected $matcher;

    /**
     * @var UrlGeneratorInterface|null
     */
    protected $generator;

    /**
     * @var RouteCollection|null
     */
    protected $collection;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var mixed
     */
    protected $defaultLocale;

    /**
     * @var mixed
     */
    protected $resource;

    /**
     * @var null|\Psr\Log\LoggerInterface
     */
    protected $logger;


    public function __construct($container, $resource, LoggerInterface $logger = null, RequestContext $context = null, array $options = array())
    {
        $this->setOptions($options);
        $this->context = null === $context ? new RequestContext() : $context;
        $this->container = $container;
        $this->resource = $resource;
        $this->logger = $logger;
    }


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
        static $i18nCollection;

        if($i18nCollection instanceof RouteCollection === false) {
            if (null === $this->collection) {
                $this->collection = $this->container->get('routing.loader')->load($this->resource, $this->options['resource_type']);
            }

            $i18nCollection = new RouteCollection();
            foreach ($this->collection->getResources() as $resource) {
                $i18nCollection->addResource($resource);
            }

            foreach ($this->collection->all() as $name => $route) {

                //do not add i18n routing prefix
                if ($this->shouldExcludeRoute($name, $route)) {
                    $i18nCollection->add($name, $route);
                    continue;
                }

                //add i18n routing prefix
                foreach ($this->generateI18nPatterns($name, $route) as $pattern => $locales) {
                    foreach ($locales as $locale) {
                        $localeRoute = clone $route;
                        $localeRoute->setPath($pattern);
                        $localeRoute->setDefault('_locale', $locale);
                        $i18nCollection->add($locale.self::ROUTING_PREFIX.$name, $localeRoute);
                    }
                }
            }
        }

        return $i18nCollection;
    }

    private function shouldExcludeRoute($routeName, Route $route)
    {
        if ('_' === $routeName[0]) {
            return true;
        }

        if (false === $route->getOption('i18n')) {
            return true;
        }

        return false;
    }

    private function generateI18nPatterns($routeName, Route $route)
    {
        $patterns = array();
        foreach($this->container->getParameter('hip_i18n_routing.locales') as $locale) {

            $i18nPattern = $route->getPath();

            //do not add prefix for default locale
            if($this->container->getParameter('hip_i18n_routing.default_locale') !== $locale) {
                $i18nPattern = '/'.$locale;
                if($route->getPath() != '/') {
                    $i18nPattern .= $route->getPath();
                }
            }

            $patterns[$i18nPattern][] = $locale;
        }

        return $patterns;
    }

    private function getLocale()
    {
        // determine the most suitable locale to use for route generation
        $currentLocale = $this->context->getParameter('_locale');
        if (isset($parameters['_locale'])) {
            return $parameters['_locale'];
        } elseif ($currentLocale) {
            return $currentLocale;
        } else {
            return $this->defaultLocale;
        }
    }
	
	/**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        $locale = $this->getLocale();
        $generator = $this->getGenerator();
        try {
            $url = $generator->generate($locale.self::ROUTING_PREFIX.$name, $parameters, $referenceType);

            return $url;
        } catch (RouteNotFoundException $ex) {
            // fallback to default behavior
        }

        // use the default behavior if no localized route exists
        return $generator->generate($name, $parameters);
    }


	/**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        $params = $this->getMatcher()->match($pathinfo);

        if (false === $params) {
            return false;
        }

        if (isset($params['_locale']) && 0 < $pos = strpos($params['_route'], self::ROUTING_PREFIX)) {
            $params['_route'] = substr($params['_route'], $pos + strlen(self::ROUTING_PREFIX));
        }

        return $params;
    }
	
	/**
     * {@inheritdoc}
     */
    public function supports($name)
    {
        return (strpos($name, self::ROUTING_PREFIX) !== false || $this->getRouteCollection()->get($this->getLocale().self::ROUTING_PREFIX.$name) !== null);
    }

	/**
     * {@inheritdoc}
     */
    public function getRouteDebugMessage($name, array $parameters = array())
    {
        return "Route '$name' not found";
    }





    /**
     * Gets the UrlGenerator instance associated with this Router.
     *
     * @return UrlGeneratorInterface A UrlGeneratorInterface instance
     */
    public function getGenerator()
    {
        if (null !== $this->generator) {
            return $this->generator;
        }

        if (null === $this->options['cache_dir'] || null === $this->options['generator_cache_class']) {
            $this->generator = new $this->options['generator_class']($this->getRouteCollection(), $this->context, $this->logger);
        } else {
            $class = $this->options['generator_cache_class'];
            $cache = new ConfigCache($this->options['cache_dir'].'/'.$class.'.php', $this->options['debug']);
            if (!$cache->isFresh($class)) {
                $dumper = new $this->options['generator_dumper_class']($this->getRouteCollection());

                $options = array(
                    'class' => $class,
                    'base_class' => $this->options['generator_base_class'],
                );

                $cache->write($dumper->dump($options), $this->getRouteCollection()->getResources());
            }

            require_once $cache;

            $this->generator = new $class($this->context, $this->logger);
        }

        if ($this->generator instanceof ConfigurableRequirementsInterface) {
            $this->generator->setStrictRequirements($this->options['strict_requirements']);
        }

        return $this->generator;
    }


    /**
     * Gets the UrlMatcher instance associated with this Router.
     *
     * @return UrlMatcherInterface A UrlMatcherInterface instance
     */
    public function getMatcher()
    {
        if (null !== $this->matcher) {
            return $this->matcher;
        }

        if (null === $this->options['cache_dir'] || null === $this->options['matcher_cache_class']) {
            return $this->matcher = new $this->options['matcher_class']($this->getRouteCollection(), $this->context);
        }

        $class = $this->options['matcher_cache_class'];
        $cache = new ConfigCache($this->options['cache_dir'].'/'.$class.'.php', $this->options['debug']);
        if (!$cache->isFresh($class)) {
            $dumper = new $this->options['matcher_dumper_class']($this->getRouteCollection());

            $options = array(
                'class' => $class,
                'base_class' => $this->options['matcher_base_class'],
            );

            $cache->write($dumper->dump($options), $this->getRouteCollection()->getResources());
        }

        require_once $cache;

        return $this->matcher = new $class($this->context);
    }


    /**
     * Sets options.
     *
     * Available options:
     *
     * * cache_dir: The cache directory (or null to disable caching)
     * * debug: Whether to enable debugging or not (false by default)
     * * resource_type: Type hint for the main resource (optional)
     *
     * @param array $options An array of options
     *
     * @throws \InvalidArgumentException When unsupported option is provided
     */
    public function setOptions(array $options)
    {
        $this->options = array(
            'cache_dir' => null,
            'debug' => false,
            'generator_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator',
            'generator_base_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator',
            'generator_dumper_class' => 'Symfony\\Component\\Routing\\Generator\\Dumper\\PhpGeneratorDumper',
            'generator_cache_class' => 'ProjectUrlGenerator',
            'matcher_class' => 'Symfony\\Component\\Routing\\Matcher\\UrlMatcher',
            'matcher_base_class' => 'Symfony\\Component\\Routing\\Matcher\\UrlMatcher',
            'matcher_dumper_class' => 'Symfony\\Component\\Routing\\Matcher\\Dumper\\PhpMatcherDumper',
            'matcher_cache_class' => 'ProjectUrlMatcher',
            'resource_type' => null,
            'strict_requirements' => true,
        );

        // check option names and live merge, if errors are encountered Exception will be thrown
        $invalid = array();
        foreach ($options as $key => $value) {
            if (array_key_exists($key, $this->options)) {
                $this->options[$key] = $value;
            } else {
                $invalid[] = $key;
            }
        }

        if ($invalid) {
            throw new \InvalidArgumentException(sprintf('The Router does not support the following options: "%s".', implode('", "', $invalid)));
        }
    }
}
