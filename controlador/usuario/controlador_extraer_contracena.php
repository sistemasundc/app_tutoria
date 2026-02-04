<?php
error_log("ID USUARIO: " . $_POST['id_usu']);
error_log("ROL: " . $_POST['rol_usu']);
require '../../modelo/modelo_usuario.php';

$MU = new Modelo_Usuario();

$usu_id = htmlspecialchars($_POST['id_usu'], ENT_QUOTES, 'UTF-8');
$rol_usu = htmlspecialchars($_POST['rol_usu'], ENT_QUOTES, 'UTF-8');


$consulta = $MU->Extraer_contracena($usu_id, $rol_usu);

if ($consulta && count($consulta) > 0) {
    echo json_encode($consulta);
} else {
    echo json_encode([]); // JSON vacío en lugar de "0"
}
?>
