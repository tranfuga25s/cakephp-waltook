<?php
/*

WALTOOK SDK

Versión API 1.2


Ejemplo de aplicación del método de obtención de mensajes (Método 1: Callback)

Este script deberá colocarlo en una URL accesible y se convertirá en su CALLBACK URL

Por ejemplo:
http://SU_SERVIDOR/SU_CARPETA/recibir.php

Ésta será la URL que deberá ingresar en su panel de servicios Waltook, en el sector de API.

Waltook enviará los datos a este script de cada mensaje que reciba de los teléfonos móviles.

Para más información, consulte el instructivo.


En este ejemplo, por cada mensaje que se obtiene, se genera un archivo de texto con el contenido del SMS recibido.

*/



//Funciones API Waltook
require("waltook_connect.php");



//Toma la variable que envió Waltook, la desencripta, la procesa y separa los datos del mensaje almacenándolos en un array.
$wd=waltook_capture_message();

//Si el array contiene el texto del mensaje, lo imprime
if($wd['txt'])
{
	//Se arma un texto de ejemplo con los datos del mensaje
	
	$mensaje="Mensaje de: ".$wd['uid']."\n";
	$mensaje.=$wd['txt'];
	
	//Se guarda en un archivo de texto el SMS recibido colocando la fecha actual como nombre de archivo
	
	$archivo = fopen("recibidos/".date("Y_m_d_H_i_s").".txt","w");
	fwrite($archivo,$mensaje);
	fclose($archivo);
	
	//Imprime el mensaje de confirmación
	waltook_reply_ok();
}else
{
	
	//imprime el mensaje de error, waltook considerará que el mensaje no está leído y volverá a intentar enviarlo más tarde.
	waltook_reply_error();
}

?>