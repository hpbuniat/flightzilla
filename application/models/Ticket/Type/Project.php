<?php
/**
 * Project.php
 *
 * @copyright  Copyright (c) 2012 Unister GmbH
 * @version    $Id: $
 */

/**
 * <Subject>
 *
 * <Description...>
 *
 * @package    ???
 * @subpackage ???
 * @author     Unister GmbH <teamleitung-dev@unister-gmbh.de>
 * @author     Fluege-Dev <fluege-dev@unister.de>
 * @author     Tibor Sári <tibor.sari@unister.de>
 */
class Model_Ticket_Type_Project extends Model_Ticket_Type_Bug{

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
