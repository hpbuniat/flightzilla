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

/**
 * View-Helper create a status-indicating background-gradient
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012-2013 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
namespace Flightzilla\View\Helper;
use Zend\View\Helper\AbstractHelper,
    Flightzilla\Model\Ticket\Type\Bug,
    Flightzilla\Model\Ticket\Source\Bugzilla;

class Buggradient extends AbstractHelper {

    /**
     * flightzilla color-definitions
     *
     * @var string
     */
    const COLOR_TESTING = 'yellow';
    const COLOR_TESTED = 'lightgreen';
    const COLOR_PARTIALLY_TESTED = '#CCFF99';
    const COLOR_DB_CHANGE = 'orchid';
    const COLOR_TEST_FAILED = 'crimson';
    const COLOR_MERGE_REQUEST = '#9FB9FF';
    const COLOR_SCREEN_REQUEST = '#9F9F9F';
    const COLOR_TRANSLATION_REQUEST = 'orange';
    const COLOR_PRODUCT_DEPENDENCY = '#770000';

    /**
     * Get the gradient-color for a bug
     *
     * @param  \Flightzilla\Model\Ticket\Type\Bug $oTicket
     * @param  boolean $bReady
     * @param  boolean $bTransparent
     *
     * @return string
     */
    public function __invoke(Bug $oTicket, $bReady = false, $bTransparent = true) {

        $aColors = array();
        $bTestingOpen = $oTicket->hasFlag(Bug::FLAG_TESTING, Bugzilla::BUG_FLAG_REQUEST);
        $bTestingGranted = ($oTicket->hasFlag(Bug::FLAG_TESTING, Bugzilla::BUG_FLAG_GRANTED) and $oTicket->isFailed() !== true);
        $bMergeRequestOpen = $oTicket->hasFlag(Bug::FLAG_MERGE, Bugzilla::BUG_FLAG_REQUEST);
        if ($bTestingOpen === true) {
            $aColors[] = self::COLOR_TESTING;
        }

        if ($bReady and $bTestingGranted) {
            $aColors[] = self::COLOR_TESTED;
        }

        if (($bReady xor $bTestingGranted) and $bTestingOpen === false) {
            $aColors[] = self::COLOR_PARTIALLY_TESTED;
        }

        if ($oTicket->hasFlag(Bug::FLAG_DBCHANGE, Bugzilla::BUG_FLAG_REQUEST) === true or $oTicket->hasFlag(Bug::FLAG_DBCHANGE_TEST, Bugzilla::BUG_FLAG_REQUEST) === true) {
            $aColors[] = self::COLOR_DB_CHANGE;
        }

        if ($oTicket->isFailed() === true) {
            $aColors[] = self::COLOR_TEST_FAILED;
        }

        if ($bMergeRequestOpen === true) {
            $aColors[] = self::COLOR_MERGE_REQUEST;
        }

        if ($oTicket->hasFlag(Bug::FLAG_SCREEN, Bugzilla::BUG_FLAG_REQUEST) === true) {
            $aColors[] = self::COLOR_SCREEN_REQUEST;
        }

        if ($oTicket->hasFlag(Bug::FLAG_TRANSLATION, Bugzilla::BUG_FLAG_REQUEST) === true) {
            $aColors[] = self::COLOR_TRANSLATION_REQUEST;
        }

        if (($oTicket->couldBeInTrunk() === true or ($bReady and $bTestingGranted) or $bMergeRequestOpen === true) and $oTicket->hasDependenciesToOtherProducts() === true) {
            $aColors[] = self::COLOR_PRODUCT_DEPENDENCY;
        }

        $sStyle = '';
        if (count($aColors) > 0) {
            $sColors = implode(', ', $aColors);
            $sColors .= (($bTransparent === true) ? ' 70%' : ' 100%') . ', transparent';
            $aBackgrounds = array(
                'background-image: -moz-linear-gradient(0deg, ' . $sColors . ');',
                'background-image: -o-linear-gradient(0deg, ' . $sColors . ');',
                'background-image: -ms-linear-gradient(0deg, ' . $sColors . ');',
                'background-image: -webkit-linear-gradient(0deg, ' . $sColors . ');',
                'background-image: -linear-gradient(0deg, ' . $sColors . ');',
            );
            $sStyle = implode($aBackgrounds);
            unset($aBackgrounds);
        }

        if ($oTicket->isClosed() === true) {
            $sStyle .= ' opacity: 0.25;';
        }

        unset($aColors);
        return $sStyle;
    }
}
