<?php
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
            switch($oBug->deadlineStatus()) {
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