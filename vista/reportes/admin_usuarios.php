<?php
if (session_status() === PHP_SESSION_NONE) session_start();
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
  <h1><i class="fa fa-users"></i> Gestión de Usuarios</h1>
</section>

<section class="content">

  <div class="row">
    <div class="col-md-12">
      <button class="btn btn-success pull-right" onclick="abrirModalUsuario()">
        <i class="fa fa-user-plus"></i> Nuevo Usuario
      </button>
    </div>
  </div>
  <br>

  <div class="box box-primary">
    <div class="box-body">
      <table id="tablaUsuarios" class="table table-bordered table-striped" width="100%">
        <thead>
          <tr>
            <th>Usuario</th>
            <th>Nombres</th>
            <th>Correo Inst.</th>
            <th>Rol</th>
            <th>Carrera</th>
            <th>Estado</th>
            <th style="width:120px;">Acciones</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>

</section>

<!-- MODAL -->
<div class="modal fade" id="modalUsuario">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="formUsuario">
        <div class="modal-header bg-primary">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-user"></i> Usuario</h4>
        </div>

        <div class="modal-body">
          <input type="hidden" name="id_usuario" id="id_usuario">

          <div class="row">
            <div class="col-md-4">
              <label>Username</label>
              <input type="text" name="username" id="username" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label>Nombres</label>
              <input type="text" name="nombres" id="nombres" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label>Correo institucional</label>
              <input type="email" name="cor_inst" id="cor_inst" class="form-control" required>
            </div>
          </div>

          <div class="row" style="margin-top:10px;">
            <div class="col-md-4">
              <label>Apellido paterno</label>
              <input type="text" name="apaterno" id="apaterno" class="form-control">
            </div>
            <div class="col-md-4">
              <label>Apellido materno</label>
              <input type="text" name="amaterno" id="amaterno" class="form-control">
            </div>
            <div class="col-md-4">
              <label>Celular</label>
              <input type="text" name="cel_pa" id="cel_pa" class="form-control">
            </div>
          </div>

          <div class="row" style="margin-top:10px;">
            <div class="col-md-6">
              <label>Rol</label>
              <select name="rol_id" id="rol_id" class="form-control" required></select>
            </div>
            <div class="col-md-6">
              <label>Carrera</label>
              <select name="id_car" id="id_car" class="form-control" required></select>
            </div>
          </div>

          <div class="row" style="margin-top:10px;">
            <div class="col-md-4">
              <label>Estado</label>
              <select name="estado" id="estado" class="form-control">
                <option value="ACTIVO">ACTIVO</option>
                <option value="INACTIVO">INACTIVO</option>
              </select>
            </div>
          </div>

          <p class="text-muted" style="margin-top:10px;">
            * No se gestiona contraseña (login con Google).
          </p>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-save"></i> Guardar
          </button>
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

