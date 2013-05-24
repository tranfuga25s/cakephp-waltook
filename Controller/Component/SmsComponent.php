<?php
App::uses('Component', 'Controller');
App::uses('HttpSocket', 'Network/Http');
/**
 * Clase utilizada para administrar el sistema de envío de mensajes sms mediante waltook
 * @author Esteban Zeller
 */
 class SmsComponent extends Component {

   /**
    * Clave de encriptación de datos con el servidor
    */
	private $_key = null;

   /**
    * Identificador de cliente
    */
	private $_client_id = null;

   /**
    * Método de comunicación con el sistema
    */
	private $_method = null;

   /**
    * Dirección de comunicación con la API de Waltook
    * @var string $url
    */
	private $_url = "http://api.waltook.com/index.php";

   /**
    * Numero de respuestas
    */
    private $_tid = 0;

   /**
    * Formato de pedido de credito - Seteado fijo en la funcion de solicitud en el api
    * Posibles parametros:
    *  1 - Serializado
    *  2 - json
    *  3 - xml
    */
    private $_formato_credito = 1;


	private $mensajes_error = array(
		1  => 'Transacción exitosa',
		2  => 'Error de conexión con api.woltook.com',
		3  => 'Error de lectura. No se pudo desencriptar los datos',
		5  => 'Error de lectura. No se recibió ningún dato desde el servidor.',
		7  => 'Faltan parámetros',
		8  => 'Mensaje original TID no válido',
		9  => 'Número de teléfono no valido. Solo números de argentina, con prefijo DNN sin 0 o 15',
		10 => 'Mensaje muy largo. Número maximo de caracteres: 160.',
		11 => 'Cuenta suspendida',
		12 => 'No hay credito suficiente para realizar la operación.'
	);

   /**
    * Inicialización del sistema
    */
	public function initialize( /*Controller $controller*/ ) {
		// Cargo la configuracion
		if( Configure::read( 'Waltoolk.cliente_id' ) == false ) {
			throw new NotImplementedException( 'El sistema de Waltook no está configurado' );
		}
		$this->_key = Configure::read( 'Waltoolk.key' );
		$this->_client_id = Configure::read( 'Waltoolk.client_id' );
		$this->_method = Configure::read( 'Waltoolk.method' );
	}

 	public function parametros( $cliente_id, $key, $method ) {
		$this->_client_id = $cliente_id;
		$this->_key = $key;
		$this->_method = $method;
 	}

	public function getClientId() { return $this->_client_id; }
	public function getKey() { return $this->_key; }
	public function getMethod() { return $this->_method; }
	public function getUrl() { return $this->_url; }

   /**
    * Función para enviar mensajes desde el sistema
    * @param Array $numero Numero de teléfono, o un array con varios números si se desea enviar a varias personas simultaneamente
    * @param string $mensaje Mensaje a enviar
    * @return Verdadero si se pudo enviar
    * @throws NotSendedException
    */
 	public function enviar( $numero = null, $mensaje = null ) {
 		if( $numero == null ) {
 			throw new NotFoundException( 'No se configuró ningún numero telefónico' );
 		} else if( $mensaje == null ) {
 			throw new NotFoundException( 'No hay un mensaje para enviar' );
 		}

		// En el caso de que existan varios números a enviar
		if( is_array( $numero ) ) {
			$numero = implode( ',', $numero );
		}

		// Armo el array necesario
		$w_data = array(
			'uid' => $numero,
			'txt' => $mensaje,
			'tid' => $this->_numero_propio,
			'client_id' => $this->_client_id
		);

		$qstr=waltook_build_messagequery($w_data);

		$data = "q=send&client_id=".$this->_client_id."&w_qstr=".urlencode( $qstr );
		$resp_qstr = @$this->waltook_api_connect( $data );

		if( $resp_qstr )
		{
			$resp_qstr = $this->decrypt( $resp_qstr ); // Desencripta
			@parse_str( $resp_qstr, $resp_data ); // Procesa los datos y los guarda en el array $w_data
			if( $resp_data['status'] )
 			{ return true; }
 			else
 			{ throw new NotFoundException( $this->mensajes_error[$resp_data['status']] );  }// Error de lectura. No se pudo desencriptar.
		} else {
			 throw new NotFoundException( 'Error de conexión con el servidor Waltook' ); // Error de conexión
		}
 	}

   /**
    * Funcion para obtener la cantidad de credito disponible
    * @return double cantidad de credito o falso si fallo
    */
    private function getCredito() {
        // Construye el array con los parámetros de la consulta
		$w_data['client_id']=$this->_client_id;
		$w_data['format'] = $this->formato_credito;

		$qstr=$this->waltook_build_messagequery( $w_data );
		$resp=@$this->waltook_api_connect("q=credit&client_id=".$this->_client_id."&w_qstr=".urlencode($qstr));

		if($resp)
		{
			return $this->decrypt( $resp );
		} else {
			return false;
		}
    }

   /**
	* Funcion para obtener el credito de mensajes disponibles para enviar y recibir
    * @return array Array con entrada y salida de cantidad de mensajes
	*/
	public function getCreditoMensajes() {

		$resp = $this->getCredito();
		if( $resp )
		{

			if( $this->_formato_credito == 1 ) {        // Formato 1 -> Serializado
				$resp_data = unserialize( $resp );
			} else if( $this->_formato_credito == 2 ) { // Formato 2 -> JSON
				$resp_data = json_decode( $resp );
			} else {                                   // Formato 3 -> XML
				$resp_data = simplexml_load_string( $resp );
			}
			if( $resp_data['status'] != 1 ) {
				throw new NotFoundException( "El servidor Waltook contesto: ".$resp_data['status'].'-'.$this->mensajes_error[$resp_data['status']] );
			}
			if( !$resp_data['credit']['out'] )
			{
				throw new NotFoundException( 'Error de desencriptacion del mensaje de credito' );
			}
		} else {
			throw new NotFoundException( 'Error de conexion con el servidor' );
		}
		return array(
			'entrada' => $resp_data['credit']['in'],
			'salida' => $resp_data['credit']['out'],
			'status' => $this->mensajes_error[$resp_data['status']]
		);
	}


   /**
    * Funcion de encriptacion de contenido
    * @param mixed $input Elemento a encriptar
    * @return mixed Variable encriptada
    */
	private function encrypt( $input ) {
		$inputlen = strlen($input);
	  	$inputchr1 = "";
		$inputchr2 = "";

		$i = 0;
		while ($i <= $inputlen)
		{
			if ($i < $inputlen )
			{
				$inputchr1 .= chr(ord(substr($input, $i, 1))+$this->_key{$i%32});
			}
			$i++;
	  	}

	  	$i = 0;
		while ($i <= $inputlen)
	  	{
			if ($i < $inputlen )
			{
				$inputchr2 .= chr(ord(substr($inputchr1, $i, 1))+$this->_key{($i%10)+10});
			}
			$i++;
	  	}
		return base64_encode($inputchr2);
	}

	/**
	 * Función de desencriptacion de datos
	 * @param mixed $input Cadena encriptada
	 * @return cadena desencriptada
	 */
	private function decrypt($input)
	{
	 	$input = base64_decode($input);
		$inputlen = strlen($input);
		$inputchr1 = "";
		$inputchr2 = "";

	 	$i = 0;
		while ($i <= $inputlen)
		{
			if ($i < $inputlen)
			{
				$inputchr1 .= chr(ord(substr($input, $i,1))-$this->_key{($i%10)+10});
			}
			$i++;
	  	}


	 	$i = 0;
		while ($i <= $inputlen)
	  	{
			if ($i < $inputlen)
			{
				$inputchr2 .= chr(ord(substr($inputchr1, $i,1))-$this->_key{$i%32});
			}
			$i++;
	  	}
	  	return $inputchr2;
	}

   /**
    * Funcion llamada como callback
    * Agregar la dirección /waltook/waltook/recibir como callback en la api
    * @return Array Array con los datos o el error
    */
	public function recibir() {
		if( $this->request->isGet() ) {
			debug( $this->request->data );
			$w_qstr = $this->decrypt( $this->request->data );
			@parse_str($w_qstr, $w_data );
			if( is_array( $w_data ) ) {
				$w_data['status'] = 1;
				$w_data['message'] = $this->mensajes_error[$w_data['status']];
				return $w_data;
			} else {
				return array( 'error' => 3, 'message' => $this->mensajes_error[3] );
			}
		} else {
			return array( "error" => 5, 'message' => $this->mensajes_error[5] );
		}
	}

   /**
    * Función interna par construir la url de envios y la encriptacion
    * @param mixed $w_data Datos a encritar y enviar
    * @return string Datos encriptados
    */
	private function waltook_build_messagequery( $w_data )
	{
		$w_qstr=http_build_query($w_data); // Construye
		$w_qstr=$this->encrypt($w_qstr); //Encripta
		return $w_qstr;
	}

   /**
    * Obtener la lista de mensajes
    * @param mixed $status Estado de los mensajes. 1=leidos, 0=no leidos, null = todos.
    * @param integer $tid Identificador de los mensajes
    * @param integer $format Formato de devolucion. 1=Serializado, 2=JSON, 3=XML
    * @param boolean $flag Bandera de no leido. Si se pasa un 0, los mensajes obtenidos se pasan a leidos. Si se coloca como 1, se mantiene el estado de "No leido"
    * @returns Lista de mensajes o falso en caso de error
    */
    public function obtenerListaMensajes( $status = null, $tid = 0, $format = 1, $flag = 1 )
	{
		$tid=intval($tid);
		$w_data = array(
			'client_id' => $this->client_id,
			'format' => $format,
			'tid' => $tid,
			'status' => $status,
			'flag' => $flag
		);

		$qstr=$this->waltook_build_messagequery($w_data);
		$resp=@$this->waltook_api_connect("q=get&client_id=".$this->client_id."&w_qstr=".urlencode($qstr));
		if($resp)
		{
		    $data = $this->decrypt( $resp );
            if( !isset($data['messages'] ) ) {
                throw new NotFoundException( 'No se encontró la variable de mensajes' );
            }

            $returns = array();
            $estados = array( 0 => "No leído", 1 => "Leido" );
            // Cada elemento del array tendrá que estar pasado a un elemento tipo CakePHP
            foreach( $data['messages'] as $mensaje ) {
                $mensaje['texto'] = $mensaje['txt'];
                unset( $mensaje['txt'] );
                $mensaje['estado_texto'] = $estados[$mensaje['status']];
                $returns[] = array( 'Sms' => $mensaje );
            }
            return $returns;
        } else {
            return false;
        }
	}

    /**
     * Callback llamado por el sistema para cuando nos llega un sms
     * Esta función se encarga de realizar todas las acciones correspondientes para generar el mensaje y dejarlo a disposición del controller
     * Se deberá implementar el método afterReciveMessage( $message = array() ) para realizar alguna acción con el mensaje recibido.
     * Si no está implementada la función el mensaje se loggeará en el sistema.
     */
    public function recibirMensaje() {

    }

	 /**
	  * Función que obtiene la lista de mensajes que hay en el servidor
      * @param $status integer Estado de los mensajes
      * @param $tid integer ?
      * @param $flag integer ?
      * @return Array Mensajes
	  */
	private function waltook_get_messages_array($status=null,$tid=0,$flag=1)
	{


		$tid=intval($tid);



		$resp=waltook_get_messages($status,$tid,1,$flag);

		if($resp)
		{
			$resp_data=unserialize($resp);

			if(!$resp_data['status'])
			{
				$resp_data=array();
				$resp_data['status']=3; // Error de lectura. No se pudo desencriptar.
			}
		}else
		{
			$resp_data=array();
			$resp_data['status']=2;// Error de conexión
		}
		return $resp_data;
	}


   /**
    * Función para conectar con el servidor de Waltook
    * @param mixed $data Datos a enviar
    * @throws NotImplementedException Si hubo algún error
    * @return Datos de resupesta
    */
	private function waltook_api_connect( $data ) {

		$sock = new HttpSocket();

		if( $this->method == "POST" ) {
		  $response = $sock->post( $this->_url, $data );
		} else {
		  $response = $sock->get ( $this->_url, $data );
		}
		if( $response->isOk() ) {
			$cuerpo = $response->body();
			if( substr_compare( $cuerpo, 'ERROR', 0 ) == 0 ) {
				foreach( $this->mensajes_error as $num => $mje ) {
					if( intval( substr( $cuerpo, 5, 6 ) ) == $num ) {
						throw new NotFoundException( "El servidor Waltook respondió con error: ".$num." ".$this->mensajes_error[$num] );
					}
				}
				throw new NotFoundException( "El servidor Waltook respondió con un error desconocido: ".$cuerpo );
			} else {
				return $response->body();
			}
		} else {
			throw new NotImplementedException( 'El sistema devolvió una consulta con error:' . $response->code.'<br />' );
		}
	}

 }
