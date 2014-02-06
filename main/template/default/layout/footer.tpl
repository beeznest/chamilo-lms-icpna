<footer> <!-- start of #footer section -->
    <div class="container">
            <div class="row">
                <div class="span12">
                    <div class="span3">
                        <img src="/main/css/icpnatdp/images/header-logo.png">
                    </div>
                    <div class="span2 topline"><a href="#" data-toggle="modal" data-target="#FAQ">Preguntas frecuentes</a></div>
                    <!-- <div class="span2 topline"><a href="#">Política de privacidad</a></div> -->
                    <div class="span2 topline"><a href="#" data-toggle="modal" data-target="#terminos">Términos del servicios</a></div>
                    <div class="span3">
                       
                        <center><img src="/main/css/icpnatdp/images/logo-icpna.png"></center>
                       
                    </div>

                </div>
            </div><!-- end of #row -->
        <div class="row footertop">
            <div class="span11 centerline">
                Programa V-learning ICPNA / Copyright 2014 ICPNA - Todos los derechos reservados
            </div>
        </div>
    </div><!-- end of #container -->
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
