<?php
require '../../modelo/modelo_usuario.php'; // Solo modelo_usuario, no modelo_docente aquí

$usuario = new Modelo_Usuario();

$idprofe = htmlspecialchars($_POST['id_usu'], ENT_QUOTES, 'UTF-8');
$rol = htmlspecialchars($_POST['rol_usu'], ENT_QUOTES, 'UTF-8');

$consulta = $usuario->Extraer_contracena($idprofe, $rol);

$data = json_encode($consulta);
if (count($consulta) > 0) {
    echo $data;
} else {
    echo 0;
}
?>

