<?php
namespace FlightzillaTest\Controller;

class IndexControllerTest extends \FlightzillaTest\AbstractControllerTest {

    /**
     * Test, that the login is shown
     */
    public function testLoginAction() {
        $this->baseAssert('/login', 'index', 'login');
    }
}