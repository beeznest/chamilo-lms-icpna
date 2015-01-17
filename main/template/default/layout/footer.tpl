<footer {{ isInLP is defined ? 'id="footer-in-lp"' : '' }}> <!-- start of #footer section -->
    <div class="divisor-line">
        <div class="container">
            <div class="row">
                <div class="span2 {{ isInLP is defined ? 'visible-desktop' : '' }}">
                    <h4>Sobre V-learning</h4>
                    <ul class="bg-footer-movil">
                        <li><a href="#" title="¿Quienes somos?">¿Quienes somos?</a> </li>
                        <li><a href="#" title="Nuestro Equipo">Nuestro Equipo</a> </li>
                        <li><a href="#" title="Socios">Socios</a> </li>
                    </ul>
                </div>
                <div class="span2 {{ isInLP is defined ? 'visible-desktop' : '' }}">
                    <h4>Prensa</h4>
                    <ul class="bg-footer-movil">
                        <li><a href="#" title="Calendario de Medios">Calendario de medios</a> </li>
                        <li><a href="#" title="Premios">Premios</a> </li>

                    </ul>
                </div>
                <div class="span8">
                    <div class="red-social">
                        <h4>Contáctanos</h4>
                        <a href="#" title="Nuestro Facebook" target="_blank"><img src="{{ _p.web_css }}nuevo_vlearning/img/icon-facebook.png"></a>
                        <a href="#" title="Nuestro Twitter" target="_blank"><img src="{{ _p.web_css }}nuevo_vlearning/img/icon-twitter.png"></a>
                        <a href="#" title="Nuestro Youtube" target="_blank"><img src="{{ _p.web_css }}nuevo_vlearning/img/icon-youtube.png"></a>
                    </div>

                    <div class="red-social-movil">
                        <div class="title-red-social"><h4>Contactanos</h4></div>
                        <div class="icons-red-social">
                            <a href="#" title="Nuestro Facebook" target="_blank"><img src="{{ _p.web_css }}nuevo_vlearning/img/facebook-48.png"></a>
                            <a href="#" title="Nuestro Twitter" target="_blank"><img src="{{ _p.web_css }}nuevo_vlearning/img/twitter-48.png"></a>
                            <a href="#" title="Nuestro Youtube" target="_blank"><img src="{{ _p.web_css }}nuevo_vlearning/img/youtube-48.png"></a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="subfooter">
        <div class="container">
            <div class="row">
                <div class="span6">
                    <div class="links-footer">
                        <a href="#" data-toggle="modal" data-target="#FAQ">Preguntas Frecuentes</a> &nbsp| &nbsp
                        <a href="#" data-toggle="modal" data-target="#terminos-condiciones">Términos y condiciones</a> &nbsp| &nbsp
                        <a href="#" data-toggle="modal" data-target="#beneficios">Beneficios</a>
                    </div>
                </div>
                <div class="span6">
                    <div class="text-copyright">Programa V-learning ICPNA / Copyright 2014 ICPNA - Todos los derechos reservados</div>
                </div>
            </div>
        </div>
    </div>
</footer>
{%include 'default/layout/modal_footer.tpl'%}
{{ footer_extra_content }}

{% raw %}
<script>
/* Makes row highlighting possible */
$(document).ready( function() {

    /**
     * Advanced options
     * Usage
     * <a id="link" href="url">Advanced</a>
     * <div id="link_options" style="display:none">
     *     hidden content :)
     * </div>
     * */

    $(".advanced_options").on("click", function() {
        var id = $(this).attr('id') + '_options';
        var button = $(this);
        $("#"+id).toggle(function() {
            button.toggleClass('active');
        });
    });

    $(".advanced_options_open").on("click", function() {
        var id = $(this).attr('rel');
        $("#"+id).show();
    });

    $(".advanced_options_close").on("click", function() {
        var id = $(this).attr('rel');
        $("#"+id).hide();
    });

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
