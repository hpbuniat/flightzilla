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
namespace Flightzilla\Controller;

use Zend\Mvc\Controller\AbstractActionController,
    Zend\View\Model\ViewModel,
    Flightzilla\Controller\Plugin\AnalyticsService as Analytics;

/**
 * Access google-analytics
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class AnalyticsController extends AbstractActionController {

    /**
     *
     */
    public function campaignAction() {
        $oAnalytics = $this->getServiceLocator()->get('_analytics');

        $oViewModel = new ViewModel;
        $oViewModel->mode = 'analytics';
        $oViewModel->aPortals = $oAnalytics->getPortals();
        return $oViewModel;
    }

    /**
     *
     */
    public function browserAction() {
        $oAnalytics = $this->getServiceLocator()->get('_analytics');

        $oViewModel = new ViewModel;
        $oViewModel->mode = 'analytics';
        $oViewModel->aPortals = $oAnalytics->getPortals();
        return $oViewModel;
    }

    /**
     *
     */
    public function dataAction() {
        $oViewModel = new ViewModel;
        $oViewModel->setTerminal(true);

        $oViewModel->sPortal = htmlentities($this->params()->fromPost('portal'));
        if (empty($oViewModel->sPortal) !== true) {
            $oViewModel->mode = $this->params()->fromPost('mode');
            $oViewModel->which = $this->params()->fromPost('which');
            $oViewModel->sTarget = $this->params()->fromPost('container');

            $oService = $this->getPluginManager()->get(Analytics::NAME)->init($oViewModel, $oViewModel->sPortal);

            $this->getResponse()->getHeaders()->addHeaders(array(
                'Content-Type' => $oService->getContentType()
            ));
        }
        else {
            $this->getResponse()->setStatusCode(\Zend\Http\Response::STATUS_CODE_404);
        }

        return $oViewModel;
    }

    /**
     * Create a save-to-store password for the flightzilla.ini, AES-Encrypted with the ldap-password
     */
    public function cryptAction() {
        $oViewModel = new ViewModel;
        $oViewModel->setTerminal(true);

        $oAnalytics = $this->getServiceLocator()->get('_analytics');
        $sKey = $oAnalytics->getCipherKey();
        \Zend\Debug\Debug::dump($oAnalytics->getAuth()->encrypt($sKey, $this->getServiceLocator()->get('_serviceConfig')->analytics->unsecurepassword), 'Duration:' . (microtime(true) - STARTTIME) .  ' - ' . __FILE__ . ':' . __LINE__ . PHP_EOL);
        exit;
    }
}

