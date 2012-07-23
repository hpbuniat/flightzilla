<?php
class View_Helper_Buggradient extends Zend_View_Helper_Abstract {

    /**
     * Get the gradient-color for a bug
     *
     * @param  Model_Ticket_Type_Bug $oBug
     * @param  boolean $bReady
     *
     * @return string
     */
    public function buggradient(Model_Ticket_Type_Bug $oBug, $bReady = false) {
        $aColors = array();
        if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_TESTING,'?')) {
            $aColors[] = 'yellow';
        }

        if ($bReady and $oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_TESTING,'+')) {
            $aColors[] = 'lightgreen';
        }

        if (($bReady xor $oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_TESTING,'+')) and $oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_TESTING,'?') === false) {
            $aColors[] = '#CCFF99';
        }

        if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_DBCHANGE,'?') or $oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_DBCHANGE_TEST,'?')) {
            $aColors[] = 'orchid';
        }

        if ($oBug->isFailed()) {
            $aColors[] = 'crimson';
        }

        if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_MERGE,'?')) {
            $aColors[] = '#9FB9FF';
        }

        if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_SCREEN,'?')) {
            $aColors[] = '#9F9F9F';
        }

        if ($oBug->hasFlag(Model_Ticket_Type_Bug::FLAG_TRANSLATION,'?')) {
            $aColors[] = 'orange';
        }

        if (count($aColors) > 0) {
            $sColors = implode(', ', $aColors) . ' 70%, transparent';
            $aBackgrounds = array(
                'background-image: -moz-linear-gradient(0deg, ' . $sColors . ');',
                'background-image: -o-linear-gradient(0deg, ' . $sColors . ');',
                'background-image: -ms-linear-gradient(0deg, ' . $sColors . ');',
                'background-image: -webkit-linear-gradient(0deg, ' . $sColors . ');',
                'background-image: -linear-gradient(0deg, ' . $sColors . ');',
            );
            return  implode($aBackgrounds);
        }

        return '';
    }
}