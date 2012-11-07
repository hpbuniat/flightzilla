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
namespace Flightzilla\Model;


/**
 * Query Google-Analytics
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Analytics {

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
     * Create the analytics-model
     */
    /**
     * Do all Bug-related action
     *
     * @param \ZendGData\HttpClient $oHttpClient
     * @param \Zend\Config\Config $oConfig
     */
    public function __construct(\ZendGData\HttpClient $oHttpClient, \Zend\Config\Config $oConfig) {
        $this->_config = $oConfig->analytics;
        $this->_oHttp = $oHttpClient;

        $this->_getDays();
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
     * Set the auth-adapter
     *
     * @param  \Flightzilla\Authentication\Adapter $oAuth
     *
     * @return $this
     */
    public function setAuth(\Flightzilla\Authentication\Adapter $oAuth) {
        $this->_oAuth = $oAuth;
        if (empty($this->_config->password) !== true) {
            $this->_oHttp = \ZendGData\ClientLogin::getHttpClient(
                $this->_config->login,
                $this->_oAuth->decrypt($this->getCipherKey(), $this->_config->password),
                \ZendGData\Analytics::AUTH_SERVICE_NAME,
                $this->_oHttp
            );
        }

        return $this;
    }

    /**
     * Get the ciper key, to decyper the login
     *
     * @return string
     */
    public function getCipherKey() {
        return md5($this->_config->login . $this->_oAuth->getLogin() . $this->_oAuth->getPassword());
    }

    /**
     * Get the auth-compontent
     *
     * @return \Flightzilla\Authentication\Adapter
     */
    public function getAuth() {
        return $this->_oAuth;
    }

    /**
     * Get the metrics for all portals
     *
     * @return array
     */
    public function get() {
        foreach ($this->_config->portal as $oPortal) {
            $this->_collect($this->_oHttp, $oPortal);
        }

        return $this->_aMetrics;
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
     * Get the metrics for a single portal
     *
     * @param  string $sPortal
     * @param  boolean $bPaid
     *
     * @return array
     */
    public function getPortalData($sPortal, $bPaid = false) {
        foreach ($this->_config->portal as $oPortal) {
            if ($oPortal->name === $sPortal) {
                $this->_collect($this->_oHttp, $oPortal, $bPaid);
            }
        }

        return $this->_aMetrics[$sPortal];
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
        for ($i=0;$i<$iValues;$i++) {
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

    /**
     * Get the days to compare
     *
     * @return Analytics
     */
    protected function _getDays() {
        $this->_aDays = array();
        for ($iDay = 1; $iDay <= self::NUMBER_OF_DAYS; $iDay++) {
            $iDate = strtotime(sprintf('-%d days', $iDay));
            $sDay = date('Y-m-d', $iDate);
            $this->_aDays[$sDay] = array();
            for ($iWeek = 1; $iWeek <= self::NUMBER_OF_WEEKS; $iWeek++) {
                $this->_aDays[$sDay][] = date('Y-m-d', strtotime(sprintf('-%d weeks', $iWeek), $iDate));
            }
        }

        return $this;
    }

    /**
     * Collect data for a portal
     *
     * @param  \Zend\Http\Client $oHttp
     * @param  \Zend\Config\Config $oPortal
     * @param  boolean $bPaid
     *
     * @return Analytics
     */
    protected function _collect(\Zend\Http\Client $oHttp, \Zend\Config\Config $oPortal, $bPaid = false) {
        $aMetric = array();
        foreach ($this->_aDays as $sFirst => $aCompare) {
            $aMetric[$sFirst]['base'] = $this->_process($this->_fetch($oHttp, $oPortal->id, $sFirst, $sFirst, $bPaid));
            foreach ($aCompare as $sCompare) {
                $aMetric[$sFirst]['compare'][$sCompare] = $this->_process($this->_fetch($oHttp, $oPortal->id, $sCompare, $sCompare, $bPaid));
            }
        }

        $this->_aMetrics[$oPortal->name] = $aMetric;
        return $this;
    }

    /**
     * Process a result-data-feed
     *
     * @param  \ZendGData\Analytics\DataFeed $oResult
     *
     * @return array
     */
    protected function _process(\ZendGData\Analytics\DataFeed $oResult) {
        $aMetric = array(
            'total' => array(),
            'campaigns' => array()
        );

        $iTotalVisits = $iTotalTransactions = 0;
        foreach ($oResult as $oRow) {
            $sCampaign = $oRow->getDimension(\ZendGData\Analytics\DataQuery::DIMENSION_CAMPAIGN)->getValue();
            $iVisits = $oRow->getValue(\ZendGData\Analytics\DataQuery::METRIC_VISITS)->getValue();
            $iTotalVisits += $iVisits;

            $iTransactions = $oRow->getValue(\ZendGData\Analytics\DataQuery::METRIC_TRANSACTIONS)->getValue();
            $iTotalTransactions += $iTransactions;

            $aMetric['campaigns'][$sCampaign] = array(
                'visits' => $iVisits,
                'transactions' => $iTransactions,
            );

            $aMetric['campaigns'][$sCampaign]['conversion'] = ($iVisits > 0) ? round(($iTransactions/$iVisits)*100, 2) : 0;
        }

        $aMetric['total'] = array(
            'visits' => $iTotalVisits,
            'transactions' => $iTotalTransactions
        );

        $aMetric['total']['conversion'] = ($iTotalVisits > 0) ? round(($iTotalTransactions/$iTotalVisits)*100, 2) : 0;
        return $aMetric;
    }

    /**
     * Fetch data from analytics
     *
     * @param  \Zend\Http\Client $oHttp
     * @param  int $iProfile
     * @param  string $sStartDate
     * @param  string $sEndDate
     * @param  string $bPaid
     *
     * @return \ZendGData\Analytics\DataFeed
     */
    protected function _fetch(\Zend\Http\Client $oHttp, $iProfile, $sStartDate, $sEndDate, $bPaid = false) {
        $oService = new \ZendGData\Analytics($oHttp);
        $oQuery = $oService->newDataQuery()->setProfileId($iProfile)
                           ->addDimension(\ZendGData\Analytics\DataQuery::DIMENSION_CAMPAIGN)
                           ->addMetric(\ZendGData\Analytics\DataQuery::METRIC_VISITS)
                           ->addMetric(\ZendGData\Analytics\DataQuery::METRIC_TRANSACTIONS)
                           ->setStartDate($sStartDate)
                           ->setEndDate($sEndDate)
                           ->addSort(\ZendGData\Analytics\DataQuery::METRIC_VISITS, true)
                           ->setMaxResults(500);

        if ($bPaid === true) {
            $oQuery->addFilter('ga:medium==cpa,ga:medium==cpc,ga:medium==cpm,ga:medium==cpp,ga:medium==cpv,ga:medium==ppc');
        }

        $sUrl = $oQuery->getQueryUrl();
        $sHash = md5($sUrl);
        $oResult = $this->_oCache->getItem($sHash);
        if (empty($oResult) === true) {
            $oResult = $oService->getDataFeed($sUrl);
            $this->_oCache->setItem($sHash, $oResult);
        }

        return $oResult;
    }
}
