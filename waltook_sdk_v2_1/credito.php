<?php

/*

WALTOOK SDK

Versión API 2.1


Ejemplo de envío de un mensaje de texto

Para más información, consulte el instructivo.

*/

//Funciones API Waltook
require("waltook_connect.php");


//Envía el mensaje y guarda el estado en la variable $status
$data=waltook_get_credit_array();

print_r($data);

//Toma el código de estado y verifica si se envió (Cod. 1) o si se generó un error (cualquier otro valor distinto de 1)
if($data['credit'])
{
	print "Mensajes de entrada: ".$data['credit']['in'];
	print "<br>";
	print "Mensajes de salida: ".$data['credit']['out'];
		
}else
{
	print "Error: ".$data['status'];	
}

?>
