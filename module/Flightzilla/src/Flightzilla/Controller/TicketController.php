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
    Flightzilla\Controller\Plugin\Authenticate,
    Flightzilla\Controller\Plugin\TicketService;

/**
 * Ticket-related controller
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class TicketController extends AbstractActionController {

    /**
     *
     */
    public function listAction() {
        $oViewModel = new ViewModel;
        $oViewModel->setTerminal(true);

        $oViewModel->dropAction = $this->params()->fromPost('drop');
        $sTickets = $this->params()->fromPost('tickets');
        if (empty($sTickets) !== true) {
            $oViewModel->aTickets = $this->getPluginManager()->get(TicketService::NAME)->getService()->getBugListByIds($sTickets, false);
        }

        return $oViewModel;
    }

    /**
     *
     */
    public function modifyAction() {
        $oViewModel = new ViewModel;
        $oViewModel->setTerminal(true);

        $aModify = $this->params()->fromPost('modify');
        $aSpecial = array(
            'estimation',
            'worked'
        );
        foreach ($aSpecial as $sSpecial) {
            $aTemp = $this->params()->fromPost($sSpecial);
            if (empty($aTemp) !== true) {
                foreach ($aTemp as $iTicket => $aActions) {
                    foreach ($aActions as $mValue) {
                        $aModify[$iTicket][] = array(
                            'action' => sprintf('set%s', ucfirst($sSpecial)),
                            'value' => $mValue,
                        );
                    }
                }
            }
        }


        $oTicketService = $this->getPluginManager()->get(TicketService::NAME)->getService();
        $aTickets = array();
        if (empty($aModify) !== true) {
            foreach ($aModify as $iTicket => $aActions) {
                $oTicketWriter = new \Flightzilla\Model\Ticket\Source\Writer\Bugzilla($oTicketService);
                $oTicket = $oTicketService->getBugById($iTicket);
                foreach ($aActions as $mAction) {
                    $sAction = (is_scalar($mAction) === true) ? $mAction : $mAction['action'];
                    $mValue = (is_scalar($mAction) === true) ? false : $mAction['value'];

                    if (method_exists($oTicketWriter, $sAction) === true) {
                        $oTicketWriter->$sAction($oTicket, $mValue);
                    }
                }

                $oTicketService->updateTicket($oTicketWriter);
                unset($oTicketWriter);

                $aTickets[] = $oTicket->id();
            }
        }

        $oViewModel->aTickets = $oTicketService->getBugListByIds($aTickets, false);
        return $oViewModel;
    }
}

