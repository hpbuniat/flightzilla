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
    Flightzilla\Controller\Plugin\TicketService;

/**
 * Access mergy-related methods
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class MergyController extends AbstractActionController {

    /**
     *
     */
    public function indexAction() {
        $oViewModel = new ViewModel;
        $oViewModel->mode = 'sourcecode';

        $this->getPluginManager()->get(TicketService::NAME)->init($oViewModel);
        $oViewModel->sRepositories = json_encode(array_keys($this->getServiceLocator()->get('mergy')->source->toArray()));
        return $oViewModel;
    }

    /**
     *
     */
    public function statusAction() {
        $oViewModel = new ViewModel;
        $oViewModel->mode = 'sourcecode';

        $oConfig = $this->getServiceLocator()->get('mergy');
        $oLogger = $this->getServiceLocator()->get('_log');
        $oMergy = new \Flightzilla\Model\Mergy\Invoker(new \Flightzilla\Model\Command(), $oLogger);

        $oViewModel->oTicketService = $this->getPluginManager()->get(TicketService::NAME)->init($oViewModel)->getService();

        try {
            $oSources = $oConfig->source;
            foreach ($oSources as $sName => $oSource) {
                $oMergy->unmerged(new \Flightzilla\Model\Mergy\Revision\Stack($sName, $oSource), $oConfig->command, $oSource);
            }

            $oViewModel->oMergy = $oMergy;
            $oViewModel->aAllTickets = $oViewModel->oTicketService->getAllBugs();
            unset($oConfig, $oMergy);
        }
        catch (\Exception $e) {
            $oLogger->info($e);
        }

        return $oViewModel;
    }

    /**
     *
     */
    public function mergeAction() {
        $oViewModel = new ViewModel;
        $oViewModel->setTerminal(true);
        $oViewModel->mode = 'sourcecode';

        $oParams = $this->params();
        $sRepository = $oParams->fromPost('repo');

        $oConfig = $this->getServiceLocator()->get('mergy');

        /* @var $oLogger \Zend\Log\Logger */
        $oLogger = $this->getServiceLocator()->get('_log');
        $oMergy = new \Flightzilla\Model\Mergy\Invoker(new \Flightzilla\Model\Command(), $oLogger);

        $sTickets = $oParams->fromPost('tickets');
        $bCommit = (bool) $oParams->fromPost('commit', false);
        try {
            if (empty($sTickets) !== true and isset($oConfig->source->$sRepository) === true) {
                $oSource = $oConfig->source->$sRepository;
                $oViewModel->sResult = $oMergy->merge($oConfig->command, $oSource, $sTickets, $bCommit)->getOutput();
                $oViewModel->sMessage = $oMergy->getMessage();
                $oViewModel->bSuccess = $oMergy->isSuccess();
            }
        }
        catch (\Exception $e) {
            $oLogger->info($e);
            $this->getResponse()->setStatusCode(\Zend\Http\Response::STATUS_CODE_404);
        }

        return $oViewModel;
    }

    /**
     *
     */
    public function mergelistAction() {
        $oViewModel = new ViewModel;
        $oViewModel->setTerminal(true);
        $oViewModel->mode = 'sourcecode';

        $oConfig = $this->getServiceLocator()->get('mergy');
        $oLogger = $this->getServiceLocator()->get('_log');
        $oMergy = new \Flightzilla\Model\Mergy\Invoker(new \Flightzilla\Model\Command(), $oLogger);

        $sTickets = $this->params()->fromPost('tickets');
        try {
            if (empty($sTickets) !== true) {
                $oSources = $oConfig->source;
                foreach ($oSources as $sName => $oSource) {
                    $oMergy->mergelist(new \Flightzilla\Model\Mergy\Revision\Stack($sName, $oSource), $oConfig->command, $oSource, $sTickets);
                }
            }

            $oViewModel->aMergyStack = $oMergy->getStack();
            unset($oConfig, $oMergy);
        }
        catch (\Exception $e) {
            $oLogger->info($e);
            $this->getResponse()->setStatusCode(\Zend\Http\Response::STATUS_CODE_400);
        }

        return $oViewModel;
    }
}

