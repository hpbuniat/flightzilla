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
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */

/**
 * Ticket-related controller
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class IndexController extends Zend_Controller_Action {

    /**
     * @var Model_Ticket_Source_Bugzilla
     */
    private $_oBugzilla;

    /**
     * Some common init stuff
     */
    public function init() {
        $sAction = $this->getRequest()->getActionName();
        if (Zend_Auth::getInstance()->hasIdentity() === true) {
            $this->_oBugzilla = new Model_Ticket_Source_Bugzilla(($sAction !== 'dashboard'));
            $this->view->mode = $sAction;
        }
        elseif ($sAction !== 'login' and $sAction !== 'logout') {
            $this->_redirect('/index/login');
        }
    }

    /**
     * Login
     */
    public function loginAction() {
        Zend_Auth::getInstance()->clearIdentity();
        setcookie('_BUGZILLA_LOGIN', '', -1);
        if ($this->_getParam('username', false) !== false and $this->_getParam('password', false) !== false) {
            $adapter = new Model_Auth($this->_getAllParams());
            $oAuth = Zend_Auth::getInstance();
            $oAuth->authenticate($adapter);

            if (Zend_Auth::getInstance()->hasIdentity() === true) {
                setcookie('_BUGZILLA_LOGIN', $adapter->getCrypted(), time() + 604800, Zend_Controller_Front::getInstance()->getBaseUrl());
            }

            $this->_redirect('/index');
        }
    }

    /**
     * Logout
     */
    public function logoutAction() {
        Zend_Auth::getInstance()->clearIdentity();
        setcookie('_BUGZILLA_LOGIN', '', -1);
        $this->_redirect('/index/login');
    }

    /**
     * Main overview
     */
    public function indexAction() {
        $this->_oBugzilla->setView($this->view);
        $this->view->mode = 'list';
    }

    /**
     *
     */
    public function dashboardAction() {
        $this->_oBugzilla->setView($this->view);
        $this->render('index');
    }

    /**
     *
     */
    public function teamAction() {
        $this->_oBugzilla->setView($this->view);
    }

    /**
     *
     */
    public function summaryAction() {
        $sDate = $this->_getParam('date');
        if (strtotime($sDate) === false or strtotime($sDate) === 0) {
            $sDate = '';
        }

        if (empty($sDate) === true) {
            $sDate = date('Y-m-d', strtotime('last weekday'));
        }

        $this->view->sDate = $sDate;
        $this->view->bugsSummary = $this->_oBugzilla->getSummary($sDate);
    }

    /**
     * Show ticket-details directly in flightzilla
     */
    public function detailAction() {
        $this->_helper->layout()->disableLayout();

        $sTicket = $this->getParam('ticket');
        if (empty($sTicket) !== true) {
            $this->view->oTicket = $this->_oBugzilla->getBugById($sTicket);
        }
    }

    /**
     *
     */
    public function goBugzillaAction() {
        $params = implode(',', $this->_getParam('id'));
        $this->_redirect(Zend_Registry::get('_Config')->model->bugzilla->baseUrl . '/buglist.cgi?quicksearch=' . $params);
        exit();
    }
}

