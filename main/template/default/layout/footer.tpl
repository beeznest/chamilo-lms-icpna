<footer> <!-- start of #footer section -->	    
    <div class="container">
            <div class="row"> 
           <!--  Start Logotipo -->
            <div id="footer-one" class="span6 padding-footer">
                {# logo #}
                {{ logo }}
            </div>
           <!--  End Logotipo  --> 
           <!--  Sobre Vlearning -->      
            <div id="footer_two" class="span3">

            <h1 class="title">Sobre V Learning</h1>
            <ul class="listen">
                <li><a href="#">¿Quienes Somos?</a></li>
                <li><a href="#">Nuestro Equipo</a></li>
                <li><a href="#">Ayuda</a></li>
            </ul>
                         
            </div>
            <!--  End Sobre Vlearning -->
            <!--  Start Prensa -->
            
            <div id="footer_tree" class="span2">
                 <h1 class="title">Prensa</h1>
                    <ul class="listen">
                        <li><a href="#">Cobertura de Actividades</a></li>
                        <li><a href="#">Noticias</a></li>
                        <li><a href="#">Otras cosas</a></li>
                    </ul>
            </div>
            <!--  End Prensa -->
            <!--  Start contactenos -->
            <div id="footer_four" class="span1"> 

                    
            </div><!-- end contactenos -->
        </div><!-- end of #row --> 
        <div class="row line">
            <div class="span3"><a href="#">Terminos del Servicio</a></div>
            <div class="span3"><a href="#">Politica de Privacidad</a></div> 
            <div class="span6"><a href="#">Mapa del Sitio</a></div>           
        </div>       
    </div><!-- end of #container -->
</footer>

{{ footer_extra_content }}

{% raw %}
<script>
/* Makes row highlighting possible */
$(document).ready( function() {
    //Chosen select
    $(".chzn-select").chosen();     
    
    //Table highlight
    $("form .data_table input:checkbox").click(function() {
        if ($(this).is(":checked")) {
            $(this).parentsUntil("tr").parent().addClass("row_selected");
                        
        } else {
            $(this).parentsUntil("tr").parent().removeClass("row_selected");
        }    
    });
    
    /* For non HTML5 browsers */
    if ($("#formLogin".length > 1)) {
        $("input[name=login]").focus();
    }
    
    /* For IOS users */
    $('.autocapitalize_off').attr('autocapitalize', 'off');
    
    //Tool tip (in exercises)
    var tip_options = {
        placement : 'right'
    }
    $('.boot-tooltip').tooltip(tip_options);
   
});
</script>
{% endraw %}
{{ execution_stats }}