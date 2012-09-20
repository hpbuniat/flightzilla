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
 * A Project
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version Release: @package_version@
 * @link https://github.com/hpbuniat/flightzilla
 */
class Model_Ticket_Type_Project extends Model_Ticket_Type_Bug {

    /**
     * Get the end-date in seconds
     *
     * @param Model_Ticket_Source_Bugzilla $oBugzilla
     * @param Model_Resource_Manager       $oResource
     *
     * @return int
     */
    public function getEndDate(Model_Ticket_Source_Bugzilla $oBugzilla, Model_Resource_Manager $oResource) {

        if ($this->_iEndDate > 0) {
            return $this->_iEndDate;
        }

        if ($this->cf_due_date) {
            $this->_iEndDate = strtotime((string) $this->cf_due_date);
        }
        else {
            // Start date + estimated
            $iStartDate = $this->getStartDate($oBugzilla, $oResource);

            $estimated = 0.00;
            $depends   = $this->getDepends($oBugzilla);
            foreach ($depends as $child) {
                $estimated += (float) $oBugzilla
                    ->getBugById($child)
                    ->duration();
            }

            $this->_iEndDate = strtotime(sprintf('+%d day ' . Model_Timeline_Date::END, ceil($estimated / Model_Timeline_Date::AMOUNT)), $iStartDate);
        }

        return $this->_iEndDate;
    }

    /**
     * Return the ticket number of the projects predecessor or 0 if there isn't one.
     *
     * A valid predecessor has the status unconfirmed, confirmed or assigned, and is a project or theme.
     * If there are more than one predecessor, the one with the highest estimated time will be returned.
     *
     * @param Model_Ticket_Source_Bugzilla $oBugzilla
     * @param Model_Resource_Manager       $oResource
     *
     * @return int
     */
    public function getActivePredecessor(Model_Ticket_Source_Bugzilla $oBugzilla, Model_Resource_Manager $oResource) {
        if ($this->hasDependencies()) {
            $iTicket         = 0;
            $dependencies = $this->getDepends($oBugzilla);

            if (count($dependencies) > 1) {
                foreach ($dependencies as $dependency) {
                    $oComparisonTicket = $oBugzilla->getBugById($dependency);
                    if ($iTicket === 0
                        or ($oComparisonTicket->isStatusAtMost(Model_Ticket_Type_Bug::STATUS_REOPENED)
                            and ($oComparisonTicket->isTheme() === true
                                or $oComparisonTicket->isProject() === true)
                            and (float) $oBugzilla->getBugById($dependency)->getEndDate($oBugzilla, $oResource) > (float) $oBugzilla->getBugById($iTicket)->getEndDate($oBugzilla, $oResource))
                    ) {

                        $iTicket = $dependency;
                    }
                }
            }
            else {
                $oComparisonTicket = $oBugzilla->getBugById((int) $this->_data->dependson);
                if (($oComparisonTicket->isTheme() === true
                        or $oComparisonTicket->isProject() === true)
                    and $oComparisonTicket->isStatusAtMost(Model_Ticket_Type_Bug::STATUS_REOPENED)
                ) {

                    $iTicket = (int) $this->_data->dependson;
                }
            }

            return $iTicket;
        }

        return 0;
    }

}
