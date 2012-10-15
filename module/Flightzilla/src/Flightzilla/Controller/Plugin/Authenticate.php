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
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace Flightzilla\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin,
    Zend\Authentication\AuthenticationService,
    Zend\Mvc\Controller\AbstractActionController,
    Flightzilla\Authentication\Adapter;

/**
 * A simple plugin to provide a auth-check
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Authenticate extends AbstractPlugin {

    /**
     * Name of the plugin
     *
     * @var string
     */
    const NAME = 'authenticate';

    /**
     * The cookie-name
     *
     * @var string
     */
    const COOKIE_NAME = '_FLIGHTZILLA_LOGIN';

    /**
     * The authentication adapter
     *
     * @var Adapter
     */
    protected $_authAdapter = null;

    /**
     * The authentication-service
     *
     * @var AuthenticationService
     */
    protected $_authService = null;

    /**
     * Perform the authentication
     *
     * @return $this
     */
    public function performAuthentication() {
        $oAuthAdapter = $this->getAuthAdapter();
        $mParams = array();

        $oRequest = $this->getController()->getRequest();
        if ($oRequest->isPost() === true) {
            $mParams = $oRequest->getPost();
        }

        if (empty($mParams) === true or empty($mParams['username']) === true or empty($mParams['password']) === true) {
            $mParams = $oRequest->getHeader('Cookie');
            if (isset($mParams[self::COOKIE_NAME]) === true) {
                $mParams = $mParams[self::COOKIE_NAME];
            }
            else {
                $mParams = array();
            }
        }
        else {
            $mParams = array(
                'username' => $mParams['username'],
                'password' => $mParams['password']
            );
        }

        $oAuthAdapter->setup($mParams);
        $this->getAuthService()->authenticate($oAuthAdapter);
        return $this;
    }

    /**
     * Check if Identity is present
     *
     * @return bool
     */
    public function hasIdentity() {

        return $this->getAuthService()->hasIdentity();
    }

    /**
     * Return current Identity
     *
     * @return mixed|null
     */
    public function getIdentity() {

        return $this->getAuthService()->getIdentity();
    }

    /**
     * Clear the identity
     *
     * @return void
     */
    public function clearIdentity() {
        setcookie(self::COOKIE_NAME, '', -1);
        $this->getAuthService()->clearIdentity();
    }

    /**
     * Persist the authentication in a cookie
     *
     * @return void
     */
    public function persist() {
        setcookie(self::COOKIE_NAME, $this->getAuthAdapter()->getCrypted(), time() + 604800, $this->getController()->getRequest()->getBaseUrl());
    }

    /**
     * Sets Auth Adapter
     *
     * @param \Flightzilla\Authentication\Adapter $authAdapter
     *
     * @return $this
     */
    public function setAuthAdapter(Adapter $authAdapter) {

        $this->_authAdapter = $authAdapter;

        return $this;
    }

    /**
     * Returns Auth Adapter
     *
     * @return \Flightzilla\Authentication\Adapter
     */
    public function getAuthAdapter() {

        if ($this->_authAdapter === null) {
            $this->setAuthAdapter(new Adapter());
        }

        return $this->_authAdapter;
    }

    /**
     * Sets Auth Service
     *
     * @param \Zend\Authentication\AuthenticationService $authService
     *
     * @return $this
     */
    public function setAuthService(AuthenticationService $authService) {

        $this->_authService = $authService;

        return $this;
    }

    /**
     * Gets Auth Service
     *
     * @return \Zend\Authentication\AuthenticationService
     */
    public function getAuthService() {

        if ($this->_authService === null) {
            $this->setAuthService(new AuthenticationService());
        }

        return $this->_authService;
    }

}
