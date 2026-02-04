<script type="text/javascript" src="../js/historial_derivaciones.js?rev=<?php echo time();?>"></script>
<script type="text/javascript" src="../js/pdf.js?rev=<?php echo time();?>"></script>
	<div class="col-md-12" >
	  <div class="box box-primary box-solid">
	    <div class="box-header with-border">
	      <h3 class="box-title" style="text-align: center;"><center>Historial de Derivaciones</center></h3>
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

	   <?php 
              date_default_timezone_set('America/Lima');
              $n_today = date('N');
              $f_today = date('Y-m-d');
            ?>
            <input type="text" id="ntoday" value="<?php echo $n_today ?>" hidden >
            <input type="text" id="ftoday" value="<?php echo $f_today ?>" hidden >
	    <!-- /.box-header -->
	    <div class="box-body">
	      <div class="box-body">
	        <div class="row">
		        <div class="col-md-9">
	        	</div>
		       <div class="col-md-3 d-flex align-items-left">
				    <label for="" style="margin-right: 10px;">Filtrar búsqueda</label>
				    <input type="text" class="global_filter form-control" id="global_filter" placeholder="Ingresar dato a buscar" style="border-radius: 5px;">
				</div>
	        </div>
	  
	         <table id="tabla_historial_derivaciones" class="display responsive nowrap" style="width:100%">
	                <thead>
	                    <tr>
	                        <th>N° id</th>
	                        <th>Fecha</th>
	                        <th>Estudiante</th>
	                        <th>Teléfono</th>
	                        <th>Estado</th>
							<th>Área</th>
							<th>Formato</th>
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
	                    </tr>
	                </tfoot>
	            </table>
	       
	    </div>
	     <div class="modal-footer">
	         
	     </div>
	  </div>

	  <form method="post" action="../pdf_ge/index.php" id="formHis" target="_blank">
	  	<input type="text" name="id_his" id="id_history" hidden>
	  </form>

	  <form method="post" action="../pdf_ge/index.php" target="_blank" id="formder">
			<input type="text" name="id_derivacion" id="id_der" hidden>
		</form>

	</div>
</div>
	<script>
	$(document).ready(function() { 
	    listar_historial_derivaciones();
	});
</script>