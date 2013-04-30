<?php

/*

WALTOOK SDK

Versión API 1.2


Ejemplo de aplicación del método de obtención de mensajes (Método 2)

Para más información, consulte el instructivo.


En este ejemplo, su servidor consultará a Waltook por los mensajes almacenados allí, los obtendrá en formato ARRAY y los mostrará en una tabla.

*/

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Consultar en Tabla</title>
</head><body><?php


//Funciones API Waltook
require("waltook_connect.php");

//Seleccionar todos los mensajes (leídos y no leídos) y no cambiar el estado de los no leídos
$a=waltook_get_messages_array(null,0,0); 

if($a['status']==1)
{

//Si hay más de un mensaje, mostrarlos en la tabla
if(count($a['messages'])>0)
{
	$estados=array(0=>"No leído",1=>"Leído");
	print "<table>";
	print "<tr><td>Teléfono</td><td>Mensaje</td><td>Timestamp</td><td>Estado</td></tr>";	
	foreach($a['messages'] as $m)
	{
		print "<tr><td>".$m['uid']."</td><td>".$m['txt']."</td><td>".$m['timestamp']."</td><td>".$estados[$m['status']]."</td></tr>";			
	}

	print "</table>";	
}else
{
	print "No hay mensajes";	
}
}else
{
	print "ERROR: ".$a['status'];	
}
 
?></body></html>