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
 * Access mergy-related methods
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class MergyController extends Zend_Controller_Action {

    /**
     * @var Model_Ticket_Source_Bugzilla
     */
    private $_oBugzilla;

    /**
     *
     */
    public function init() {
        $sAction = $this->getRequest()->getActionName();
        if (Zend_Auth::getInstance()->hasIdentity() === true) {
            $this->_oBugzilla = new Model_Ticket_Source_Bugzilla();
            $this->view->mode = 'mergy';
        }
        elseif ($sAction !== 'login' and $sAction !== 'logout') {
            $this->_redirect('/index/login');
        }
    }

    /**
     *
     */
    public function indexAction() {
        $this->_oBugzilla->setView($this->view);
        $this->view->sRepositories = json_encode(array_keys(Zend_Registry::get('_Config')->model->mergy->source->toArray()));
    }

    /**
     *
     */
    public function mergeAction() {
        $this->_helper->layout()->disableLayout();

        $sRepository = $this->_getParam('repo');
        $oConfig = Zend_Registry::get('_Config')->model->mergy;
        $oMergy = new Model_Mergy_Invoker(new Model_Command());
        $aStack = array();

        $sTickets = $this->_getParam('tickets');
        $bCommit = (bool) $this->_getParam('commit', false);
        if (empty($sTickets) !== true and isset($oConfig->source->$sRepository) === true) {
            $oSource = $oConfig->source->$sRepository;
            $this->view->sResult = $oMergy->merge($oConfig->command, $oSource, $sTickets, $bCommit)->getOutput();
            $this->view->sMessage = $oMergy->getMessage();
            $this->view->bSuccess = $oMergy->isSuccess();
        }
    }

    /**
     *
     */
    public function mergelistAction() {
        $this->_helper->layout()->disableLayout();

        $oConfig = Zend_Registry::get('_Config')->model->mergy;
        $oMergy = new Model_Mergy_Invoker(new Model_Command());

        $sTickets = $this->_getParam('tickets');
        if (empty($sTickets) !== true) {
            $oSources = $oConfig->source;
            foreach ($oSources as $sName => $oSource) {
                $oMergy->mergelist(new Model_Mergy_Revision_Stack($sName, $oSource), $oConfig->command, $oSource, $sTickets);
            }
        }

        $this->view->aMergyStack = $oMergy->getStack();
        unset($oConfig, $oMergy);
    }
}

