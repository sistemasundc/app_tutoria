<script type="text/javascript" src="../js/reportep.js?rev=<?php echo time();?>"></script>
	<div class="col-md-12" >
	  <div class="box box-warning box-solid">
	    <div class="box-header with-border">
	      <h3 class="box-title" style="text-align: center;"><center>Lista de Estudiante Asignados</center></h3>
	    </div>

	    <style>
	          .selecturno {
	            display: flex;
	            justify-content: end;
	          }

	          #butsearch{

	      border-radius: 5px;
	    margin-top: -2px;
	    font-size: 10px;
	    background-color: #05ccc4;
	            

	          }

	          .cmbColumn {
		  
		  border-radius: 3px;
		  margin-boton: 8px;
		 
		  border: 1px solid gray;
		  font-size: 14px;
		  height: 30px;
		  padding: 5px;
		  width: 100px;
		}

		.cmbColumn .option {
		  height: 30px;
		  border-button: 8px;
		}
	        </style>

	    <!-- /.box-header -->
	    <div class="box-body">
	      <div class="box-body">
	        <div class="row">
	          	
	          	

	          	<div class="col-md-10">
					<label for="">Asignaci&oacute;n</label>
					<br>
					<select class="cmbColumn" id="tipo_asignacion" >
					    <option value="grupal" selected>Grupal</option>
						<option value="individual">Individual</option>	
					</select>
	          	</div>

	    
	          
		        <div class="col-md-2">
		            <div class="input-group">
		            	 <label for="">Filtrar búsqueda</label>
		                   <input type="text" class="global_filter form-control" id="global_filter" placeholder="Ingresar dato a buscar" style="border-radius: 5px;">
		               </div>
		        </div>
	        </div>
	        <br>
	        <br>
	         <table id="table_alumno_asignado" class="display responsive nowrap" style="width:100%">
	                <thead>
	                    <tr>
	                        <th>N° id</th>
	                        <th>Cod</th>
	                        <th>Apellidos</th>
	                        <th>Nombres</th>
	                        <th>Tel&eacute;fono</th>
	                        <th>Ciclo</th>
							<th>Dni</th>
	                        <th>Correo</th>
	                        
	                        <th>Tipo</th>
	                        <th>Rendimiento</th>
	                        <th>Derivar</th>
	                    </tr> 
	                </thead>
	                <tfoot>
	                    <tr>
	                        <th></th>
	                        <th></th>
	                        <th></th>
	                        <th></th>
	                        <th></th>
	                        <th></th>
	                        <th></th>
	                        <th></th>
	                        <th></th>
	                        <th></th> 
	                        <th></th> 
	                    </tr>
	                </tfoot>
	            </table>
	       
	    </div>
	     <div class="modal-footer">
	         
	     </div>
	  </div>

<form autocomplete="false" onsubmit="return false">
  <div class="modal fade" id="modal_derivar">
    <div class="modal-dialog">
      <div class="modal-content">
      
        <!-- Modal Header -->
        <div class="modal-header" style="border: none;">
         <center> <h4 class="modal-title"><b>Derivar Estudiante</b></h4>
         </center>
          <button type="button" class="close" data-dismiss="modal" style="position: absolute; left: 90%; top: 7%;">&times;</button>
        </div>
        
        <!-- Modal body -->
        <div class="modal-body">
         <div class="row ">
         	<div class="col-md-12">
		        <div class="box-body">
		            <div class="btn-group" style="width: 100%; margin-bottom: 10px;">
		                <label for=""><b>Area de Apoyo:<b></label>
		                <select class="js-example-basic-single global_filter form-control campoasis " id="areas_apoyo" style="width:100%;" > 
		                </select>
		            </div>
		        </div>	

		        
            </div>
		    </div>
            <div class="col-md-12">
				<label for="">Motivo:</label>
		        <textarea type="text" class="areat campoasis form-control" id="motivo_der" style="border-radius: 5px; max-width: 100%;"></textarea>
			</div>
			
          </div>
        	<br>
        	<br>
        <!-- Modal footer -->
        <div class="modal-footer" style="margin-top: 2em;">
            <button class="btn btn-primary" onclick="Derivar_estudiante()" ><i class="fa fa-check"><b>&nbsp;Derivar</b></i></button>
            <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-close"><b>&nbsp;Cerrar</b></i></button>
        </div>
      </div>
    </div>
  </div>
</div>
</form>

	</div>
</div>


	<script>
$(document).ready(function() {
	$('.js-example-basic-single').select2();
    listar_Alumnos_Asignados();//esta en index.js==> cargar semestre actual
    listar_areas_apoyo();
} );
</script>