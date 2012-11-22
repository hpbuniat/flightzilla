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
    Zend\Mvc\Controller\AbstractActionController,
    \Flightzilla\Model\Analytics\Query\Builder;

/**
 * A plugin to init the ticket-service
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class AnalyticsService extends AbstractPlugin {

    /**
     * Name of the plugin
     *
     * @var string
     */
    const NAME = 'analyticsservice';

    /**
     * The ticket-service
     *
     * @var \Flightzilla\Model\Ticket\AbstractSource
     */
    protected $_oService = null;

    /**
     * The page-content
     *
     * @var string
     */
    protected $_sContentType = 'text/html';

    /**
     * Get the ticket-service
     *
     * @return \Flightzilla\Model\Ticket\AbstractSource
     */
    public function getService() {
        if (empty($this->_oService) === true) {
            $this->_oService = $this->getController()->getServiceLocator()->get('_analytics');
        }

        return $this->_oService;
    }

    /**
     * Set the ticket-service
     *
     * @param  \Flightzilla\Model\Analytics\Service $oService
     *
     * @return $this
     */
    public function setService(\Flightzilla\Model\Analytics\Service $oService) {
        $this->_oService = $oService;
        return $this;
    }

    /**
     * Init the ticket-service
     *
     * @param  \Zend\View\Model\ViewModel $oViewModel
     * @param  string $sPortal
     *
     * @return $this
     */
    public function init(\Zend\View\Model\ViewModel $oViewModel, $sPortal = '') {
        $oAnalyticsService = $this->getService();
        /* @var $oAnalyticsService \Flightzilla\Model\Analytics\Service */

        $bPaid = ($oViewModel->which === 'sem') ? $oAnalyticsService::ONLY_PAID_TRAFFIC : $oAnalyticsService::ALL_TRAFFIC;
        switch ($oViewModel->mode) {
            case 'browser':
                $oQuery = Builder::build(Builder::BROWSER, $oAnalyticsService);
                $oViewModel->aData = $oQuery->get($sPortal);
                break;

            case 'conversion':
                $oQuery = Builder::build(Builder::CONVERSION, $oAnalyticsService);
                $oQuery->setOptions(array(
                     'paid' => $bPaid
                ));

                $oViewModel->aData = $oQuery->get($sPortal);
                $oViewModel->aSeries = $oAnalyticsService->getSeries($oViewModel->aData);
                $this->_sContentType = 'application/javascript';
                break;

            case 'campaign':
                $oQuery = Builder::build(Builder::CONVERSION, $oAnalyticsService);
                $oQuery->setOptions(array(
                     'paid' => $bPaid
                ));

                $oViewModel->aData = $oQuery->get($sPortal);
                break;
        }

        $oViewModel->oPortal = $oAnalyticsService->getPortalInfo($sPortal);
        $oViewModel->setTemplate(sprintf('flightzilla/analytics/data/%s', $oViewModel->mode));

        return $this;
    }

    /**
     * Get the content-type
     *
     * @return string
     */
    public function getContentType() {
        return $this->_sContentType;
    }
}
