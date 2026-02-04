<?php
session_start();
require '../../modelo/modelo_docente.php';
$docente = new Docente();

$id_session = htmlspecialchars($_POST['id'], ENT_QUOTES, 'UTF-8');
$consulta = $docente->ListaAsistenciaAlumnos($id_session);
echo json_encode($consulta, JSON_UNESCAPED_UNICODE);
/* session_start();
require '../../modelo/modelo_docente.php';
$docente = new Docente();

$id_session = htmlspecialchars($_POST['id'], ENT_QUOTES, 'UTF-8');

// Llamada al método público por defecto
$consulta = $docent->ListaAsistenciaAlumnos($id_session);
echo json_encode($consulta);  */
