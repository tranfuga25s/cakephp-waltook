cakephp-waltook
===============

Una implementación del servicio sms Walltook para cakephp.

Sus métodos están basados en su api:

http://www.waltook.com/recursos/api/sdk.zip
Versión: v0.2 Febrero 2013

Configuración
=============

Son necesarios 2 elementos para hacer funcionar este plugin. En el archivo app/Plugin/Waltoolk/Config/bootstrap.php
se deberá ingresar:

Configure::write( 'Waltook.client_id', ID_CLIENTE );
Configure::write( 'Waltook.cliente_key', KEY );
Configure::write( 'Waltook.method', 'POST' );

Cuando se hace la carga del plugin será necesario hacerlo de la siguiente manera:

CakePlugin::load( 'Waltoolk', array( 'bootstrap' => true ) );

Uso
===

Para utilizarlo será necesario cargar el componente

public $components = array( 'Waltook.Sms' );

y dentro de la aplicación se podrán utilizar los siguientes métodos:

Envio de mensajes
----- -- --------

Para enviar un mensaje a un numero de teléfono:

if( $this->Sms->enviar( '3424535453', 'Mensaje' ) ) {
	$this->Session->setFlash( 'Mensaje enviado correctamente' );
}

o varios destinatarios

if( $this->Sms->enviar( array( '1432543534','232543543' ), 'Mensaje' ) ) {
}

Vista de cantidad de credito disponible
----- -- -------- -- ------- ----------

$credito = $this->Sms->getCredito();

El array devuelto por esta funcion tendrá el siguiente formato:
array(
	'entrada' => Cantidad de mensajes disponibles para recibir
	'salida' => Cantidad de mensajes disponibles para enviar desde el sistema
	'status' => Estado de la solicitud
)

