<div class="row">
    <div class="col-sm-6 col-sm-offset-3">
        {{ form }}
    </div>
</div>
<div class="row">
    <div class="col-sm-6 col-sm-offset-3" id="player">
    </div>
</div>

<script>
    $(document).on('ready', function () {
        $('form#validate').on('submit', function (e) {
            e.preventDefault();

            var frm = $(this),
                formData = new FormData(this),
                player = $('#player'),
                frmGroup = $('#validate .form-group');

            this.reset();

            function handleReponse(response) {
                if (response.ok) {
                    return response.blob();
                }

                var error = new Error(response.statusText);
                error.response = response;

                throw error;
            }

            function handleBlob(blob) {
                frmGroup
                    .removeClass('has-error')
                    .addClass('has-success');

                var audio = $('<audio>');
                audio
                    .prop({
                        controls: true,
                        src: URL.createObjectURL(blob)
                    })
                    .on('play', handlePlay)
                    .on('pause', handlePause);

                player.html(audio);
                frm.find('#validate_pass').parents('.form-group').remove();
                frm.find(':submit').parents('.form-group').remove();

                window.setTimeout(function () {
                    frmGroup
                        .removeClass('has-success');

                }, 1500);
            }

            function handleError(error) {
                error
                    .response.text()
                    .then(function (errorMessage) {
                        frmGroup
                            .removeClass('has-success')
                            .addClass('has-error')
                            .find('.help-block')
                            .text(errorMessage);
                    });
            }

            function handlePlay() {
                fetch(_p.web_ajax + 'document.ajax.php?a=show_safely_play&' + _p.web_cid_query, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    cache: 'reload'
                })
            }

            function handlePause() {
                fetch(_p.web_ajax + 'document.ajax.php?a=show_safely_pause&' + _p.web_cid_query, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    cache: 'reload'
                })
            }

            fetch(_p.web_ajax + 'document.ajax.php?a=show_safely&' + _p.web_cid_query, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                cache: 'reload'
            })
                .then(handleReponse)
                .then(handleBlob)
                .catch(handleError);
        });

        $('body').on('contextmenu', 'audio', function (e) {
            e.preventDefault();

            return;
        });
    });
</script>