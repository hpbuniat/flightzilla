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
 * View-Helper to create a deadline-flag
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class View_Helper_Deadlinestatus extends Zend_View_Helper_Abstract {

    /**
     * Determine the deadline-status of a bug
     *
     * @param  Model_Ticket_Type_Bug $oBug
     *
     * @return string
     */
    public function deadlinestatus(Model_Ticket_Type_Bug $oBug) {

        if ($oBug->deadlineStatus()) {
            $sIcon = 'ui-silk-flag-green';
            switch ($oBug->deadlineStatus()) {
                case Model_Ticket_Type_Bug::DEADLINE_PAST:
                    $sIcon = 'ui-silk-flag-pink';
                    break;

                case Model_Ticket_Type_Bug::DEADLINE_TODAY:
                    $sIcon = 'ui-silk-flag-red';
                    break;

                case Model_Ticket_Type_Bug::DEADLINE_NEAR:
                    $sIcon = 'ui-silk-flag-yellow';
                    break;

                case Model_Ticket_Type_Bug::DEADLINE_WEEK:
                    $sIcon = 'ui-silk-flag-orange';
                    break;

                default:
                    break;
            }

            return '&nbsp;<span class="deadline ui-silk ' . $sIcon . '" title="' . $oBug->getDeadline() . '">&nbsp;</span>';
        }

        return '';
    }
}
