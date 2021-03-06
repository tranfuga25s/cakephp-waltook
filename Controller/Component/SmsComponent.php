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
     * Codigo configurado de respuesta de mensajes
     */
     private $_request_code = null;

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

    private $_limite_caracteres = 150;

    private $_date_format = 'd-m-Y H:i:s';

    private $controller = null;

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
	public function initialize( Controller $controller ) {
		// Cargo la configuracion
		$this->controller = $controller;
		if( Configure::read( 'Waltoolk.client_id' ) == false ) {
			//throw new NotImplementedException( 'El sistema de Waltook no está configurado' );
		} else {
    		$this->_key = Configure::read( 'Waltoolk.key' );
    		$this->_client_id = Configure::read( 'Waltoolk.client_id' );
    		$this->_method = Configure::read( 'Waltoolk.method' );
            $this->_request_code = Configure::read( 'Waltoolk.request_code' );
        }
	}

 	public function parametros( $cliente_id, $key, $method, $request_code = null ) {
		$this->_client_id = $cliente_id;
		$this->_key = $key;
		$this->_method = $method;
        $this->_request_code = $request_code;
 	}

	public function getClientId() { return $this->_client_id; }
	public function getKey() { return $this->_key; }
	public function getMethod() { return $this->_method; }
	public function getUrl() { return $this->_url; }
    public function getRequestCode() { return $this->_request_code; }
    public function devolucionCorrecta() { print "OK"; }
    public function devolucionIncorrecta() { print "ERROR"; }

    public static function habilitado() {
        // Verifico que esté configurado
        if( ! Configure::check( 'Waltoolk.client_id' ) || ! Configure::check( 'Waltoolk.key' ) ) {
            return false;
        }
        return true;
    }

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

		$qstr=$this->waltook_build_messagequery($w_data);

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
			if( !array_key_exists( 'credit', $resp_data ) )
			{
			    $this->log( "Error de desencriptacion de mensajes de waltook: ".print_r( $resp_data, true ) );
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
    * Agregar la dirección del controlador que recibirá los mensajes como callback en la api
    * @return Array Array con los datos o el error
    */
	public function recibir() {
		if( $this->controller->request->is( 'get' ) ) {
		    $this->controller->autoRender = false;
            $this->controller->layout = false;
            $this->controller->RequestHandler->respondAs( 'text' );
		    $datos = $this->controller->request->query;
            if( is_array( $this->controller->request->query ) ) {
                $datos = array_pop( $this->controller->request->query );
            }
            $w_qstr = $this->decrypt( $datos );
			@parse_str( $w_qstr, $w_data );
            $this->log( "Nuevo Sms Recibido" );
            $this->log( print_r( $w_data , true ) );
			if( is_array( $w_data ) ) {
				$w_data['status'] = 1;
				$w_data['message'] = $this->mensajes_error[$w_data['status']];
                $w_data['fechahora'] = date( $this->_date_format, $w_data['timestamp'] );
				$mensaje = array( 'Sms' => $w_data );
			} else {
                $this->Sms->devolucionIncorrecta();
       		    $this->log( "Error de recepción de sms por callback".print_r( array( 'error' => 3, 'message' => $this->mensajes_error[3] ), true ) );
			}
		} else {
			$this->log( "Error de recepción de sms por callback". print_r(  array( "error" => 5, 'message' => $this->mensajes_error[5] ), true ) );
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
			'client_id' => $this->_client_id,
			'format' => $format,
			'tid' => $tid,
			'status' => $status,
			'flag' => $flag
		);

		$qstr=$this->waltook_build_messagequery($w_data);
		$resp=@$this->waltook_api_connect("q=get&client_id=".$this->_client_id."&w_qstr=".urlencode($qstr));
		if($resp)
		{
		    $data = $this->decrypt( $resp );
            if( $format == 1 ) {        // Formato 1 -> Serializado
                $data = unserialize( $data );
            } else if( $format == 2 ) { // Formato 2 -> JSON
                $data = json_decode( $data );
            } else {                    // Formato 3 -> XML
                $data = simplexml_load_string( $data );
            }
            if( ! array_key_exists( 'messages', $data ) ) {
                throw new NotFoundException( 'No se encontró la variable de mensajes' );
            }

            $returns = array();
            $estados = array( 0 => "No leído", 1 => "Leido" );
            if( count( $data['messages'] ) > 0 ) {
                // Cada elemento del array tendrá que estar pasado a un elemento tipo CakePHP
                foreach( $data['messages'] as $mensaje ) {
                    $mensaje['texto'] = $mensaje['txt'];
                    unset( $mensaje['txt'] );
                    $mensaje['estado_texto'] = $estados[$mensaje['status']];
                    $mensaje['fechahora'] = date(  $this->_date_format, $mensaje['timestamp'] );
                    $returns[] = array( 'Sms' => $mensaje );
                }
            }
            return $returns;
        } else {
            return false;
        }
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

    /**
     * Función utilizada para configurar el sistema correctamente.
     * @param cliente_id Identificador del cliente.
     * @param key Clave de encriptacion
     * @param metodo
     * @param codigo Codigo de identificacion
     */
     public function configurarServicio( $cliente_id = null, $key = '', $method = 'GET', $codigo = null ) {

         if( is_null( $cliente_id ) || strlen( $key ) == 0 || is_null( $codigo ) ) {
            return false;
         }

         // Configuro el servicio con los parametros pasados e intento obtener el saldo
         // Si funciona veo de configurar los datos pasados dentro del sistema
         // escribiendo el archivo bootstrap.php
         $dir = new Folder( App::pluginPath('Waltook'), false );
         $bootstrap = new File( $dir->pwd().'Config'.DS.'bootstrap.php', true, 0777 );
         if( $bootstrap->open( 'w', true ) ) {
             $data  = "<?php \n";
             $data .= " /** CONFIGURACION PARA WALTOOK **/ \n";
             $data .= "Configure::write( 'Waltoolk.client_id', ".$cliente_id."  ); \n";
             $data .= "Configure::write( 'Waltoolk.key', '".$key."' ); \n";
             $data .= "Configure::write( 'Waltoolk.method', '".$method."' ); \n";
             $data .= "Configure::write( 'Waltoolk.request_code', '".$codigo."' ); \n";
             $data .= "// Generado: ".date( 'd-m-Y H:i:s' )." \n";
             if( $bootstrap->write( $data, 'w', true ) ) {
                 $bootstrap->close();
                 // Cambio los permisos a solo lectura
                 $dir->chmod( $dir->pwd().DS.'Config', 0522, false );
                 return true;
             } else {
                 $this->log( "No se pudo escribir el archivo" );
             }
         } else {
             die( "No se pudo abrir el archivo para escritura" );
         }
         return;
     }

 }
