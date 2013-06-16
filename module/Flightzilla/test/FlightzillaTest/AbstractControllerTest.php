<?php

namespace FlightzillaTest;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

abstract class AbstractControllerTest extends AbstractHttpControllerTestCase {

    protected $traceError = true;

    public function setUp() {

        $this->setApplicationConfig(
            include dirname(__FILE__) . '/../../../../config/application.config.test.php'
        );

        parent::setUp();
    }

    public function tearDown() {

        $this->unsetAuth();
    }

    /**
     * Do some basic assertions
     *
     * @param string $sDispatch
     * @param string $sController
     * @param string|boolean $sAction
     * @param int $iCode
     */
    public function baseAssert($sDispatch, $sController, $sAction = false, $iCode = 200) {

        $this->dispatch($sDispatch);
        $this->assertResponseStatusCode($iCode);

        $this->assertModuleName('Flightzilla');
        $this->assertControllerName(sprintf('%s', strtolower($sController)));
        $this->assertControllerClass(sprintf('%sController', ucfirst($sController)));
        if ($sAction !== false) {
            $this->assertActionName($sAction);
        }
    }

    /**
     *
     */
    public function setAuthed() {
        $oService = $this->getApplicationServiceLocator();
        $oService->setAllowOverride(true);
        // set auth ..

    }

    /**
     *
     */
    public function unsetAuth() {

    }
}
