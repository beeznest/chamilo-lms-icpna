/* For licensing terms, see /license.txt */
/*
 Learning Path minipanel - Chamilo 1.8.8
 Adding mini panel to browse Learning Paths
 Requirements: JQuery 1.4.4, JQuery UI 1.8.7
 @author Alberto Torreblanca @albert1t0
 @author Julio Montoya Cleaning/fixing some code
 **/

// Copy little progress bar in <tr></tr>
function toogle_minipanel() {   
    
    // Construct mini panel
    var panel = $('#lp_navigation_elem div:first').clone();

    $(panel).attr('id', 'control');
    $('#learning_path_main').append(panel);
                
    $('#learning_path_main #control tr').after('<tr></tr>');
    $('#learning_path_main #control tr:eq(1)').append($('#progress_bar').html());
    $('#learning_path_main #control tr:eq(1) #progress_img_limit_left').attr('height','5');
    $('#learning_path_main #control tr:eq(1) #progress_img_full').attr('height','5');
    $('#learning_path_main #control tr:eq(1) #progress_img_limit_middle').attr('height','5');
    $('#learning_path_main #control tr:eq(1) #progress_img_empty').attr('height','5');
    $('#learning_path_main #control tr:eq(1) #progress_bar_img_limit_right').attr('height','5');
    $('#learning_path_main #control tr:eq(1) #progress_text').remove();
    $('#learning_path_main #control tr:eq(1) div').css('width','');
    
    $('#learning_path_main #control .buttons').attr('text-align','center');
    $('#learning_path_main #control .buttons img').click(function(){
        $('#learning_path_main #control tr:eq(1)').remove();
        toogle_minipanel();
    });
    // Hiding navigation left zone
    $('#learning_path_left_zone').hide(50);                
    // $('#learning_path_right_zone').css('margin-left','10px');
    $('#learning_path_right_zone').addClass('Ocultar');
    $('#hide_bar table').css('backgroundImage','url(../img/hide2.png)').css('backgroundColor','#EEEEEE');
                
}

$(document).ready(function() {
    //Adding div to hide panel
    $('#learning_path_right_zone').before('<div id="hide_bar">' +
        '<table style="border: 0 none; width: 100%; height: 100%; cursor: pointer; background-color: #EEEEEE">' +
        '<tr><td></td></tr></table></div>');
    
    /*$('#hide_bar table').css({
        backgroundImage: "url(../img/hide0.png)", 
        backgroundRepeat: "no-repeat", 
        backgroundPosition: "center center"
    });*/
    
    $('#hide_bar').on('click', function (e) {
        e.preventDefault();

        var $learningPathMain = $('#learning_path_main');

        $learningPathMain.toggleClass('folded');
    });
    
    // Adding funcionality
    /*$("#hide_bar").click(function() {
        var disp = $("#learning_path_left_zone").css("display");
        // var frmWidth = $('#content_id').width();
        
        if (disp == 'block') {
            $("#learning_path_left_zone").css('display', 'none');
            // $("#learning_path_right_zone").css('margin-left', '10px');
            $('#learning_path_right_zone').removeClass('Mostrar').addClass('Ocultar'); //Añadimos Clase Ocultar
            // $("#content_id").width(frmWidth + 250);
            $('#hide_bar table').css({
                backgroundImage: "url(../img/hide2.png)", 
                backgroundRepeat: "no-repeat", 
                backgroundPosition: "center center"
            });
        } else {
            $("#learning_path_left_zone").css("display", "block");
            // $("#learning_path_right_zone").css('margin-left', marginLeftIni);
            $('#learning_path_right_zone').removeClass('Ocultar').addClass('Mostrar'); //Añadimos Clase Mostrar
            // $('#content_id').width();
            // $("#content_id").width(frmWidth - 250);
            $('#hide_bar table').css({
                backgroundImage: "url(../img/hide0.png)", 
                backgroundRepeat: "no-repeat", 
                backgroundPosition: "center center"
            });
        }
    });*/
    
    // $('#content_id').load(function() {
    //     var cntHeight = $(this).contents().height() + 10;
    //     $(this).height(cntHeight);
    //     $("#hide_bar").css('height', cntHeight);
    // });

    (function hackForAndroid(navigator) {
        var ua = navigator.userAgent.toLowerCase(),
            isMobile = /mobile/.test(ua),
            isSafari = /mac os/.test(ua);

        if(isMobile && !isSafari) {
            $('#learning_path_right_zone iframe').height(600);
        }
    })(window.navigator);
});