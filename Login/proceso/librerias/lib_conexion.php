<?php
function conectarbd()
{
	global $bd;
	$bd = mysqli_connect("localhost", "usr_sivireno", "S1v1r3n0@");
	mysqli_select_db($bd,"dbsivireno");
	mysqli_set_charset($bd,'utf8');
}
function desconectarbd()
{
	global $bd;	
	$bd = mysqli_connect("localhost", "usr_sivireno", "S1v1r3n0@");
	mysqli_close($bd);
}
//Variables
//$url='https://sistemas.undc.edu.pe/boletasdepago';	
?>
