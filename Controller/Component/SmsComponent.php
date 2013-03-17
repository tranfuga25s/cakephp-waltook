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
	private $key = null;
	
   /**
    * Identificador de cliente
    */	
	private $client_id = null;
	
   /**
    * Método de comunicación con el sistema
    */	
	private $method = null;
	
   /**
    * Dirección de comunicación con la API de Waltook
    * @var mixed $url
    */	
	private $url = "http://api.waltook.com/index.php";
	
   /**
    * Numero de respuestas
    */
    private $tid = 0;
	
   /**
    * Formato de pedido de credito - Seteado fijo en la funcion de solicitud en el api
    * Posibles parametros:
    *  1 - Serializado
    *  2 - json
    *  3 - xml
    */	
    private $formato_credito = 2;
	
	
	private $mensajes_error = array(
		1 => 'Transacción exitosa',
		2 => 'Error de conexión con api.woltook.com',
		3 => 'Error de lectura. No se pudo desencriptar los datos',
		5 => 'Error de lectura. No se recibió ningún dato desde el servidor.',
		7 => 'Faltan parámetros',
		8 => 'Mensaje original TID no válido',
		9 => 'Número de teléfono no valido. Solo números de argentina, con prefijo DNN sin 0 o 15',
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
		$this->key = Configure::read( 'Waltoolk.key' );
		$this->client_id = Configure::read( 'Waltoolk.client_id' );
		$this->method = Configure::read( 'Waltoolk.method' );
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
			'tid' => $this->numero_propio,
			'client_id' => $this->cliente_id
		);
		
		$qstr=waltook_build_messagequery($w_data);
			
		$data = "q=send&client_id=".$this->cliente_id."&w_qstr=".urlencode( $qstr );
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
        //Construye el array con los parámetros de la consulta
		$w_data['client_id']=$this->cliente_id;
		$w_data['format'] = $this->formato_credito;
		
		$qstr=$this->waltook_build_messagequery($w_data);
		$resp=@$this->waltook_api_connect("q=credit&client_id=".$this->cliente_id."&w_qstr=".urlencode($qstr));
		
		if($resp)
		{
			return $this->decrypt($resp);
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
		debug( $resp );
		if($resp)
		{
			if( $this->formato_credito == 1 ) {        // Formato 1 -> Serializado
				$resp_data = unserialize( $resp );
			} else if( $this->formato_credito == 2 ) { // Formato 2 -> JSON
				$resp_data = json_decode( $resp );
			} else {                                   // Formato 3 -> XML
				$resp_data = simplexml_load_string( $resp );
			}
			if(!$resp_data['credit']['out'])
			{
				//$resp_data=array();
				//$resp_data['status']=3; // Error de lectura. No se pudo desencriptar.
				throw new NotFoundException( 'Error de desencriptacion del mensaje de credito' );
			}
		} else {
			//$resp_data=array();
			//$resp_data['status']=2;// Error de conexión
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
				$inputchr1 .= chr(ord(substr($input, $i, 1))+$this->key{$i%32});
			}
			$i++;
	  	}
	  
	  	$i = 0;
		while ($i <= $inputlen)
	  	{
			if ($i < $inputlen )
			{
				$inputchr2 .= chr(ord(substr($inputchr1, $i, 1))+$this->key{($i%10)+10});
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
				$inputchr1 .= chr(ord(substr($input, $i,1))-$this->key{($i%10)+10});
			}
			$i++;
	  	}
	  
	  
	 	$i = 0;
		while ($i <= $inputlen)
	  	{
			if ($i < $inputlen)
			{
				$inputchr2 .= chr(ord(substr($inputchr1, $i,1))-$this->key{$i%32});
			}
			$i++;
	  	}
	  	return $inputchr2;
	}
	

	 /**
	  * Funciones Waltook -  API
	  */	
	//Toma el mensaje que se envió por GET (Callback)
	function waltook_capture_message()
	{
		$w_qstr=$_GET['w_qstr']; //Toma la información encriptada proveniente del servidor Waltook
		
		if(!$_wqstr)
		{
		
			$w_qstr=$this->decrypt($w_qstr); // Desencripta
			
			@parse_str($w_qstr, $w_data); // Procesa los datos y los guarda en el array $w_data
			if(is_array($w_data))
			{
				$w_data['status']=1;
				return $w_data;
		
			}else
			{
				return array( "error" => 3 ); // Error de lectura. No se pudo desencriptar.
			}
		}else {
			return array( "error" => 5 );	// 	No se recibieron datos.
		}
		
	}
	
   /**
    * Función interna
    */	
	private function waltook_build_messagequery( $w_data )
	{
		$w_qstr=http_build_query($w_data); // Construye		
		$w_qstr=$this->encrypt($w_qstr); //Encripta		
		return $w_qstr;		
	}
	
	//Consulta por mensajes en el servidor
	private function waltook_get_messages($status=null,$tid=0,$format=1,$flag=1)
	{
		
		
		$tid=intval($tid);
		
		//Construye el array con los parámetros de la consulta
		
		$w_data['client_id']=WALTOOK_API_CLIENT_ID;
		
		$w_data['format']=$format;
		
		$w_data['tid']=$tid;
		
		$w_data['status']=$status;
		
		$w_data['flag']=$flag;
		
		
		
		$qstr=waltook_build_messagequery($w_data);
			
		
			
		$resp=@waltook_api_connect("q=get&client_id=".WALTOOK_API_CLIENT_ID."&w_qstr=".urlencode($qstr));
		
		
		if($resp)
		{
			
			return $this->decrypt($resp);
			
		}else
		{
			return false; 
		}
		
	}
	
	
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
    */	
	private function waltook_api_connect( $data )
	{
	
	
	$url="http://api.waltook.com/index.php";
	
	
	
	if($this->method=="POST")
	{
	
	
	  $params = array('http' => array( 
	  'method' => 'POST', 
	  'content' => $data 
	  )); 
	  
	  
	  $ctx = stream_context_create($params); 
	  
	  $fp = @fopen($url, 'rb', false, $ctx); 
	  
	  if($fp)
	  {
		  $response = @stream_get_contents($fp); 
	  }
	  return $response; 	
	
	}else
	{
		return file_get_contents($url."?".$data);
		
	}
	}
 	
 }
