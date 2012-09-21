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
     * @var array
     */
    protected $_aDependentProjects = array();

    /**
     * Get start date as timestamp.
     *
     * Start date is either the end of its predecessor or the next workday.
     *
     * @return int
     */
    public function getStartDate() {
        if ($this->_iStartDate > 0){
            return $this->_iStartDate;
        }

        // is there a predecessor?
        $iPredecessor = $this->getActivePredecessor();
        if ($iPredecessor > 0) {
            $iEndDate = $this->_oBugzilla->getBugById($iPredecessor)->getEndDate();
            $this->_iStartDate = strtotime('+1 day ' . Model_Timeline_Date::START, $iEndDate);
        }

        if ($this->_iStartDate === 0){
            $this->_iStartDate = strtotime('tomorrow ' . Model_Timeline_Date::START);
        }

        $this->_iStartDate = $this->_oDate->getNextWorkday($this->_iStartDate);

        return $this->_iStartDate;
    }

    /**
     * Get the end-date as timestamp.
     *
     * @return int
     */
    public function getEndDate() {

        if ($this->_iEndDate > 0) {
            return $this->_iEndDate;
        }

        if ($this->cf_due_date) {
            $this->_iEndDate = strtotime((string) $this->cf_due_date);
        }
        else {
            // End date of the last ticket in current project
            $aEndDates = array();
            $depends   = $this->getDepends($this->_oBugzilla);
            foreach ($depends as $child) {
                $aEndDates[] = (float) $this->_oBugzilla
                    ->getBugById($child)
                    ->getEndDate();
            }

            arsort($aEndDates);
            $this->_iEndDate = current($aEndDates);

            $this->_iEndDate = $this->_oDate->getNextWorkday($this->_iEndDate);
        }

        return $this->_iEndDate;
    }

    /**
     * Return the ticket number of the projects predecessor or 0 if there isn't one.
     *
     * A valid predecessor has the status unconfirmed, confirmed or assigned, and is a project or theme.
     * If there are more than one predecessors, the one with the latest end date will be returned.
     *
     * @return int
     */
    public function getActivePredecessor() {

        if ($this->hasDependencies()) {
            $dependencies = $this->getDependentProjects();

            $aEndDates = array();

            foreach ($dependencies as $dependency) {

                $oTicket = $this->_oBugzilla->getBugById($dependency);
                if ($oTicket->isStatusAtMost(Model_Ticket_Type_Bug::STATUS_REOPENED)) {
                    $aEndDates[$oTicket->id()] = $oTicket->getEndDate();
                }
            }

            if (empty($aEndDates) === true) {
                return 0;
            }

            arsort($aEndDates);
            return key($aEndDates);
        }

        return 0;
    }

    /**
     * Determine all dependent projects.
     *
     * @return array
     */
    public function getDependentProjects() {

        if (empty($this->_aDependentProjects) === false) {
            return $this->_aDependentProjects;
        }

        if (isset($this->dependson) === true) {
            foreach ($this->dependson as $iBug) {
                try {
                    $iBug = (int) $iBug;
                    $oBug = $this->_oBugzilla->getBugById($iBug);
                    if ($oBug->isProject() === true and $oBug->isTheme() === true) {
                        $this->_aDependentProjects[] = $iBug;
                    }
                }
                catch (Exception $e) {
                    /* happens, if a bug is not found, which is ok for closed bugs */
                }
            }
        }

        return $this->_aDependentProjects;
    }
}
