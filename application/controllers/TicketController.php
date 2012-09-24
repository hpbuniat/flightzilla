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
class TicketController extends Zend_Controller_Action {

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
            $oResource        = new Model_Resource_Manager;
            $this->_oBugzilla = new Model_Ticket_Source_Bugzilla($oResource, ($sAction !== 'dashboard'));
            $this->view->mode = $sAction;
        }
        elseif ($sAction !== 'login' and $sAction !== 'logout') {
            $this->_redirect('/index/login');
        }
    }

    /**
     *
     */
    public function listAction() {
        $this->_helper->layout()->disableLayout();

        $sTickets = $this->_getParam('tickets');
        if (empty($sTickets) !== true) {
            $this->view->aTickets = $this->_oBugzilla->getBugListByIds($sTickets);
        }
    }

    /**
     *
     */
    public function modifyAction() {
        $this->_helper->layout()->disableLayout();
        $aModify = $this->_getParam('modify');

        $aTickets = array();
        if (empty($aModify) !== true) {
            foreach ($aModify as $iTicket => $aActions) {
                $oTicketWriter = new Model_Ticket_Source_Writer_Bugzilla($this->_oBugzilla);
                $oTicket = $this->_oBugzilla->getBugById($iTicket);
                foreach ($aActions as $sAction) {
                    if (method_exists($oTicketWriter, $sAction) === true) {
                        $oTicketWriter->$sAction($oTicket);
                    }
                }

                $this->_oBugzilla->updateTicket($oTicketWriter);
                unset($oTicketWriter);

                $aTickets[] = $oTicket->id();
            }
        }

        $this->view->aTickets = $this->_oBugzilla->getBugListByIds($aTickets, false);
    }
}

