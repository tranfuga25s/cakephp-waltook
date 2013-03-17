<?php
App::uses( 'SmsComponent', 'Waltoolk.Controller/Component' );
/**
 * Consola para testear la configuración del sistema waltoolk
 * @author Esteban Zeller
 */
class WaltoolkShell extends Shell {
	
	/**
	 * Main execution of shell
	 * Obtiene automaticamente la cantidad de crédito necesario para probar la comunicación con el servidor
	 * @return void
	 */
	public function main() {
		$this->Sms = new SmsComponent();
		$this->Sms->initialize();
		$this->out( 'Consola de pruebas del sistema Waltoolk' );
		$this->out( '======= == ======= === ======= ========' );
		$this->out( ' ' );
		if( Configure::read( 'Waltoolk.cliente_id' ) == false ) {
			$this->out( 'Por favor, configure el sistema para que se pueda leer el numero de cliente y la API_KEY de Waltoolk' );
			return;
		} else {
			$this->out( 'Utilizando número de cliente: '. Configure::read( 'Waltoolk.cliente_id' ) );
		}
		$this->out( '----> Consultando creditos' );
		$devolucion = $this->Sms->getCreditoMensajes();
		$this->out( 'Cantidad de mensajes salientes: '. $devolucion['salida'] );
		$this->out( 'Cantidad de mensajes entrantes: '. $devolucion['entrada'] );
		$this->out( 'Mensaje de estado: '. $devolucion['status'] );
		return;		
	}
}