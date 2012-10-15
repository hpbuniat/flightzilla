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
 * @package   flightzilla
 * @author    Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license   http://opensource.org/licenses/BSD-3-Clause
 */
namespace Flightzilla\Controller;

use Zend\Mvc\Controller\AbstractActionController,
    Zend\View\Model\ViewModel,
    Flightzilla\Controller\Plugin\TicketService;

/**
 * Resource-planning
 *
 * @author    Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license   http://opensource.org/licenses/BSD-3-Clause
 * @version   Release: @package_version@
 * @link      https://github.com/hpbuniat/flightzilla
 */
class PlanningController extends AbstractActionController {

    /**
     *
     */
    public function dataAction() {
        $oViewModel = new ViewModel;
        $oServiceModel = $this->getPluginManager()->get(TicketService::NAME);
        $oServiceModel->init($this, $oViewModel, 'planning');

        $aTeam     = $oServiceModel->getTeam();
        $oResource = new \Flightzilla\Model\Resource\Manager();
        foreach ($aTeam as $sName) {
            $oResource->registerResource(\Flightzilla\Model\Resource\Builder::build($sName));
        }

        $aTickets = $oServiceModel->getAllBugs();
        foreach ($aTickets as $oTicket) {
            $oResource->addTicket($oTicket);
        }

        return $oViewModel;
    }

    /**
     *
     */
    public function projectsAction() {
        $oViewModel = new ViewModel;
        $oServiceModel = $this->getPluginManager()->get(TicketService::NAME);
        $oServiceModel->init($this, $oViewModel, 'planning');

        $oProject = new \Flightzilla\Model\Project\Container($oServiceModel);
        $oProject->setup()->sortProjects();

        $oViewModel->aStack    = $oProject->getProjectsAsStack();
        $oViewModel->aErrors   = $oProject->getErrors();
        $oViewModel->aProjects = $oProject->getProjectsRaw();
        $aProjects             = $oProject->getProjects();
        $oViewModel->sProjects = str_replace('\/', '/', json_encode($aProjects));
        return $oViewModel;
    }

    /**
     *
     */
    public function sprintAction() {
        $oViewModel = new ViewModel;
        $oServiceModel = $this->getPluginManager()->get(TicketService::NAME);
        $oViewModel->aStack = $oServiceModel->getTeam();
        return $oViewModel;
    }
}

