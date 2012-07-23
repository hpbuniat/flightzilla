<?php
/**
 * flightzilla
 *
 * Copyright (c)2012, Hans-Peter Buniat <hpbuniat@googlemail.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in
 * the documentation and/or other materials provided with the
 * distribution.
 *
 * * Neither the name of Hans-Peter Buniat nor the names of his
 * contributors may be used to endorse or promote products derived
 * from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
    * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
    * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package flightzilla
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */

/**
 * Bootstrap the application
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    /**
     * The config
     *
     * @var Zend_Config
     */
    protected $_config = null;

    /**
     * Load the config
     */
    protected function _initConfig() {
        $this->_config = new Zend_Config($this->getOptions(), true);
        Zend_Registry::set('_Config', $this->_config);
    }

    /**
     * Load the Cache
     */
    protected function _initDataCache() {
        if (!$this->hasPluginResource('cachemanager')) {
            return;
        }

        $dataCache = $this->getPluginResource('cachemanager')->getCacheManager()->getCache('bugzilla');
        if (!$dataCache) {
            return;
        }

        Zend_Registry::set('_Cache', $dataCache);
    }

    /**
     * Load the logger
     */
    protected function _initLogger() {
        // wenn cachemanager nicht gelade
        if (!$this->hasPluginResource('log')) {
            return;
        }

        $logger = $this->getPluginResource('Log')->getLog();
        Zend_Registry::set('_Logger', $logger);
    }

    /**
     * Init authentication
     */
    protected function _initAuth() {
        if (isset($_COOKIE['_BUGZILLA_LOGIN']) === true) {
            $oAuth = new Model_Auth($_COOKIE['_BUGZILLA_LOGIN']);
            Zend_Auth::getInstance()->authenticate($oAuth);

            Zend_Registry::set('_Auth', $oAuth);
        }
    }

    /**
     * Init the view with some vars
     */
    protected function _initViewvars() {
        if (!$this->hasPluginResource('view')) {
            return;
        }

        $oView = $this->getPluginResource('view')->getView();
        $oView->sBugzilla = $this->_config->model->bugzilla->baseUrl;
        $oView->sName = $this->_config->app->name;
    }
}

