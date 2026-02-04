<?php
// reportes/admin_nee.php
if (session_status() === PHP_SESSION_NONE) session_start();

$rolesPermitidos = ['COORDINADOR GENERAL DE TUTORIA','VICEPRESIDENCIA ACADEMICA','SUPERVISION'];
if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'] ?? '', $rolesPermitidos)) {
  die('Acceso denegado');
}
?>
<style>
        h1 {
        width: 100%;
        background-color: #2c3e50;
        color: white;
        padding: 15px 20px;
        font-size: 20px;
        font-weight: bold;
        text-align: center;
        margin-top: 50px;
    }
</style>
<section class="content-header">
  <center><h1><i class="fa fa-wheelchair"></i> ESTUDIANTES CON NECESIDADES EDUCATIVAS ESPECIALES / CONADIS</h1></center>
</section>

<section class="content">

  <div class="box">
    <div class="box-header with-border">
      <button class="btn btn-primary" data-toggle="modal" data-target="#modalNEE">
        <i class="fa fa-plus"></i> Agregar estudiante NEE
      </button>
    </div>

    <div class="box-body">
      <table id="tablaNEE" class="table table-bordered table-striped" style="width:100%">
        <thead>
          <tr>
            <th>ID</th>
            <th>Estudiante</th>
            <th>Carrera</th>
            <th>Correo</th>
            <th>Ciclo</th>
            <th>Semestre</th>
            <th>Notas</th>
            <th>Asistencia</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

</section>

<!-- MODAL (Bootstrap 3) -->
<div class="modal fade" id="modalNEE" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form id="formNEE" class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span>&times;</span></button>
        <h4 class="modal-title"><b>Agregar estudiante NEE</b></h4>
      </div>

      <div class="modal-body">
        <div class="row">
          <div class="col-md-8">
            <label>Buscar estudiante (DNI / Código / Apellidos / Nombres)</label>
            <input type="text" class="form-control" id="q" placeholder="Ej: 73022346 / 2311050294 / ROJAS..." autocomplete="off">
          </div>
          <div class="col-md-4">
            <label>&nbsp;</label>
            <button type="button" class="btn btn-default btn-block" id="btnBuscar">
              <i class="fa fa-search"></i> Buscar
            </button>
          </div>
        </div>

        <hr>

        <div class="table-responsive">
          <table class="table table-bordered table-hover" id="tablaBusqueda">
            <thead>
              <tr>
                <th>ID</th>
                <th>Estudiante</th>
                <th>DNI</th>
                <th>Código</th>
                <th>Correo</th>
                <th>Ciclo</th>
                <th>Semestre</th>
                <th>Carrera</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="9" class="text-center text-muted">Realiza una búsqueda…</td></tr>
            </tbody>
          </table>
        </div>

        <!-- hidden -->
        <input type="hidden" name="id_estu" id="id_estu">
        <input type="hidden" name="dni" id="dni">
        <input type="hidden" name="codigo" id="codigo">
        <input type="hidden" name="email" id="email">
        <input type="hidden" name="programa" id="programa">
        <input type="hidden" name="ape_paterno" id="ape_paterno">
        <input type="hidden" name="ape_materno" id="ape_materno">
        <input type="hidden" name="nombres" id="nombres">

        <div class="alert alert-info" id="seleccionInfo" style="display:none;margin-top:10px;"></div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal">Cerrar</button>
        <button type="submit" class="btn btn-primary" id="btnGuardar" disabled>Guardar NEE</button>
      </div>
    </form>
  </div>
</div>
<?php include "vistas/modales/modal_notas_nee.php"; ?>
<?php include "vistas/modales/modal_asistencia_nee.php"; ?>

