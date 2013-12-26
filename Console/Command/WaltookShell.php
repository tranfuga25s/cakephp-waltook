<?php
App::uses( 'ComponentCollection', 'Controller' );
App::uses( 'SmsComponent', 'Waltook.Controller/Component' );
/**
 * Consola para testear la configuración del sistema waltoolk
 * @author Esteban Zeller
 */
class WaltookShell extends Shell {

    private $Sms = null;
    private $coleccion = null;
    public $uses = array( 'Gestotux.ConteoSms' );

    private function _inicializar() {
        $this->coleccion = new ComponentCollection();
        $this->Sms = new SmsComponent( $this->coleccion );
        $this->Sms->parametros( 200, '83978016f41d43314766b7116f922284', 'GET' );
        $this->out( 'Consola de pruebas del sistema Waltoolk' );
        $this->out( '======= == ======= === ======= ========' );
        $this->out( ' ' );
        $this->out( 'Utilizando número de cliente: '. $this->Sms->getClientID() );
        $this->out( 'Utilizando key: '.$this->Sms->getKey() );
        $this->out( 'Utilizando método:'.$this->Sms->getMethod() );
        $this->out( 'Utilizando url:'.$this->Sms->getUrl() );

        Configure::config( 'Gestotux', new IniReader( ROOT.DS.APP_DIR.DS.'Plugin'.DS.'Gestotux'.DS.'Config'.DS.'cliente' ) );
        Configure::load( '', 'Gestotux' );
        $this->ConteoSms->setearCliente( Configure::read( 'Gestotux.cliente' ) );
    }

	/**
	 * Main execution of shell
	 * Obtiene automaticamente la cantidad de crédito necesario para probar la comunicación con el servidor
	 * @return void
	 */
	public function main() {
        $this->_inicializar();
		$this->out( '----> Consultando creditos' );
		$devolucion = $this->Sms->getCreditoMensajes();
		$this->out( 'Cantidad de mensajes salientes: '. $devolucion['salida'] );
		$this->out( 'Cantidad de mensajes entrantes: '. $devolucion['entrada'] );
		$this->out( 'Mensaje de estado: '. $devolucion['status'] );
		return;
	}

    public function getMensajes() {
        $this->_inicializar();
        $this->out( '----> Consultando lista de mensajes en el servidor' );
        $lista = $this->Sms->obtenerListaMensajes();
        $this->out( '-> Obtenidos '.count($lista).' mensajes.' );
        if( count( $lista ) > 0 ) {
            foreach( $lista as $numero => $mensaje ) {
                $this->out( 'Mensaje '.$numero );
                $this->out( '-> tid: '.$mensaje['Sms']['tid'] );
                $this->out( '-> estado: '.$mensaje['Sms']['estado_texto'] );
                $this->out( '-> Num teléfono: '.$mensaje['Sms']['uid'] );
                $this->out( '-> Texto: '.$mensaje['Sms']['texto'] );
                $this->out( '-> Fecha de envio: '.$mensaje['Sms']['fechahora'] );
                $this->out( '-----------------------------------------------------------------------------' );
            }
        }
    }

    public function enviarMensaje() {
        $this->_inicializar();
        $this->out( 'ATENCION: ENVIAR MENSAJES POSEE UN CARGO.' );
        $this->out( 'Enviando mensaje: ' );
        $this->out( 'Destinatario: '.$this->args[0] );
        $this->out( 'Mensaje: '.$this->args[1] );
        $input = $this->in( 'Esta seguro de enviar? (S/N)' );
        if( $input == "S" ) {
            $this->out( 'Intentando enviar mensaje' );
            $r = $this->Sms->enviar( $this->args[0], $this->args[1] );
            if( $r ) {
                if( ! $this->ConteoSms->agregarEnviado() ) {
                    $this->out( 'No se pudo registrar el envio' );
                } else {
                    $this->out( 'Se registró el envío' );
                }
                $this->out( 'Mensaje enviado correctamente' );
            } else {
                $this->out( 'Mensaje no enviado.' );
            }
        } else {
            $this->out( 'Mensaje no enviado' );
        }
    }
}