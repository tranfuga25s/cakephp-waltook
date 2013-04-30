<?php

/*

WALTOOK SDK

Versión API 1.2


Ejemplo de aplicación del método de obtención de mensajes (Método 2)

Para más información, consulte el instructivo.


En este ejemplo, su servidor consultará a Waltook por los mensajes almacenados allí, los obtendrá en formato JSON y lo imprimirá en pantalla tal como lo recibe.

*/


require("waltook_connect.php");

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Consultar en JSON</title>
</head><body>

<p>Respuesta en JSON
<pre>
<?php




//Seleccionar todos los mensajes
$a=waltook_get_messages(null,0,2,0); 

print nl2br(htmlentities($a));


 
?>
</pre>

</body></html>