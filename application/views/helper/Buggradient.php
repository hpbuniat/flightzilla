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
 * View-Helper create a status-indicating background-gradient
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class View_Helper_Buggradient extends Zend_View_Helper_Abstract {

    /**
     * Get the gradient-color for a bug
     *
     * @param  Model_Ticket_Type_Bug $oBug
     * @param  boolean $bReady
     * @param  boolean $bTransparent
     *
     * @return string
     */
    public function buggradient(Model_Ticket_Type_Bug $oBug, $bReady = false, $bTransparent = true) {

        $aColors = array();
        $bTestingOpen = $oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_TESTING, '?');
        $bTestingGranted = $oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_TESTING, '+');
        if ($bTestingOpen === true) {
            $aColors[] = 'yellow';
        }

        if ($bReady and $bTestingGranted) {
            $aColors[] = 'lightgreen';
        }

        if (($bReady xor $bTestingGranted) and $bTestingOpen === false) {
            $aColors[] = '#CCFF99';
        }

        if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_DBCHANGE, '?') or $oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_DBCHANGE_TEST, '?')) {
            $aColors[] = 'orchid';
        }

        if ($oBug->isFailed() === true and $bTestingGranted !== true) {
            $aColors[] = 'crimson';
        }

        if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_MERGE, '?')) {
            $aColors[] = '#9FB9FF';
        }

        if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_SCREEN, '?')) {
            $aColors[] = '#9F9F9F';
        }

        if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_TRANSLATION, '?')) {
            $aColors[] = 'orange';
        }

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
            return implode($aBackgrounds);
        }

        return '';
    }
}
