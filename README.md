i18n-routing-bundle
===================
inspired by<br />
https://github.com/schmittjoh/JMSI18nRoutingBundle<br />
and<br />
https://github.com/sonata-project/SonataPageBundle
<br /><br />
This bundle makes it possible to use symfony-cmf/routing <a href="http://symfony.com/doc/master/cmf/components/routing.html#chainrouter">chained routing</a> on your i18n routing.<br />
<br />
here is a configuration example:<br />
<br />
hip_i18n_routing:<br />
    default_locale: 'de'<br />
    locales: ['de','en','fr','es']<br />
<br />
cmf_routing:<br />
    chain:<br />
        routers_by_id:<br />
            hip_i18n.router: 300<br />
            sonata.page.router: 200<br />
            router.default: 100<br />