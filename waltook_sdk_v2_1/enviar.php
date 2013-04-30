<?php

/*

WALTOOK SDK

Versión API 2.1


Ejemplo de envío de un mensaje de texto

Para más información, consulte el instructivo.

*/

//Funciones API Waltook
require("waltook_connect.php");

//Parámetros del mensaje a enviar
$w_data['uid']="123456789,123456789"; //REEMPLACE POR SU NÚMERO DE TELEFONO
$w_data['txt']="mensaje"; //REMPLACE POR EL TEXTO DE SU MENSAJE
$w_data['tid']=0;


//Envía el mensaje y guarda el estado en la variable $status
$status=waltook_send_message($w_data);


//Toma el código de estado y verifica si se envió (Cod. 1) o si se generó un error (cualquier otro valor distinto de 1)
if($status==1)
{
	print "Mensaje enviado correctamente";	
}else
{
	print "Error al enviar el mensaje. Cód: ".$status;	
}

?>
