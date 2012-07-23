<?php
class View_Helper_Flagicons extends Zend_View_Helper_Abstract {

    /**
     * Get the workflow-stats of the bug
     *
     * @param  Model_Ticket_Type_Bug $oBug
     *
     * @return string
     */
    public function flagicons(Model_Ticket_Type_Bug $oBug) {
        $sClasses = '';
        if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_COMMENT, '?')) {
            $sClasses .= '&nbsp;<span class="ui-silk ui-silk-exclamation" title="' . Model_Ticket_Type_Bug::FLAG_COMMENT . '">&nbsp;</span>';
            if (strlen($oBug->commentrequest_user) > 0) {
                $sClasses .= '<span class="red">&rarr; ' . $oBug->commentrequest_user . '</span>';
            }
        }

        if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_TRANSLATION, '+')) {
            $sClasses .= '<span class="red">i18n</span>';
        }

        if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_SCREEN, '+')) {
            $sClasses .= '&nbsp;<span class="ui-silk ui-silk-thumb-up" title="' . Model_Ticket_Type_Bug::FLAG_SCREEN . '">&nbsp;</span>';
        }

        if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_DBCHANGE, '+')) {
            $sClasses .= '&nbsp;<span class="ui-silk ui-silk-database-refresh" title="' . Model_Ticket_Type_Bug::FLAG_DBCHANGE . '">&nbsp;</span>';
        }

        if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_TESTING, '?') and strlen($oBug->testingrequest_user) > 0) {
            $sClasses .= '<span class="testing red">&rarr; ' . $oBug->testingrequest_user . '</span>';
        }

        return $sClasses;
    }
}