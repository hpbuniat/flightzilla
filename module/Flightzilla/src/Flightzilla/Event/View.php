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

namespace Flightzilla\Event;

use \Zend\Mvc\MvcEvent;

/**
 * A simple event-handler for the authentication
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class View {

    /**
     * Event handler for the view
     *
     * @param \Zend\Mvc\MvcEvent $oEvent
     *
     * @return bool
     */
    public static function setup(MvcEvent $oEvent) {
        $oServiceManager = $oEvent->getApplication()->getServiceManager();

        /* @var $oConfig \Zend\Config\Config */
        $oConfig = $oServiceManager->get('_serviceConfig');

        /* @var $oAuth \Flightzilla\Authentication\Adapter */
        $oAuth = $oServiceManager->get('_auth');

        $oViewModel = $oEvent->getViewModel();
        $oViewModel->sController = $oEvent->getController();
        $oViewModel->sRoute = $oEvent->getRouteMatch()->getMatchedRouteName();
        $oViewModel->sBugzilla = $oConfig->bugzilla->baseUrl;
        $oViewModel->sName = $oConfig->name;
        $oViewModel->oConfig = $oConfig->bugzilla;

        $oViewModel->sCurrentUser = $oAuth->getLogin();

        $aProducts = $oConfig->bugzilla->projects->toArray();
        $oSession = $oServiceManager->get('_session');
        if ($oSession->offsetExists('sCurrentProduct') !== true) {
            $oSession->offsetSet('sCurrentProduct', key($aProducts));
        }

        $oViewModel->sCurrentProject = $oSession->offsetGet('sCurrentProduct');
        $oViewModel->aConfigProjects = array_keys($aProducts);
        unset ($aProducts);

        return true;
    }
}
