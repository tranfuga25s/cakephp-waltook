<?php


/*

WALTOOK SDK

Versión API 2.1


*/


require_once("config.php");

//Función para la encriptación de datos
function waltook_encrypt($input)
{
 
	$key=WALTOOK_API_CLIENT_KEY;
	
  
	$inputlen = strlen($input);
  	
	$inputchr1 = "";
	$inputchr2 = "";
	
	
	$i = 0;
	
	while ($i <= $inputlen)
	{
		if ($i < $inputlen )
		{
			$inputchr1 .= chr(ord(substr($input, $i, 1))+$key{$i%32});
		}
		$i++;
  	}
  
  	$i = 0;
  	
	while ($i <= $inputlen)
  	{
		if ($i < $inputlen )
		{
			$inputchr2 .= chr(ord(substr($inputchr1, $i, 1))+$key{($i%10)+10});
		}
		$i++;
  	}
  
	return base64_encode($inputchr2);

  
}

//Función para la desencriptación de datos

function waltook_decrypt($input)
{

	$key=WALTOOK_API_CLIENT_KEY;
	
 	$input = base64_decode($input);
	
	$inputlen = strlen($input);
  
	$inputchr1 = "";
	$inputchr2 = "";
	
 	$i = 0;
 	
	while ($i <= $inputlen)
	{
		if ($i < $inputlen)
		{
			$inputchr1 .= chr(ord(substr($input, $i,1))-$key{($i%10)+10});
		}
		$i++;
  	}
  
  
 	$i = 0;
 	
	while ($i <= $inputlen)
  	{
		if ($i < $inputlen)
		{
			$inputchr2 .= chr(ord(substr($inputchr1, $i,1))-$key{$i%32});
		}
		$i++;
  	}

  	return $inputchr2;
  
}


//Toma el mensaje que se envió por GET (Callback)
function waltook_capture_message()
{
	
	
  
	$w_qstr=$_GET['w_qstr']; //Toma la información encriptada proveniente del servidor Waltook
	
	if(!$_wqstr)
	{
	
		$w_qstr=waltook_decrypt($w_qstr); // Desencripta
		
		@parse_str($w_qstr, $w_data); // Procesa los datos y los guarda en el array $w_data
		if(is_array($w_data))
		{
			$w_data['status']=1;
			return $w_data;
	
		}else
		{
			return array("error"=>3); // Error de lectura. No se pudo desencriptar.
		}
	}else
	{
		return array("error"=>5);	// 	No se recibieron datos.
	}
	
}


//Envía mensaje(s)
function waltook_send_message($w_data)
{
	
	
	
	if(is_array($w_data))
	{
	
		$w_data['client_id']=WALTOOK_API_CLIENT_ID;
		
		
	
		$qstr=waltook_build_messagequery($w_data);
		
		
		$data="q=send&client_id=".WALTOOK_API_CLIENT_ID."&w_qstr=".urlencode($qstr);
		
		$resp_qstr=@waltook_api_connect($data);
		
		
		if($resp_qstr)
		{
			$resp_qstr=waltook_decrypt($resp_qstr); // Desencripta
			
			
			
			@parse_str($resp_qstr, $resp_data); // Procesa los datos y los guarda en el array $w_data
		
			if($resp_data['status'])
			{
				
				return $resp_data['status'];
				
			}else
			{
				return 3; // Error de lectura. No se pudo desencriptar.
			}
		}else
		{
			return 2; // Error de conexión
		}
	}else
	{
		return 7;	// Faltan parámetros
	}
}

function waltook_build_messagequery($w_data)
{
	
	
	
	$w_qstr=http_build_query($w_data); // Construye 
	
	
	
	$w_qstr=waltook_encrypt($w_qstr); //Encripta
	
	
	return $w_qstr;
	
}

//Consulta por mensajes en el servidor
function waltook_get_messages($status=null,$tid=0,$format=1,$flag=1)
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
		
		return waltook_decrypt($resp);
		
	}else
	{
		return false; 
	}
	
}


function waltook_get_messages_array($status=null,$tid=0,$flag=1)
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


//Consulta por el crédito de mensajes disponibles
function waltook_get_credit($format=1)
{
	
	

	
	//Construye el array con los parámetros de la consulta
	
	$w_data['client_id']=WALTOOK_API_CLIENT_ID;
	
	$w_data['format']=$format;
	
	
	
	$qstr=waltook_build_messagequery($w_data);
		
	
		
	$resp=@waltook_api_connect("q=credit&client_id=".WALTOOK_API_CLIENT_ID."&w_qstr=".urlencode($qstr));
	
	
	if($resp)
	{
		
		return waltook_decrypt($resp);
		
	}else
	{
		return false; 
	}
	
}

function waltook_get_credit_array()
{
	
	
	$resp=waltook_get_credit(1);
	
			
	if($resp)
	{
		
		
			
		$resp_data=unserialize($resp);
		
		
		if(!$resp_data['credit'])
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


//Función para conectar con el servidor de Waltook
function waltook_api_connect($data)
{
	
	
	$url="http://api.waltook.com/index.php";
	
	
	
	if(WALTOOK_API_METHOD=="POST")
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

function waltook_reply_ok()
{
	
	
	print "OK";
	
}


function waltook_reply_error()
{
	
	
	print "ERROR";
	
}


?>