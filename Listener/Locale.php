<?php
namespace Hip\I18nRoutingBundle\Listener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class Locale
{
    private $defaultLocale;
    private $availableLocales;

    public function __construct($defaultLocale, $availableLocales){
        $this->defaultLocale = $defaultLocale;
        $this->availableLocales = $availableLocales;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $request = $event->getRequest();
        if ('' !== rtrim($request->getPathInfo(), '/')) {
            return;
        }

        $ex = $event->getException();
        if (!$ex instanceof NotFoundHttpException || !$ex->getPrevious() instanceof ResourceNotFoundException) {
            return;
        }

        $locale = false;

        // if a locale has been specifically set as a query parameter, use it
        if ($request->query->has('hl')) {
            $hostLanguage = $request->query->get('hl');

            if (preg_match('#^[a-z]{2}(?:_[a-z]{2})?$#i', $hostLanguage)) {
                $locale = $hostLanguage;
            }
        }

        // check if a session exists, and if it contains a locale
        if ($locale === false && $request->hasPreviousSession()) {
            $session = $request->getSession();
            if ($session->has('_locale')) {
                $locale = $session->get('_locale');
            }
        }

        // use accept header for locale matching if sent
        if ($locale === false && $languages = $request->getLanguages()) {
            foreach ($languages as $lang) {
                if (in_array($lang, $this->availableLocales, true)) {
                    $locale = $lang;
                    break;
                }
            }
        }

        if ($locale === false) {
            $locale = $this->defaultLocale;
        }

        $request->setLocale($locale);

        $params = $request->query->all();
        unset($params['hl']);

        $event->setResponse(new RedirectResponse($request->getBaseUrl().'/'.$locale.'/'.($params ? '?'.http_build_query($params) : '')));
    }
}