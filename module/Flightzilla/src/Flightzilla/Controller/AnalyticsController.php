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
namespace Flightzilla\Controller;

use Zend\Mvc\Controller\AbstractActionController,
    Zend\View\Model\ViewModel,
    Flightzilla\Controller\Plugin\Authenticate;

/**
 * Access google-analytics
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class AnalyticsController extends AbstractActionController {

    /**
     * @var \Flightzilla\Model\Analytics
     */
    private $_oAnalytics;

    /**
     *
     */
    public function init() {
        if (Zend_Auth::getInstance()->hasIdentity() === true) {
            $this->_oAnalytics = new \Flightzilla\Model\Analytics(Zend_Registry::get('_Auth'));
        }
    }

    /**
     *
     */
    public function indexAction() {
        if (Zend_Auth::getInstance()->hasIdentity() === true and $this->_oAnalytics instanceof \Flightzilla\Model\Analytics) {
            $this->view->mode = 'analytics';
            $this->view->aPortals = $this->_oAnalytics->getPortals();
        }
        else {
            $this->_redirect('/index/login');
        }
    }

    /**
     *
     */
    public function dataAction() {
        $this->_helper->layout()->disableLayout();

        $sMode = 'data';
        $sPortal = $this->_getParam('portal');
        if (empty($sPortal) !== true and $this->_oAnalytics instanceof \Flightzilla\Model\Analytics) {
            $this->view->mode = $this->_getParam('mode');
            $this->view->which = $this->_getParam('which');
            $bPaid = \Flightzilla\Model\Analytics::ALL_TRAFFIC;
            if ($this->view->which === 'sem') {
                $bPaid = \Flightzilla\Model\Analytics::ONLY_PAID_TRAFFIC;
            }

            switch ($this->view->mode) {
                case 'conversion':
                    $this->view->aData = $this->_oAnalytics->getPortalData($sPortal, $bPaid);
                    $this->view->aSeries = $this->_oAnalytics->getSeries($this->view->aData);
                    break;

                case 'campaign':
                    $sMode = $this->view->mode;
                    $this->view->aData = $this->_oAnalytics->getPortalData($sPortal, $bPaid);
                    break;
            }

            $this->view->oPortal = $this->_oAnalytics->getPortalInfo($sPortal);
            $this->view->sTarget = $this->_getParam('container');

            $this->getResponse()->setHeader('Content-Type', 'application/javascript');
            $this->render($sMode);
        }
        else {
            $this->getResponse()->setRawHeader('HTTP/1.1 404 Not Found');
        }
    }

    /**
     * Create a save-to-store password for the flightzilla.ini, AES-Encrypted with the ldap-password
     */
    public function cryptAction() {
        if (Zend_Auth::getInstance()->hasIdentity() === true and $this->_oAnalytics instanceof \Flightzilla\Model\Analytics) {
            $this->_helper->viewRenderer->setNoRender(true);

            $sKey = $this->_oAnalytics->getCipherKey();
            Zend_Debug::dump($this->_oAnalytics->getAuth()->encrypt($sKey, Zend_Registry::get('_Config')->model->analytics->unsecurepassword), __FILE__ . ':' . __LINE__);
        }
        else {
            throw new Exception('you will need do be logged in, to create your save password');
        }
    }
}

