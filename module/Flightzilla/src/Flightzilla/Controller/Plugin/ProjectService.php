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
namespace Flightzilla\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin,
    Zend\Mvc\Controller\AbstractActionController;

/**
 * A plugin to init the project-service
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class ProjectService extends AbstractTicketService {

    /**
     * Name of the plugin
     *
     * @var string
     */
    const NAME = 'projectservice';

    /**
     * Init the ticket-service
     *
     * @param  \Zend\View\Model\ViewModel $oViewModel
     * @param  AbstractTicketService $oTicketService
     *
     * @return $this
     */
    public function init(\Zend\View\Model\ViewModel $oViewModel, AbstractTicketService $oTicketService) {
        $oViewModel->mode = 'project';

        $oServiceModel = $oTicketService->init($oViewModel, $oViewModel->mode)->getService();
        /* @var \Flightzilla\Model\Ticket\Source\Bugzilla $oServiceModel */

        $oViewModel->aProjects = $oServiceModel->getProjects();
        $oViewModel->oResourceManager = $oServiceModel->getResourceManager();

        $oKanbanStatus = new \Flightzilla\Model\Kanban\Status($oServiceModel->getProjects(), $oServiceModel);
        $oViewModel->aKanban = $oKanbanStatus->setGrouped()->setTypes(array(
            \Flightzilla\Model\Ticket\Type\Bug::TYPE_PROJECT,
            \Flightzilla\Model\Ticket\Type\Bug::TYPE_THEME,
        ))->process()->getByTicket();

        return $oViewModel;
    }
}
