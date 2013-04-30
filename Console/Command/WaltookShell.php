<?php
App::uses( 'SmsComponent', 'Waltook.Controller/Component' );
/**
 * Consola para testear la configuración del sistema waltoolk
 * @author Esteban Zeller
 */
class WaltookShell extends Shell {
	
	/**
	 * Main execution of shell
	 * Obtiene automaticamente la cantidad de crédito necesario para probar la comunicación con el servidor
	 * @return void
	 */
	public function main() {
		$this->Sms = new SmsComponent();
		//$this->Sms->initialize();
		$this->Sms->parametros( 200, '83978016f41d43314766b7116f922284', 'GET' );
		$this->out( 'Consola de pruebas del sistema Waltoolk' );
		$this->out( '======= == ======= === ======= ========' );
		$this->out( ' ' );
		$this->out( 'Utilizando número de cliente: '. $this->Sms->getClientID() );
		$this->out( 'Utilizando key: '.$this->Sms->getKey() );
		$this->out( 'Utilizando método:'.$this->Sms->getMethod() );
		$this->out( 'Utilizando url:'.$this->Sms->getUrl() );
		$this->out( '----> Consultando creditos' );
		$devolucion = $this->Sms->getCreditoMensajes();
		$this->out( 'Cantidad de mensajes salientes: '. $devolucion['salida'] );
		$this->out( 'Cantidad de mensajes entrantes: '. $devolucion['entrada'] );
		$this->out( 'Mensaje de estado: '. $devolucion['status'] );
		return;		
	}
}