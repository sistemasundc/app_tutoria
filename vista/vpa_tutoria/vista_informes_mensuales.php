<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<script type="text/javascript" src="../js/docente.js?rev=<?php echo time();?>"></script>
<script type="text/javascript" src="../js/index.js?rev= <?php echo time();?>"></script>
<?php session_start(); ?>
<div class="col-md-7">
    <div class="box box-warning box-solid">
        <div class="box-header with-border">
              <h3 class="box-title">LISTA DE DOCENTES TUTORES</h3>

            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                </button>
            </div>
              <!-- /.box-tools -->
        </div>
            <!-- /.box-header -->
            <div class="box-body">
            <div class="form-group">
                <div class="col-lg-9">
                    <div class="input-group">
                        <input type="text" class="global_filter form-control" id="global_filter" placeholder="Ingresar dato a buscar">
                        <span class="input-group-addon"><i class="fa fa-search"></i></span>
                    </div>
                </div>
                <div class="col-lg-3">
                    <button class="btn btn-danger" style="width:100%" onclick="AbrirModalDocente()"><i class="glyphicon glyphicon-plus"></i>&nbsp; Nuevo Registro</button>
                </div>
             </div>
            <table id="tabla_Docentes" class="display responsive nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>NOMBRES</th>
   
                        <th>ESCUELA</th>
                       
                        <th>Acci&oacute;n</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        
                    </tr>
                </tfoot>
            </table>
            </div>
            <!-- /.box-body -->
    </div>
          <!-- /.box -->
</div>

<div class="col-md-5">
    <div class="box box-warning">
        <div class="box-header with-border">
              <h3 class="box-title">ALUMNO ASIGNADOS</h3>

            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                </button>
            </div>
              <!-- /.box-tools -->
        </div>
            <!-- /.box-header -->
            <div class="box-body">
            <div class="form-group">
                <table id="tablasAsignados" class="display responsive nowrap" style="width:100%" hidden>
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Alumno</th>
                        <th>Acci&oacute;n</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
             </div>
            
            </div>
            <!-- /.box-body -->
    </div>
          <!-- /.box -->
</div>

<form autocomplete="false" onsubmit="return false">
    <div class="modal fade" id="modal_registro_docente" role="dialog">
        <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #05ccc4; border: none;">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title"><b>Registro De Docentes</b></h4>
            </div>
            <div class="modal-body">
                <div class="col-lg-12">
                    <label for="">Docentes:</label>
                    <select class="js-example-basic-single" name="state" id="sdocente" style="width:100%;">
                        
                    </select><br><br>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="Registrar_Docente()"><i class="fa fa-check"><b>&nbsp;Registrar</b></i></button>
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-close"><b>&nbsp;Cerrar</b></i></button>
            </div>
        </div>
        </div>
    </div>
</form>

<!-- The Modal -->
<form autocomplete="false" >
  <div class="modal fade" id="modal_agregar_curso">
    <div class="modal-dialog">
      <div class="modal-content" style="width: 65%" >
        
        <!-- Modal Header -->
        <div class="modal-header" style="border: none;">
         <center> <h4 class="modal-title" style="text-transform: uppercase;"><b>Asignación de alumnos</b></h4>
         </center>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        
        <!-- Modal body -->
        <div class="modal-body">
         <div class="row ">
              
              <div class="col-lg-12">
                   <label for="">CARRERA, CICLO, SECCIÓN TURNO Y GRUPO</label>
                   <input type="text" id="textId_co" value="<?php echo $_SESSION['S_IDUSUARIO'] ?>"hidden >
                    <select class="js-example-basic-single" name="state" id="cicloa" style="width:100%;">
                    </select><br><br>
            </div>
          </div>
        <!-- Modal footer -->
        <div class="modal-footer">
                <button class="btn btn-primary" id="subirasigbtn" onclick="DocentAsignado()" ><i class="fa fa-check"><b>&nbsp;Registrar</b></i></button>
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-close"><b>&nbsp;Cerrar</b></i></button>
            </div>
        
      </div>
    </div>
  </div>
</div>
</form>

<!-- The Modal -->
<form autocomplete="false" onsubmit="return false">
  <div class="modal fade" id="modal_ver_curso">
    <div class="modal-dialog">
      <div class="modal-content">
      
        <!-- Modal Header -->
        <div class="modal-header">
         <center> <h4 class="modal-title"><b> ALUMNOS ASIGNADOS</b></h4></center>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
         <input type="text" name="" id="textiddocenteV" hidden>
        <!-- Modal body -->
        <div class="modal-body">
                <style>
               .modal-body{  height: 100%; } .loader{display: none; }</style><div class="loader"><img src="abc.gif" alt="" style="width: 50px;height:50px;"></div>

                    <div class="col-lg-12">
                       <div class=" table-responsive" ><br>
                             <table id="tabla_cursogrado_docent "style="width: 100%" class="table">
                                  <thead class=" thead-drak" bgcolor="black" style="color: #ffffff">
                                           <td>Numero</td> 
                                            <td>Cursos</td> 
                                            <td>Grados</td>
                                           <td>Quitar</td> 
                                        </tr>
                                   <tbody id="tabla_cursogrado_docent">   
                                  </tbody>
                            </table>
                        
                        </div>
                   </div>
        </div>

         
        <!-- Modal footer -->
        <div class="modal-footer">
              
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-close"><b>&nbsp;Cerrar</b></i></button>
            </div>
        
      </div>
    </div>
  </div>
</div>
</form>
<form autocomplete="false" onsubmit="return false">
    <div class="modal fade" id="docente_edit" role="dialog">
        <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title"><b>ACTUALIZAR DOCENTE</b></h4>
            </div>
            <div class="modal-body">
               <input type="text"  id="id_docent" hidden>
                <div class="col-lg-12">
                    <label for="">Usuario</label>
                    <input type="text" class="form-control" id="docentenom" placeholder="Ingrese usuario"><br>
                </div>
                <div class="col-lg-12">
                    <label for="">Apellidos</label>
                    <input type="text" class="form-control" id="appdocent" placeholder="Ingrese usuario"><br>
                </div>
                 
                 <div class="col-lg-12">
                     <label for="">Estado</label>
                    <select class="js-example-basic-single" name="state" id="statusdocent" style="width:100%;">
                       <option value="ACTIVO">ACTIVO</option>
                        <option value="INACTIVO">INACTIVO</option>
                        
                    </select><br><br>
                </div>
                <div class="col-lg-12">
                    <label for="">Sexo</label>
                    <select class="js-example-basic-single" name="state" id="docentsex" style="width:100%;">
                        <option value="M">MASCULINO</option>
                        <option value="F">FEMENINO</option>
                    </select><br><br>
                </div>
                <div class="col-lg-12">
                    <label for="">Tipo </label>
                    <select class="js-example-basic-single" name="state" id="tipodocebt" style="width:100%;">
                        <option value="CONTRATADO">CONTRATADO</option>
                        <option value="NOMBRADO">NOMBRADO</option>
                    </select><br><br>
                </div>

            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="Update_Docente()"><i class="fa fa-check"><b>&nbsp;Registrar</b></i></button>
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-close"><b>&nbsp;Cerrar</b></i></button>
            </div>
        </div>
        </div>
    </div>
</form>
<script>
$(document).ready(function() {
    listar_docente();
    listar_combo_docentes();
    $('.js-example-basic-single').select2();
    
    $("#modal_registro_docente").on('shown.bs.modal',function(){
        $("#txt_usu").focus();  
    })
/*
    $('#tipo_asig').change(function(){//se ejecuta la funcion
    var idnivel= $('#tipo_asig').val();
                Traer_cursos(idnivel);         
   }) */
});
</script>
