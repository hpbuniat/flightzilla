<?php
/**
 * Type.php
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
class Model_Ticket_Type {

    /**
     * A theme is a collection of bugs.
     */
    const THEME = 'Theme';

    /**
     * A project.
     */
    const PROJECT = 'Projekt';

    /**
     * Create a ticket
     *
     * @param  SimpleXMLElement $oXml
     *
     * @return Model_Ticket_Type_Bug|Model_Ticket_Type_Project|Model_Ticket_Type_Theme
     */
    public static function factory(SimpleXMLElement $oXml) {

        if (stripos((string) $oXml->keywords, Model_Ticket_Type::PROJECT) !== false) {
            return new Model_Ticket_Type_Project($oXml);
        }
        elseif (stripos((string) $oXml->keywords, Model_Ticket_Type::THEME) !== false) {
            return new Model_Ticket_Type_Theme($oXml);
        }

        return new Model_Ticket_Type_Bug($oXml);
    }
}
