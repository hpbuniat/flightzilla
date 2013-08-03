<?php
/**
 * flightzilla
 *
 * Copyright (c) 2012-2013, Hans-Peter Buniat <hpbuniat@googlemail.com>.
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
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace Flightzilla\Model\Analytics;

/**
 * Query Google-Analytics
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Service {

    /**
     * Modifier for _fetch to select only paid-traffic
     *
     * @var boolean
     */
    const ONLY_PAID_TRAFFIC = true;

    /**
     * Modifier for _fetch to select all traffic
     *
     * @var boolean
     */
    const ALL_TRAFFIC = false;

    /**
     * Number of days in the past to compare
     *
     * @var int
     */
    const NUMBER_OF_DAYS = 7;

    /**
     * Number of weeks to compare with each day
     *
     * @var int
     */
    const NUMBER_OF_WEEKS = 4;

    /**
     * The authenticated http-client
     *
     * @var \Zend\Http\Client
     */
    protected $_oHttp = null;

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
     * Is the http-client logged in?
     *
     * @var boolean
     */
    protected $_bLogin = false;

    /**
     * Create the analytics-model
     *
     * @param \ZendGData\HttpClient $oHttpClient
     * @param \Zend\Config\Config $oConfig
     */
    public function __construct(\ZendGData\HttpClient $oHttpClient, \Zend\Config\Config $oConfig) {
        $this->_config = $oConfig->analytics;
        $this->_oHttp = $oHttpClient;
    }

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
     * Get the cache
     *
     * @return \Zend\Cache\Storage\StorageInterface
     */
    public function getCache() {
        return $this->_oCache;
    }

    /**
     * Get the config
     *
     * @return \Zend\Config\Config
     */
    public function getConfig() {
        return $this->_config;
    }

    /**
     * Get the http-client
     *
     * @return \ZendGData\HttpClient
     */
    public function getHttpClient() {
        return $this->_oHttp;
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
     * Get the auth-component
     *
     * @return \Flightzilla\Authentication\Adapter
     */
    public function getAuth() {
        return $this->_oAuth;
    }

    /**
     * Perform login to analytics
     *
     * @return $this
     */
    public function login() {
        if (empty($this->_config->password) !== true and $this->_bLogin === false) {
            $this->_oHttp = \ZendGData\ClientLogin::getHttpClient(
                $this->_config->login,
                $this->_oAuth->decrypt($this->getCipherKey(), $this->_config->password),
                \ZendGData\Analytics::AUTH_SERVICE_NAME,
                $this->_oHttp
            );

            $this->_bLogin = true;
        }

        return $this;
    }

    /**
     * Get the cipher key, to decipher the login
     *
     * @return string
     */
    public function getCipherKey() {
        return md5($this->_config->login . $this->_oAuth->getLogin() . $this->_oAuth->getPassword());
    }

    /**
     * Get the config of the portal
     *
     * @param  string $sPortal
     *
     * @return \Zend\Config\Config
     *
     * @throws \InvalidArgumentException
     */
    public function getPortalInfo($sPortal) {
        foreach ($this->_config->portal as $oPortal) {
            if ($oPortal->name === $sPortal) {
                return $oPortal;
            }
        }

        throw new \InvalidArgumentException('unknown portal');
    }

    /**
     * Transform the data to a series display
     *
     * @param  array $aData
     *
     * @return array
     */
    public function getSeries($aData) {
        $aSeries = array(
            0 => array(
                'name' => 'This week',
                'data' => array()
            )
        );
        for ($iWeek = 1; $iWeek <= self::NUMBER_OF_WEEKS; $iWeek++) {
            $aSeries[$iWeek] = array(
                'name' => sprintf('%d week(s) before', $iWeek),
                'data' => array()
            );
        }

        foreach ($aData as $sDate => $aDateData) {
            $aSeries[0]['data'][] = $aDateData['base']['total']['conversion'];
            $i = 1;
            foreach ($aDateData['compare'] as $aCompare) {
                $aSeries[$i++]['data'][] = $aCompare['total']['conversion'];
            }
        }

        $aSeries[] = $this->_getAverage($aSeries);
        return $aSeries;
    }

    /**
     * Get the average of a series
     *
     * @param  array $aSeries
     *
     * @return array
     */
    protected function _getAverage($aSeries) {
        $aAverage = array(
            'type' => 'spline',
            'marker' => array(
                'enabled' => false,
            ),
            'dashStyle' => 'shortdot',
            'name' => 'Average',
            'data' => array()
        );

        $iCount = count($aSeries);
        $iValues = count($aSeries[0]['data']);
        for ($i=0; $i < $iValues; $i++) {
            $fSum = 0;
            foreach ($aSeries as $iNumber => $aData) {
                $fSum += $aData['data'][$i];
            }

            $aAverage['data'][] = round($fSum/$iCount,2);
        }

        return $aAverage;
    }

    /**
     * Return all portals
     *
     * @return string
     */
    public function getPortals() {
        $aPortals = array();
        foreach ($this->_config->portal as $oPortal) {
            $aPortals[$oPortal->id] = $oPortal->name;
        }

        return $aPortals;
    }
}
