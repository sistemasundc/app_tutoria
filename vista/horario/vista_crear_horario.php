<?php
include_once '../../modelo/modelo_horario.php';
$ddd  = new  Horario();

$a[0] = 1;

session_start();
$doce = $_SESSION['S_IDUSUARIO'];

//$cursos = $ddd->listar_combo_cursos($doce);

$horas = $ddd -> ListaHora();
?>


<script type="text/javascript" src="../js/horario.js?rev=<?php echo time();?>"></script> 
<div class="col-md-12" id="create-form-horario">
          <div class="box box-warning box-solid">
          
            <div class="box-header with-border">
              <h3 class="box-title">NUEVO HORARIO</h3>

              <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                </button>
              </div>
              <!-- /.box-tools -->
            </div>

            <!-- /.box-header -->
            <div class="box-body">
    <section class="content" >
      <div class="row">
        <div class="col-md-3">

           
            <div class="box-body">
              <div class="btn-group" style="width: 100%; margin-bottom: 10px;">
                    <label for=""><b>TIPO<b></label>
                    <select class="js-example-basic-single" name="state" id="tipo_asig" style="width:100%;" > 
                    </select>
                
              </div>
            </div>
         

         <div class="container">
           <div class="container">
             <div id="divtrabajo"></div>
           </div>
         </div>

          <div class="box box-solid">
            <div class="box-header with-border">
              <label for=""><b>ALUMNOS<b></label>
            </div>
            <div class="box-body">
               
              <input type="text" name="" id="id_horario" hidden>
              <div id="alumnos_asignados">
                   <!-- js -->
              </div>
            </div> 
          </div>
        </div>
        
        <!-- /.col -->
        <div class="col-md-9">
          <div class="box box-primary">
            <div class="box-body no-padding  table-responsive">
              <!-- THE table -->
             <table class="table table-bordered table-sm">
    <thead>
        <tr style="background-color: #066da7; color: white;">
            <th>Hora</th>
            <th>Lunes</th>
            <th>Martes</th>
            <th>Miércoles</th>
            <th>Jueves</th>
            <th>Viernes</th>
            <th>Sábado</th>
            <th>Domingo</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($horas as $hora) { ?>
            <tr>
                <td><?php echo $hora['inicio'] . ' - ' . $hora['fin']; ?></td>

                <?php
                $horario = $ddd->ListarHoras($doce, $hora['idhora']);
                $dias = [1, 2, 3, 4, 5, 6, 7]; // Lista de días de la semana
                foreach ($dias as $dia) {
                    $contenidoEncontrado = false;
                    foreach ($horario as $contenido) {
                        if ($dia == $contenido['dia']) {

                            if ($contenido['tipo'] == '2'){
                              ?>
                                <td id="td<?php echo $contenido['dia'] . $hora['idhora']; ?>" idhora="<?php echo $contenido['dia']; ?>" iddia="<?php echo $hora['idhora']; ?>" idhorario="">
                                  <span class='label label-primary' style="font-size: 13px;">GRUPAL</span>
                                  <span class='label label-danger' style="font-size: 13px;" onclick="Eliminar_alumno_horario(<?php echo $contenido['id_horario'];?>, <?php echo $contenido['tipo'] ?>, <?php echo $hora['idhora'] ?>, <?php echo $contenido['dia'] ?>)">
                                    <i class="fa fa-trash" aria-hidden="true"></i>
                                  </span>
                                </td>
                              <?php
                            }else {
                              ?>
                                <td id="td<?php echo $contenido['dia'] . $hora['idhora']; ?>" idhora="<?php echo $contenido['dia']; ?>" iddia="<?php echo $hora['idhora']; ?>" idhorario="">
                                  <span class='label label-warning' style="font-size: 13px;"><?php echo $contenido['estudiante'] ?></span>
                                  <span class='label label-danger' style="font-size: 13px;" onclick="Eliminar_alumno_horario(<?php echo $contenido['id_horario'];?>, <?php echo $contenido['tipo'] ?>, <?php echo $hora['idhora'] ?>, <?php echo $contenido['dia'] ?>)">
                                    <i class="fa fa-trash" aria-hidden="true"></i>
                                  </span>
                                </td>
                              <?php
                            }
                            $contenidoEncontrado = true;
                            break;
                        }
                    }
                    if (!$contenidoEncontrado) {
                        ?>
                        <td id="td<?php echo $dia . $hora['idhora']; ?>" class="dropzone" idhora="<?php echo $dia; ?>" iddia="<?php echo $hora['idhora']; ?>" idhorario=""></td>
                        <?php
                    }
                }
                ?>
            </tr>
        <?php } ?>
    </tbody>
</table>
            <!-- /.box-body -->
          </div>
          <!-- /. box -->
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
    </section>
            </div> 

           <div class="modal-footer">
                <button class="btn btn-success" onclick="Registrar_Horario()"><i class="fa fa-check"><b>&nbsp;Save</b></i></button>
            </div>
            <br>


        </div>
      </div>



   
  <script> 

 $(document).ready(function() {
   $('.js-example-basic-single').select2();
    listar_combo_niveles();//LISTA DE GRADOS
    crearidaleatorio();//CREARDO ID ALEATORIO PARA HORARIO
        
    //SemstreActualH();//combo semestre
    crearHorarios(); 

    $("#tipo_asig").change(function(){
        Listar_alumnos_asignados();
    });
            
    $("#modal_registro").on('shown.bs.modal',function(){
        $("#txt_usu").focus();  
    })


} );    
           
</script>


