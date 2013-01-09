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
namespace Flightzilla\Model\Analytics\Query;

/**
 * Query Google-Analytics
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Browser extends AbstractQuery {

    /**
     * (non-PHPdoc)
     * @see AbstractQuery::_collect()
     */
    protected function _collect(\Zend\Http\Client $oHttp, \Zend\Config\Config $oPortal) {
        $this->_aMetrics[$oPortal->name] = $this->_process($this->_fetch($oHttp, $oPortal->id, date('Y-m-d', strtotime('-1 week')), date('Y-m-d', strtotime('-1 day'))));
        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see AbstractQuery::_process()
     */
    protected function _process(\ZendGData\Analytics\DataFeed $oResult) {
        $aMetric = array();
        $iTotalTransactions = $iTotalVisits = 0;
        foreach ($oResult as $oRow) {
            $sBrowser = sprintf('%s (%s)', $oRow->getDimension(\ZendGData\Analytics\DataQuery::DIMENSION_BROWSER)->getValue(), $oRow->getDimension(\ZendGData\Analytics\DataQuery::DIMENSION_OPERATING_SYSTEM)->getValue());
            $sVersion = $oRow->getDimension(\ZendGData\Analytics\DataQuery::DIMENSION_BROWSER_VERSION)->getValue();
            $iVisits = $oRow->getValue(\ZendGData\Analytics\DataQuery::METRIC_VISITS)->getValue();
            $iTransactions = $oRow->getValue(\ZendGData\Analytics\DataQuery::METRIC_TRANSACTIONS)->getValue();

            if (isset($aMetric[$sBrowser]) !== true) {
                $aMetric[$sBrowser] = array(
                    'name' => $sBrowser,
                    'visits' => $iVisits,
                    'transactions' => $iTransactions,
                    'versions' => array(
                        $sVersion => array(
                            'visits' => $iVisits,
                            'transactions' => $iTransactions,
                            'conversion' => ($iVisits > 0) ? round(($iTransactions/$iVisits)*100, 2) : 0
                        )
                    )
                );
            }
            else {
                $aMetric[$sBrowser]['visits'] += $iVisits;
                $aMetric[$sBrowser]['transactions'] += $iTransactions;
                $aMetric[$sBrowser]['versions'][$sVersion] = array(
                    'visits' => $iVisits,
                    'transactions' => $iTransactions,
                    'conversion' => ($iVisits > 0) ? round(($iTransactions/$iVisits)*100, 2) : 0
                );
            }

            $iTotalVisits += $iVisits;
            $iTotalTransactions += $iTransactions;
        }

        foreach ($aMetric as $sBrowser => $aValues) {
            $aMetric[$sBrowser]['conversion'] = ($aValues['visits'] > 0) ? round(($aValues['transactions'] / $aValues['visits']) * 100, 2) : 0;
            $aMetric[$sBrowser]['share'] = ($iTotalVisits > 0) ? round(($aValues['visits'] / $iTotalVisits) * 100, 2) : 0;
            foreach ($aMetric[$sBrowser]['versions'] as $sVersion => $aVersion) {
                $aMetric[$sBrowser]['versions'][$sVersion]['share'] = ($aValues['visits'] > 0) ? round(($aVersion['visits'] / $aValues['visits']) * 100, 2) : 0;
                $aMetric[$sBrowser]['versions'][$sVersion]['totalshare'] = ($iTotalVisits > 0) ? round(($aVersion['visits'] / $iTotalVisits) * 100, 2) : 0;
            }
        }

        return $aMetric;
    }

    /**
     * (non-PHPdoc)
     * @see AbstractQuery::_fetch()
     */
    protected function _fetch(\Zend\Http\Client $oHttp, $iProfile, $sStartDate, $sEndDate) {
        $oService = new \ZendGData\Analytics($oHttp);
        $oQuery = $oService->newDataQuery()->setProfileId($iProfile)
            ->addDimension(\ZendGData\Analytics\DataQuery::DIMENSION_BROWSER)
            ->addDimension(\ZendGData\Analytics\DataQuery::DIMENSION_BROWSER_VERSION)
            ->addDimension(\ZendGData\Analytics\DataQuery::DIMENSION_OPERATING_SYSTEM)
            ->addMetric(\ZendGData\Analytics\DataQuery::METRIC_VISITS)
            ->addMetric(\ZendGData\Analytics\DataQuery::METRIC_TRANSACTIONS)
            ->setStartDate($sStartDate)
            ->setEndDate($sEndDate)
            ->addSort(\ZendGData\Analytics\DataQuery::METRIC_VISITS, true)
            ->setMaxResults(100);


        return $this->_queryAnalytics($oQuery, $oService);
    }
}
