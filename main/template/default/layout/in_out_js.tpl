{# IN/OUT FEATURE FOR TEACHERS #}
{% if _u.status == 1 %}
<script>
    $(document).ready(function () {
        $("#btn_in_session").click(function () {
            $.ajax({
                type: "GET",
                dataType: "json",
                url: "{{ _p.web_ajax }}course_home.ajax.php",
                data: "a=save_teacher_track_in&course_id=" + $("#in_course_id").val() + "&session_id=" + $("#in_session_id").val(),
                success: function (jsonData) {
                    if (jsonData.id == 1) {
                        $("#in-out-modal-label").css("color", "blue");
                        $("#in-out-modal .modal-body").html("<p style='color: blue'>Your IN has been saved correctly (" + jsonData.date + ").</p>");
                        $("#btn_in_session").addClass('hide-important');
                        $("#btn_out_session").removeClass('hide-important');
                    } else {
                        var rowHtml = "<table class='table table-bordered'><tr><th  style='color: gray'>Session</th></tr>";
                        $.each(jsonData.data, function (key, value) {
                            rowHtml += "<tr><td style='color: gray'>" + value.session_name + "</td></tr>";
                        });
                        rowHtml += "</table>"

                        $("#in-out-modal-label").css("color", "red");
                        $("#in-out-modal .modal-body").html("<p style='color: red'>We have detected some problems \
                            maybe you did not log OUT from other course. Please first log OUT you other session \
                            to continue. </p>" + rowHtml);
                    }
                    $("#in-out-modal-label").html("Alert!");
                    $("#in-out-modal .modal-footer").html("<button type='button' class='btn' data-dismiss='modal' aria-hidden='true'>Close</button>");
                    $("#in-out-modal").modal({
                        backdrop: 'static',
                        keyborad: false,
                        show: true
                    });
                }
            });
        });

        $("#btn_out_session").click(function () {
            $("#in-out-modal-label").html("Alert!");
            $("#in-out-modal-label").css("color", "gray");
            $("#in-out-modal .modal-body").html("<p style='color: gray'>Are you sure you want to log OUT?</p>");
            $("#in-out-modal .modal-footer").html("<button type='button' class='btn' data-dismiss='modal' aria-hidden='true'>No</button> \
                <button type='button' name='confirm_out_session' id='confirm_out_session' class='btn btn-danger'>Yes</button>");
            $("#in-out-modal").on('hidden', function (e, isDoneOut) {
                if (isDoneOut === true) {
                    $('#logout_button').trigger('click');
                }
            }).modal({
                backdrop: 'static',
                keyborad: false,
                show: true
            });
        });

        $("#in-out-modal").on("click", "#confirm_out_session", function () {
            $.ajax({
                type: "GET",
                dataType: "json",
                url: "{{ _p.web_ajax }}course_home.ajax.php",
                data: "a=save_teacher_track_out&course_id=" + $("#in_course_id").val() + "&session_id=" + $("#in_session_id").val(),
                success: function (jsonData) {
                    if (jsonData.id == 1) {
                        $("#in-out-modal-label").css("color", "blue");
                        $("#in-out-modal .modal-body").html("<p style='color: blue'>Your OUT has been saved correctly (" + jsonData.date + ").</p>");
                        $("#btn_out_session").addClass('hide-important');
                        $("#btn_in_session").removeClass('hide-important');
                    } else {
                        $("#in-out-modal-label").css("color", "red");
                        $("#in-out-modal .modal-body").html("<p style='color: red'>We have detected some problems \
                            Please contact the support team.</p>");
                    }
                    $("#in-out-modal-label").html("Alert!");
                    $("#in-out-modal .modal-footer").html("<button type='button' class='btn' data-dismiss='modal' id='out-close-modal'>Close</button>");
                }
            });
        });

        $("#in-out-modal").on("click", "#out-close-modal", function () {
            $("#in-out-modal").trigger('hidden', [true]);
        });
    });
</script>
    {% endif %}
    {# END IN / OUT FEATURE #}