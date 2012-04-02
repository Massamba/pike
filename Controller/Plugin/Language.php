<?php
/**
 * Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */

/**
 * Language controller plugin
 *
 * To use this front controller plugin add the following lines to your application.ini:
 * autoloaderNamespaces[] = "Pike"
 * resources.frontController.plugins.Language = "Pike_Controller_Plugin_Language"
 * 
 * Make sure Zend_Translate and Zend_Locale are setup first!
 * 
 * Then add routes in your application.ini like:
 * 
 * resources.router.routes.defaultModule.type = Zend_Controller_Router_Route_Module
 * ; If abstract is set to On, this route will be unusable outside a chain route and forces
 * ; every URI to have a language prefix. This also avoids duplicate content.
 * resources.router.routes.defaultModule.abstract = On
 * resources.router.routes.defaultModule.defaults.module = "default"
 * resources.router.routes.defaultModule.defaults.controller = "index"
 * resources.router.routes.defaultModule.defaults.action = "index"
 * 
 * resources.router.routes.language.type = Zend_Controller_Router_Route
 * resources.router.routes.language.route = ":language"
 * resources.router.routes.language.reqs.language = "^(de|en|fr|nl)$"
 * resources.router.routes.language.defaults.language = "en"
 * resources.router.routes.language.defaults.module = "default"
 * resources.router.routes.language.defaults.controller = "index"
 * resources.router.routes.language.defaults.action = "index"
 * 
 * resources.router.routes.default.type = Zend_Controller_Router_Route_Chain
 * resources.router.routes.default.chain = "language,defaultModule"
 * 
 * resources.router.routes.sitemap.type = Zend_Controller_Router_Route_Static
 * resources.router.routes.sitemap.route = "sitemap"
 * resources.router.routes.sitemap.defaults.module = "default"
 * resources.router.routes.sitemap.defaults.controller = "sitemap"
 * resources.router.routes.sitemap.defaults.action = "index"
 * 
 * resources.router.routes.newsShowAbstract.type = Zend_Controller_Router_Route
 * resources.router.routes.newsShowAbstract.abstract = On
 * resources.router.routes.newsShowAbstract.route = "news/:article"
 * resources.router.routes.newsShowAbstract.defaults.module = "default"
 * resources.router.routes.newsShowAbstract.defaults.controller = "news"
 * resources.router.routes.newsShowAbstract.defaults.action = "show"
 * 
 * resources.router.routes.newsShow.type = Zend_Controller_Router_Route_Chain
 * resources.router.routes.newsShow.chain = "language,newsShowAbstract"
 * 
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_Controller_Plugin_Language extends Zend_Controller_Plugin_Abstract
{
    /**
     * Called before Zend_Controller_Front begins evaluating the request against its routes.
     * 
     * @param Zend_Controller_Request_Abstract $request 
     */
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        /**
         * Check if no language information is available in the request URI because
         * the base URL is entered.
         */
        if (substr($request->getRequestUri(), 0, -1) == $request->getBaseUrl()) {
            $translate = Zend_Registry::get('Zend_Translate');
            
            // Get current locale language (autodetected if "auto" is used as config value)
            $language = Zend_Registry::get("Zend_Locale")->getLanguage();

            // Set the default language if the preferred language is not available
            if (!$translate->isAvailable($language)) {
                Zend_Registry::set("Zend_Locale", new Zend_Locale('default'));

                // Get default language
                $language = Zend_Registry::get("Zend_Locale")->getLanguage();

                // Set new locale in the translator
                $translate->setLocale(Zend_Registry::get('Zend_Locale'));
            }

            $request->setRequestUri($request->getRequestUri() . $language . '/');
            $request->setParam("language", $language);
            
            // Support the translation of routes
            Zend_Controller_Router_Route::setDefaultTranslator($translate);
        }
    }

    /**
     * Called after Zend_Controller_Router exits.
     * Called after Zend_Controller_Front exits from the router.
     * 
     * @param  Zend_Controller_Request_Abstract $request
     * @throws Zend_Controller_Router_Exception 
     */
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        // Get language from request param
        $language = $request->getParam("language");

        // Get default locale
        $locale = Zend_Registry::get('Zend_Locale');
        $defaultLocale = current(array_keys($locale::getDefault()));
        
        // Set default language if not available
        if (null === $language) {
            $language = $defaultLocale;
        }
        
        // Throw an exception if the current language is not available
        // and is also not the default language.
        if (!Zend_Registry::get('Zend_Translate')->isAvailable($language)
            && $language != $defaultLocale
        ) {
            throw new Zend_Controller_Router_Exception('Translation language is not available', 404);
        }

        // Set the locale
        Zend_Registry::set("Zend_Locale", new Zend_Locale($language));

        // Set new locale in the translator
        Zend_Registry::get("Zend_Translate")->setLocale(Zend_Registry::get("Zend_Locale"));
        
        // Set the correct language in navigation links etc.
        $router = Zend_Controller_Front::getInstance()->getRouter();
        $router->setGlobalParam('language', $language);
    }
}