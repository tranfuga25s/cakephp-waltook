<?php
App::uses('ComponentCollection', 'Controller');
App::uses('Component', 'Controller');
App::uses('AppController', 'Controller');
App::uses('SessionComponent', 'Controller/Component');
App::uses('SmsComponent', 'Waltook.Controller/Component');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');

/**
 * DiaTurnoRecallComponent Test Case
 *
 */
class SmsComponentTest extends CakeTestCase {


    public $components = array( 'Session' );

    private $controlador = null;

    private $request_code = 'TSSFE';
    private $client_id = 200;
    private $key = '83978016f41d43314766b7116f922284';
    private $method = 'GET';

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
        $this->Sms->startup($this->controlador);
        Configure::write( 'Waltook.client_id', $this->client_id );
        Configure::write( 'Waltook.key', $this->key );
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

    /*!
     * Pruebo que existan los archivos de configuracion
     */
    public function testInicializacion() {
        $this->Sms->initialize( $this->controlador );
        $this->Sms->parametros( $this->client_id, $this->key, $this->method, $this->request_code );
        $this->assertNotEqual( $this->Sms->getClientId(), 0, "La clave de cliente no puede ser cero" );
        $this->assertNotEqual( $this->Sms->getKey(), null, "La clave de cliente no puede ser nula" );
        $this->assertNotEqual( $this->Sms->getMethod(), null, "El metodo no puede ser nulo" );
        $this->assertNotEqual( $this->Sms->getUrl(), null, "El Url no puede ser nulo" );
        $this->assertNotEqual( $this->Sms->getRequestCode(), null, "El Request code no coincide" );
    }

    public function testCodigosDevoluciones() {
        /// @TODO: Agregar validaciones
        /* $this->assertEqual( $this->Sms->devolucionCorrecta(), "OK", "La devolucion correcta debe ser la cadena OK" );
        $this->assertEqual( $this->Sms->devolucionIncorrecta(), "ERROR", "La devolución incorrecta debe ser la cadena ERROR" );*/
    }

    public function testHabilitado() {
        $this->assertEqual( $this->Sms->habilitado(), true, "El componente no está habilitado" );
    }

    public function testConfigurarServicio() {
        $this->Sms->configurarServicio( $this->client_id, $this->key, $this->method, $this->request_code );
        $this->assertFileExists( APP.'Plugin'.DS.'Waltook'.DS.'Config'.DS.'bootstrap.php', "No existe el archivo de configuracion" );
        $archivo = new File( APP.'Plugin'.DS.'Waltook'.DS.'Config'.DS.'bootstrap.php', false );
        $this->assertEqual( $archivo->open( 'r' ), true, "No se pudo abrir el archivo" );
        $leido = $archivo->read();
        $this->assertNotEqual( $leido, false, "No se pudo leer el archivo" );
        $lineas = explode( "\n", $leido );
        $this->assertNotCount( 0, $lineas, "No existen lineas en el archivo leido" );
        $this->assertCount( 8, $lineas, "No existen la cantidad exacta de lineas - archivo modificado luego" );
    }

}