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
     * The cache-instance
     *
     * @var Zend_Cache_Core
     */
    protected $_oCache = null;

    /**
     * The configuration
     *
     * @var Zend_Config
     */
    protected $_config = null;

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
     * @var Zend_Http_Client
     */
    protected $_oHttp = null;

    /**
     * The auth-component
     *
     * @var \Flightzilla\Model\Authenticate
     */
    protected $_oAuth = null;

    /**
     * Create the analytics-model
     *
     * @param Model_Auth $oAuth
     */
    public function __construct(Model_Auth $oAuth) {
        $this->_oCache = Zend_Registry::get('_Cache');
        $this->_config = Zend_Registry::get('_Config')->model->analytics;
        $this->_oAuth = $oAuth;

        $this->_getDays();
        if (empty($this->_config->password) !== true) {
            $this->_oHttp = Zend_Gdata_ClientLogin::getHttpClient($this->_config->login, $this->_oAuth->decrypt($this->getCipherKey(), $this->_config->password), Zend_Gdata_Analytics::AUTH_SERVICE_NAME);
        }
    }

    /**
     * Get the ciper key, to decyper the login
     *
     * @return string
     */
    public function getCipherKey() {
        return md5($this->_config->login . Zend_Registry::get('_login') . Zend_Registry::get('_password'));
    }

    /**
     * Get the auth-compontent
     *
     * @return Model_Auth
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
     * @return Zend_Config
     *
     * @throws InvalidArgumentException
     */
    public function getPortalInfo($sPortal) {
        foreach ($this->_config->portal as $oPortal) {
            if ($oPortal->name === $sPortal) {
                return $oPortal;
            }
        }

        throw new InvalidArgumentException('unknown portal');
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
     * @param  Zend_Http_Client $oHttp
     * @param  Zend_Config $oPortal
     * @param  boolean $bPaid
     *
     * @return Analytics
     */
    protected function _collect(Zend_Http_Client $oHttp, Zend_Config $oPortal, $bPaid = false) {
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
     * @param  Zend_Gdata_Analytics_DataFeed $oResult
     *
     * @return array
     */
    protected function _process(Zend_Gdata_Analytics_DataFeed $oResult) {
        $aMetric = array(
            'total' => array(),
            'campaigns' => array()
        );

        $iTotalVisits = $iTotalTransactions = 0;
        foreach ($oResult as $oRow) {
            $sCampaign = $oRow->getDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_CAMPAIGN)->getValue();
            $iVisits = $oRow->getValue(Zend_Gdata_Analytics_DataQuery::METRIC_VISITS)->getValue();
            $iTotalVisits += $iVisits;

            $iTransactions = $oRow->getValue(Zend_Gdata_Analytics_DataQuery::METRIC_TRANSACTIONS)->getValue();
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
     * @param  Zend_Http_Client $oHttp
     * @param  int $iProfile
     * @param  string $sStartDate
     * @param  string $sEndDate
     * @param  string $bPaid
     *
     * @return Zend_Gdata_Analytics_DataFeed
     */
    protected function _fetch(Zend_Http_Client $oHttp, $iProfile, $sStartDate, $sEndDate, $bPaid = false) {
        $oService = new Zend_Gdata_Analytics($oHttp);
        $oQuery = $oService->newDataQuery()->setProfileId($iProfile)
                           ->addDimension(Zend_Gdata_Analytics_DataQuery::DIMENSION_CAMPAIGN)
                           ->addMetric(Zend_Gdata_Analytics_DataQuery::METRIC_VISITS)
                           ->addMetric(Zend_Gdata_Analytics_DataQuery::METRIC_TRANSACTIONS)
                           ->setStartDate($sStartDate)
                           ->setEndDate($sEndDate)
                           ->addSort(Zend_Gdata_Analytics_DataQuery::METRIC_VISITS, true)
                           ->setMaxResults(500);

        if ($bPaid === true) {
            $oQuery->setFilter('ga:medium==cpa,ga:medium==cpc,ga:medium==cpm,ga:medium==cpp,ga:medium==cpv,ga:medium==ppc');
        }

        $sUrl = $oQuery->getQueryUrl();
        $sHash = md5($sUrl);
        $oResult = $this->_oCache->load($sHash);
        if ($oResult === false) {
            $oResult = $oService->getDataFeed($sUrl);
            $this->_oCache->save($oResult, $sHash);
        }

        return $oResult;
    }
}
