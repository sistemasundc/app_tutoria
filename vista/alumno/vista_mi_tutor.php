<?php session_start(); ?>

<script type="text/javascript" src="../js/alumno.js?rev=<?php echo time(); ?>"></script>

<div class="col-md-12">
  <div class="box box-info">
    <div class="box-header with-border">
      <h3 class="box-title"><br>TUTORES ASIGNADOS</br></h3>
    </div>

    <div id="mis-tutores-asignados">

    </div>
  </div>
</div>

<script>
  $(document).ready(function () {
    Docente_a_Cargo();
  });
</script>