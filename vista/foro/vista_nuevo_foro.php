<link rel="stylesheet" href="../Plantilla/dist/css/skins/_foro_nuevo.css">

        <div class="tt-wrapper-inner" style="margin-bottom: 20px;">
            <h1 class="tt-title-border">
                Crear una nueva publicación
            </h1>
            <form class="form-default form-create-topic">
                <div class="form-group">
                    <label for="inputTopicTitle">Título del tema</label>
                    <div class="tt-value-wrapper">
                        <input type="text" id="asunto" class="form-control" placeholder="Asunto de tu tema" autocomplete="off">
                    </div>
                </div>
              
                <div class="pt-editor">
                    <h6 class="pt-title">Contenido del tema</h6>
                   
                    <div class="form-group">
                        <textarea id="mensaje" class="form-control" rows="5" placeholder="Escribe aquí tu contenido de tema" autocomplete="off"></textarea>
                    </div>
                     <div class="row" hidden>
                        <div class="col-md-12" hidden>
                            <div class="form-group" hidden>
                                <label for="inputTopicTitle" hidden>Permisos de visualización</label>
                                <select class="form-control" id="id_carga" hidden>
                                </select>
                            </div>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-auto ml-md-auto">
                            <button type="button" class="btn btn-success btn-width-lg" onclick="PublicarForo()">Crear publicación</button> 
                        </div>
                    </div>
                </div>
            </form>
        </div>


<script type="text/javascript">
$(document).ready(function() {
    listar_combo_ciclos();
} );
</script>