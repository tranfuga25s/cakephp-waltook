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

```
Configure::write( 'Waltook.client_id', ID_CLIENTE );
Configure::write( 'Waltook.cliente_key', KEY );
Configure::write( 'Waltook.method', 'POST' );
Configure::write( 'Waltook.request_code', 'TSSFE' );
```

Cuando se hace la carga del plugin será necesario hacerlo de la siguiente manera:

```
CakePlugin::load( 'Waltoolk', array( 'bootstrap' => true ) );
```

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

Lista de mensajes recibidos
----- -- -------- ---------

$mensajes = $this->Sms->obtenerListaMensajes( $status, $tid, $format, $flag )

* status: Estado de los mensajes. 1=leidos, 0=no leidos, null = todos (por defecto).
* tid: Identificador de los mensajes
* format: Formato de devolucion. 1=Serializado, 2=JSON, 3=XML. Parametro por defecto seteado.
* flag: Bandera de no leido. Si se pasa un 0, los mensajes obtenidos se pasan a leidos. Si se coloca como 1, se mantiene el estado de "No leido"

La devolución será un array con el formato de cake:
array(
    [0] => array(
        'Sms' => array(
            'uid' => Numero de telefono
            'mensaje' => Texto del mensaje
            'status' => Identificacion interna de estado
            'estado_texto' => Identificacion de estado en formato texto
            'timestamp' => Fecha y hora recibido en formato timestamp
            'fechahora' => Fecha y hora recibido en formato texto
        )
    ),
    [1] => .....
)

si no existen mensajes, se devuelve un array vacío.

Callback de recepción de mensajes
-------- -- --------- -- --------

El sistema Waltook permite abrir una conexión directamente al servidor del cliente al recibir un mensaje con el identificador del cliente.
Si su identificador es por ejemplo: RSF y el sistema recibe un mensaje de texto con ese prefijo en el texto del mensaje, llamará a la dirección colocada como callback en la configuracion.

Para captar esta llamada, se deberá ingresar la dirección de un controlador real que esté usando el componente con una acción similar a la siguiente:

public function recibirSms() { $this->Sms->recibir(); }

Si el controlador tiene el nombre "Avisos", se colocará la direccion http://servidor.com/avisos/recibir_sms como callback.

El componente recibirá los datos y enviará la respuesta correcta al servidor de waltook.

