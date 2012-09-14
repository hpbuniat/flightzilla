<?php
/**
 * Exception.php
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
class Model_Ticket_Type_Bug_Exception extends Exception{
    const INVALID_STATUS = '%s is not a valid bug status!';
    const INVALID_START_DATE = 'Start date must not be zero! Please edit ticket %s.';
}
