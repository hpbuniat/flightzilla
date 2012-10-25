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
namespace Flightzilla\Model\Ticket;


/**
 * Abstract for Ticket-Sources
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
abstract class AbstractSource {
    /**
     * The cache-instance
     *
     * @var \Zend\Cache\Storage\StorageInterface
     */
    protected $_oCache = null;

    /**
     * The auth-instance
     *
     * @var \Flightzilla\Authentication\Adapter
     */
    protected $_oAuth = null;

    /**
     * The cookie-path
     *
     * @var string
     */
    protected $_sCookie = null;

    /**
     * The configuration
     *
     * @var \Zend\Config\Config
     */
    protected $_config = null;

    /**
     * The http-client
     *
     * @var \Zend\Http\Client
     */
    protected $_client = null;

    /**
     * Set the cache
     *
     * @param  \Zend\Cache\Storage\StorageInterface $oCache
     *
     * @return $this
     */
    public function setCache(\Zend\Cache\Storage\StorageInterface $oCache) {
        $this->_oCache = $oCache;
        return $this;
    }

    /**
     * Set the auth-adapter
     *
     * @param  \Flightzilla\Authentication\Adapter $oAuth
     *
     * @return $this
     */
    public function setAuth(\Flightzilla\Authentication\Adapter $oAuth) {
        $this->_oAuth = $oAuth;
        return $this;
    }

    /**
     * Init the curl-client
     *
     * @return $this
     */
    public function initHttpClient() {
        $this->_sCookie = sprintf('%sflightzilla%s', $this->_config->bugzilla->http->cookiePath, md5($this->_oAuth->getLogin()));

        $aCurlOptions = array(
            CURLOPT_COOKIEFILE => $this->_sCookie,
            CURLOPT_COOKIEJAR => $this->_sCookie,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false
        );

        if (isset($this->_config->bugzilla->http->proxy) === true) {
            $aCurlOptions[CURLOPT_PROXY] = $this->_config->bugzilla->http->proxy;
        }

        $this->_client->setOptions(array(
            'timeout' => 30,
            'adapter' => 'Zend\Http\Client\Adapter\Curl',
            'curloptions' => $aCurlOptions
        ));

        return $this;
    }
}
