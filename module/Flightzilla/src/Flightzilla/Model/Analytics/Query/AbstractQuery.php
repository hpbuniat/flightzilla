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
namespace Flightzilla\Model\Analytics\Query;

/**
 * Abstract query-class
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
abstract class AbstractQuery {

    /**
     * The metrics for each portal
     *
     * @var array
     */
    protected $_aMetrics = array();

    /**
     * The days to analyze
     *
     * @var array
     */
    protected $_aDays = array();

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
     * The configuration
     *
     * @var \Zend\Config\Config
     */
    protected $_config = null;

    /**
     * Query-options
     *
     * @var array
     */
    protected $_aOptions = array(
        'paid' => false
    );

    /**
     * Setup a query
     *
     * @param \Flightzilla\Model\Analytics\Service $oAnalyticsService
     */
    public function __construct(\Flightzilla\Model\Analytics\Service $oAnalyticsService) {
        $this->_oCache = $oAnalyticsService->getCache();
        $this->_config = $oAnalyticsService->getConfig();
        $this->_oHttp = $oAnalyticsService->getHttpClient();
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
     * Set the options
     *
     * @param  array $aOptions
     *
     * @return $this
     */
    public function setOptions(array $aOptions = array()) {
        foreach ($aOptions as $sKey => $mValue) {
            if (isset($this->_aOptions[$sKey]) === true) {
                $this->_aOptions[$sKey] = $mValue;
            }
        }

        return $this;
    }

    /**
     * Get the days to compare
     *
     * @param  int $iDays
     * @param  int $iWeeks
     *
     * @return $this
     */
    protected function _getDays($iDays, $iWeeks) {
        $this->_aDays = array();
        for ($iDay = 1; $iDay <= $iDays; $iDay++) {
            $iDate = strtotime(sprintf('-%d days', $iDay));
            $sDay = date('Y-m-d', $iDate);
            $this->_aDays[$sDay] = array();
            for ($iWeek = 1; $iWeek <= $iWeeks; $iWeek++) {
                $this->_aDays[$sDay][] = date('Y-m-d', strtotime(sprintf('-%d weeks', $iWeek), $iDate));
            }
        }

        return $this;
    }

    /**
     * Execute a query
     *
     * @param  \ZendGData\Analytics\DataQuery $oQuery
     * @param  \ZendGData\Analytics $oService
     *
     * @return \ZendGData\Analytics\DataFeed
     */
    protected function _queryAnalytics(\ZendGData\Analytics\DataQuery $oQuery, \ZendGData\Analytics $oService) {
        $sUrl = $oQuery->getQueryUrl();
        $sHash = md5($sUrl);
        $oResult = $this->_oCache->getItem($sHash);
        if (empty($oResult) === true) {
            $oResult = $oService->getDataFeed($sUrl);
            $this->_oCache->setItem($sHash, $oResult);
        }

        return $oResult;
    }

    /**
     * Get the metrics for all portals
     *
     * @param  string $sPortal
     *
     * @return array
     */
    public function get($sPortal) {
        foreach ($this->_config->portal as $oPortal) {
            if ($oPortal->name === $sPortal) {
                $this->_collect($this->_oHttp, $oPortal);
            }
        }

        return (isset($this->_aMetrics[$sPortal]) === true) ? $this->_aMetrics[$sPortal] : array();
    }

    /**
     * Collect data for a portal
     *
     * @param  \Zend\Http\Client $oHttp
     * @param  \Zend\Config\Config $oPortal
     *
     * @return $this
     */
    abstract protected function _collect(\Zend\Http\Client $oHttp, \Zend\Config\Config $oPortal);

    /**
     * Process a result-data-feed
     *
     * @param  \ZendGData\Analytics\DataFeed $oResult
     *
     * @return array
     */
    abstract protected function _process(\ZendGData\Analytics\DataFeed $oResult);

    /**
     * Fetch data from analytics
     *
     * @param  \Zend\Http\Client $oHttp
     * @param  int $iProfile
     * @param  string $sStartDate
     * @param  string $sEndDate
     *
     * @return \ZendGData\Analytics\DataFeed
     */
    abstract protected function _fetch(\Zend\Http\Client $oHttp, $iProfile, $sStartDate, $sEndDate);
}
