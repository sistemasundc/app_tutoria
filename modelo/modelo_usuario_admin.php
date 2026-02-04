<?php
require_once("modelo_conexion.php");

class Modelo_Usuario_Admin extends conexion {

  public function Listar_Usuarios() {
    $c = $this->conectar();

    $sql = "SELECT u.id_usuario, u.username, u.nombres, u.apaterno, u.amaterno,
                   u.cor_inst, u.cel_pa, u.rol_id, u.id_car, u.estado,
                   r.nombre AS rol,
                   ca.nom_car AS carrera,
                   CONCAT_WS(' ', u.nombres, u.apaterno, u.amaterno) AS nombre_completo
            FROM tutoria_usuario u
            LEFT JOIN tutoria_rol r ON r.id_rol = u.rol_id
            LEFT JOIN carrera ca ON ca.id_car = u.id_car
            ORDER BY u.id_usuario DESC";

    $res = mysqli_query($c, $sql);

    $ar = [];
    while ($row = mysqli_fetch_assoc($res)) {

      $btnEstado = ($row["estado"] === "ACTIVO")
        ? '<button class="btn btn-xs btn-danger" title="Desactivar" onclick="cambiarEstado('.$row["id_usuario"].',\'INACTIVO\')"><i class="fa fa-ban"></i></button>'
        : '<button class="btn btn-xs btn-success" title="Activar" onclick="cambiarEstado('.$row["id_usuario"].',\'ACTIVO\')"><i class="fa fa-check"></i></button>';

      $row["acciones"] = '
        <button class="btn btn-xs btn-primary" title="Editar" onclick="editarUsuario('.$row["id_usuario"].')">
          <i class="fa fa-edit"></i>
        </button>
        '.$btnEstado;

      $ar[] = $row;
    }

    return $ar;
  }

  public function Obtener_Usuario($id) {
    $c = $this->conectar();
    $id = (int)$id;

    $sql = "SELECT id_usuario, username, nombres, apaterno, amaterno, cor_inst, cel_pa, rol_id, id_car, estado
            FROM tutoria_usuario
            WHERE id_usuario = $id";

    $res = mysqli_query($c, $sql);
    return mysqli_fetch_assoc($res);
  }

  public function Guardar_Usuario($d) {
    $c = $this->conectar();

    $id_usuario = $d["id_usuario"] ?? "";
    $username   = mysqli_real_escape_string($c, $d["username"] ?? "");
    $nombres    = mysqli_real_escape_string($c, $d["nombres"] ?? "");
    $apaterno   = mysqli_real_escape_string($c, $d["apaterno"] ?? "");
    $amaterno   = mysqli_real_escape_string($c, $d["amaterno"] ?? "");
    $cor_inst   = mysqli_real_escape_string($c, $d["cor_inst"] ?? "");
    $cel_pa     = mysqli_real_escape_string($c, $d["cel_pa"] ?? "");
    $rol_id     = (int)($d["rol_id"] ?? 0);
    $id_car     = (int)($d["id_car"] ?? 0);
    $estado     = mysqli_real_escape_string($c, $d["estado"] ?? "ACTIVO");

    if ($id_usuario == "") {
      $sql = "INSERT INTO tutoria_usuario
              (username, nombres, apaterno, amaterno, cor_inst, cel_pa, rol_id, id_car, estado)
              VALUES
              ('$username','$nombres','$apaterno','$amaterno','$cor_inst','$cel_pa',$rol_id,$id_car,'$estado')";
    } else {
      $id_usuario = (int)$id_usuario;
      $sql = "UPDATE tutoria_usuario SET
                username='$username',
                nombres='$nombres',
                apaterno='$apaterno',
                amaterno='$amaterno',
                cor_inst='$cor_inst',
                cel_pa='$cel_pa',
                rol_id=$rol_id,
                id_car=$id_car,
                estado='$estado'
              WHERE id_usuario=$id_usuario";
    }

    mysqli_query($c, $sql);
  }

  public function Cambiar_Estado($id, $estado) {
    $c = $this->conectar();
    $id = (int)$id;
    $estado = mysqli_real_escape_string($c, $estado);

    $sql = "UPDATE tutoria_usuario SET estado='$estado' WHERE id_usuario=$id";
    mysqli_query($c, $sql);
  }

  public function Combo_Roles() {
    $c = $this->conectar();
    $sql = "SELECT id_rol, nombre FROM tutoria_rol ORDER BY nombre";
    $res = mysqli_query($c, $sql);

    $html = '<option value="">Seleccione</option>';
    while ($r = mysqli_fetch_assoc($res)) {
      $html .= '<option value="'.$r["id_rol"].'">'.$r["nombre"].'</option>';
    }
    return $html;
  }

  public function Combo_Carreras() {
    $c = $this->conectar();
    $sql = "SELECT id_car, nom_car FROM carrera ORDER BY nom_car";
    $res = mysqli_query($c, $sql);

    $html = '<option value="">Seleccione</option>';
    while ($r = mysqli_fetch_assoc($res)) {
      $html .= '<option value="'.$r["id_car"].'">'.$r["nom_car"].'</option>';
    }
    return $html;
  }
}
