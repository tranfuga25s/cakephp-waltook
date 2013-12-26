<?php
App::uses('ComponentCollection', 'Controller');
App::uses('Component', 'Controller');
App::uses('AppController', 'Controller');
App::uses('SessionComponent', 'Controller/Component');
App::uses('SmsComponent', 'Waltook.Controller/Component');

/**
 * DiaTurnoRecallComponent Test Case
 *
 */
class SmsComponentTest extends CakeTestCase {


    public $components = array( 'Session' );

    private $controlador = null;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp() {
        parent::setUp();
        $this->Collection = new ComponentCollection();
        $this->Sms = new SmsComponent( $this->Collection );
        $this->Session = new SessionComponent( $this->Collection );
        $this->controlador = new AppController();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown() {
        unset($this->Sms);
        unset($this->Session);
        unset($this->controlador);
        unset($this->Collection);

        parent::tearDown();
    }

    public function testbasic() {
        $this->assertEqual( true, true );
    }

}