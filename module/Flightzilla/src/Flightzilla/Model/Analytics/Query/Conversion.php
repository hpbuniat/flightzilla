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
 * Query Google-Analytics
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Conversion extends AbstractQuery {

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
     * (non-PHPdoc)
     * @see AbstractQuery::_collect()
     */
    protected function _collect(\Zend\Http\Client $oHttp, \Zend\Config\Config $oPortal) {
        $this->_getDays(self::NUMBER_OF_DAYS, self::NUMBER_OF_WEEKS);

        $aMetric = array();
        foreach ($this->_aDays as $sFirst => $aCompare) {
            $aMetric[$sFirst]['base'] = $this->_process($this->_fetch($oHttp, $oPortal->id, $sFirst, $sFirst));
            foreach ($aCompare as $sCompare) {
                $aMetric[$sFirst]['compare'][$sCompare] = $this->_process($this->_fetch($oHttp, $oPortal->id, $sCompare, $sCompare));
            }
        }

        $this->_aMetrics[$oPortal->name] = $aMetric;
        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see AbstractQuery::_process()
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
     * (non-PHPdoc)
     * @see AbstractQuery::_fetch()
     */
    protected function _fetch(\Zend\Http\Client $oHttp, $iProfile, $sStartDate, $sEndDate) {
        $oService = new \ZendGData\Analytics($oHttp);
        $oQuery = $oService->newDataQuery()->setProfileId($iProfile)
            ->addDimension(\ZendGData\Analytics\DataQuery::DIMENSION_CAMPAIGN)
            ->addMetric(\ZendGData\Analytics\DataQuery::METRIC_VISITS)
            ->addMetric(\ZendGData\Analytics\DataQuery::METRIC_TRANSACTIONS)
            ->setStartDate($sStartDate)
            ->setEndDate($sEndDate)
            ->addSort(\ZendGData\Analytics\DataQuery::METRIC_VISITS, true)
            ->setMaxResults(100);

        if ($this->_aOptions['paid'] === true) {
            $oQuery->addFilter('ga:medium==cpa,ga:medium==cpc,ga:medium==cpm,ga:medium==cpp,ga:medium==cpv,ga:medium==ppc');
        }

        return $this->_queryAnalytics($oQuery, $oService);
    }
}
