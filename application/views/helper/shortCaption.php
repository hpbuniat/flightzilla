<?php
class View_Helper_ShortCaption extends Zend_View_Helper_Abstract {

    /**
     * Generate a short caption e.g. for html ids.
     *
     * @param string $caption
     * @return string
     */
    public function shortCaption($caption){
        return str_replace(' ', '', ucwords(preg_replace('#[^\w\d]+#', ' ', $caption)));
    }
}