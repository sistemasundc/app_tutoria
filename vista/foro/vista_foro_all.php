<link rel="stylesheet" href="../Plantilla/dist/css/skins/_foro.css">

<style type="text/css">
@media (min-width: 768px){ 
  .tt-list-search  { 
    padding: 0 30px;
  } 
}     

@media (min-width: 576px){ 
  .tt-list-search  { 
    display: -webkit-box; 
    display: -ms-flexbox; 
    display: flex; 
    -webkit-box-orient: horizontal; 
    -webkit-box-direction: normal; 
    -ms-flex-direction: row; 
    flex-direction: row; 
    -ms-flex-wrap: wrap; 
    flex-wrap: wrap; 
    -webkit-box-pack: justify; 
    -ms-flex-pack: justify; 
    justify-content: space-between; 
    -ms-flex-line-pack: stretch; 
    align-content: stretch; 
    -webkit-box-align: center; 
    -ms-flex-align: center; 
    align-items: center;
  } 
}

.tt-title  { 
    color: #303344; 
    font-size: 16px; 
    font-weight: 500;
} 

.tt-search { 
    display: inline-block; 
    position: relative; 
    width: 350px;
} 

.tt-search form  { 
    display: block;
} 

.tt-search .search-wrapper  { 
    display: block;
} 

.tt-search .search-form  { 
    position: relative;
} 


.tt-search .tt-search__input  { 
    border: none; 
    outline: 0; 
    font-size: 16px; 
    color: #182730; 
    font-weight: 500; 
    letter-spacing: .01em; 
    padding: 3px 15px 5px 47px; 
    width: 100%; 
    background-color: #e2e7ea; 
    border-radius: 3px; 
    height: 39px;
} 


.tt-search .tt-search__btn  { 
    background: 0 0; 
    border: none; 
    outline: 0; 
    position: absolute; 
    left: 0; 
    top: 0; 
    padding: 5px 5px 7px 15px;
} 


.tt-search .tt-search__close  { 
    position: absolute; 
    right: -43px; 
    top: 2px; 
    display: none; 
    background: 0 0; 
    border: none; 
    outline: 0; 
    padding: 5px 15px;
} 



.tt-search .tt-search__btn .tt-icon  { 
    width: 18px; 
    height: 18px; 
    fill: #666f74;
} 

.tt-search .tt-search__close .tt-icon  { 
    width: 13px; 
    height: 13px; 
    -webkit-transition: fill .2s linear; 
    transition: fill .2s linear;
} 
.headforo {
    color: #585858;
}

ul {
  list-style-type: none;
}
</style>

    

<div class="tt-list-search snipcss-kRvfq">
  <div class="tt-title">
    <i class="fa fa-archive" aria-hidden="true" style="color: #585858;"></i>&nbsp;&nbsp;Ultimos Temas
  </div>
  <div class="tt-search">
    <form class="search-wrapper">
      <div class="search-form">
        <input type="text" id="search" onkeydown="ListarForosAll()" class="tt-search__input" placeholder="Buscar temas">
        <button class="tt-search__btn" type="button">
          <svg class="tt-icon">
            <i class="fa fa-search" aria-hidden="true" style="color: #787e8b; position: relative; left: -1em;"></i> &nbsp;
          </svg>
        </button>
      </div>
    </form>
  </div>
</div>

<div style="width: 100%; height: 2px; background-color: #e2e7ea; margin-top: 10px; margin-bottom: 10px;"></div>

<div class="tt-topic-list">
            <div class="tt-list-header">
                <div class="tt-col-topic headforo"><i class="fa fa-book" aria-hidden="true"></i>&nbsp; Tema</div>                
                <div class="tt-col-value hide-mobile headforo"><i class="fa fa-commenting" aria-hidden="true"></i>&nbsp; Respuestas</div>
                <div class="tt-col-value hide-mobile headforo"><i class="fa fa-calendar-o" aria-hidden="true"></i>&nbsp; Fecha</div>
                <div class="tt-col-value headforo"><i class="fa fa-indent" aria-hidden="true"></i>&nbsp; Opciones</div>
            </div> 

            <div id="contenidoForoAll">

            </div>

            <button type="button" id="btnpag" onclick="Paginacion()" class="btn btn-primary js-topiclist-showmore" style="width: 100%; background-color: #4a6fa5;">
                Sin resultados
            </button>

            <div style="height: 2em;"></div>
        </div>

<script type="text/javascript">
    $(document).ready(function() {
        ListarForosAll();
    });
</script>