<?php 
   include_once '../../modelo/modelo_horario.php';
    $miCodigo = '2';

    $ddd  = new  Horario();

    session_start();
    $estu = $_SESSION['S_IDUSUARIO'];

    $horas = $ddd -> ListaHora();
 ?>

<style type="text/css">
table{
    background-color: white;
    text-align: left;
    border-collapse: collapse;
    width: 100%;
}
thead{
    background-color: white;
    color: black;
}
tr:hover td{
    background-color: #3c8dbc;
    color: white;
}
</style>
 <script type="text/javascript" src="../js/horario.js?rev=<?php echo time();?>"></script>
<div class="col-md-12">
    <div class="box box-warning box-solid">
        <div class="box-header with-border">
              <h3 class="box-title"> MI HORARIO TUTORIA</h3>

            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                </button>
            </div>
              <!-- /.box-tools -->
        </div>
             <!-- /////////// -->
    
            <!-- /.box-header -->
            <div class="box-body">
                <div class="row">                      
               <div class="col-md-12">
              <div class="box box-primary">
            <div class="box-body no-padding  table-responsive">

                <table class="table table-bordered">
                        <thead>
                            <tr style="background-color: #066da7; color: white;">
                                <th>Hora</th>
                                <th>Lunes</th>
                                <th>Martes</th>
                                <th>Miercoles</th>
                                <th>Jueves</th>
                                <th>Viernes</th>
                                <th>Sabado</th>
                                <th>Domingo</th>
                            </tr>
                        </thead>
                            <tbody>
        <?php foreach ($horas as $hora) { ?>
            <tr>
                <td><?php echo $hora['inicio'] . ' - ' . $hora['fin']; ?></td>

                <?php
                $horario = $ddd->ListarHorasEstudiante($estu, $hora['idhora']);
                $dias = [1, 2, 3, 4, 5, 6, 7]; // Lista de días de la semana
                foreach ($dias as $dia) {
                    $contenidoEncontrado = false;
                    foreach ($horario as $contenido) {
                        if ($dia == $contenido['dia']) {
                            ?>
                            <td><span class='label label-success' style="font-size: 13px;"><?php echo $contenido['nombres'] ?></span></td>

                            <?php
                            $contenidoEncontrado = true;
                            break;
                        }
                    }
                    if (!$contenidoEncontrado) {
                        ?>
                        <td></td>
                        <?php
                    }
                }
                ?>
            </tr>
        <?php } ?>
    </tbody>
                    </table>

                </div>
               </div>
                 </div>
            </div>
            
            </div>

            <br>
            <!-- /.box-body -->
    </div>
          <!-- /.box -->
</div>
<script type="text/javascript">
	
	$('.js-example-basic-single').select2();
</script>