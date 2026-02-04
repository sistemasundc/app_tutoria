<?php
header('Content-Type: application/json; charset=utf-8');
require_once("../../modelo/modelo_usuario_admin.php");

$MU = new Modelo_Usuario_Admin();
$accion = $_POST["accion"] ?? "";

try {
  switch ($accion) {

    case "listar":
      echo json_encode(["data" => $MU->Listar_Usuarios()]);
      break;

    case "obtener":
      $id = (int)($_POST["id_usuario"] ?? 0);
      echo json_encode($MU->Obtener_Usuario($id));
      break;

    case "guardar":
      $MU->Guardar_Usuario($_POST);
      echo json_encode(["status" => "ok", "msg" => "Usuario guardado correctamente"]);
      break;

    case "estado":
      $id = (int)($_POST["id_usuario"] ?? 0);
      $estado = $_POST["estado"] ?? "INACTIVO";
      $MU->Cambiar_Estado($id, $estado);
      echo json_encode(["status" => "ok", "msg" => "Estado actualizado"]);
      break;

    case "roles":
      // OJO: roles/carreras devuelven HTML, no JSON
      header('Content-Type: text/html; charset=utf-8');
      echo $MU->Combo_Roles();
      break;

    case "carreras":
      header('Content-Type: text/html; charset=utf-8');
      echo $MU->Combo_Carreras();
      break;

    default:
      echo json_encode(["status" => "error", "msg" => "Acción no válida"]);
      break;
  }
} catch (Throwable $e) {
  echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
}
