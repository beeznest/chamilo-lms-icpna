<div class="row">
    <div class="col-sm-6 col-sm-offset-3">
        {{ form }}
    </div>
</div>
<div class="row">
    <div class="col-sm-6 col-sm-offset-3" id="player" style="display: none;">
        <p class="clearfix">
            <div class="pull-right">
                <span id="player-current-time">00:00</span>
                /
                <span id="player-duration">00:00</span>
            </div>
            <button class="btn btn-primary btn-sm" id="player-btn-play">
                <span class="fa fa-play fa-fw" aria-hidden="true"></span>
                <span class="sr-only">{{ 'Play'|get_lang }}</span>
            </button>
            <button class="btn btn-primary btn-sm" id="player-btn-pause" style="display: none;">
                <span class="fa fa-pause fa-fw" aria-hidden="true"></span>
                <span class="sr-only">{{ 'Pause'|get_lang }}</span>
            </button>
            <button class="btn btn-primary btn-sm" id="player-btn-stop" style="display: none;">
                <span class="fa fa-square fa-fw" aria-hidden="true"></span>
                <span class="sr-only">{{ 'Stop'|get_lang }}</span>
            </button>
        </p>
        <div class="clearfix">
            <input type="range" min="0" max="100" step="0.01" value="0" id="player-range" class="btn btn-default">
        </div>
    </div>
</div>

<script>
    $(document).on('ready', function () {
        var audio = null;

        function handleCanPlay() {
            $('#player-duration').text(
                parseTime(audio.duration)
            );
            $('#player-range').attr('max', audio.duration);
        }

        function handleTimeUpdate() {
            $('#player-range').val(audio.currentTime);
            $('#player-current-time').text(
                parseTime(audio.currentTime)
            );

            if (audio.currentTime >= audio.duration) {
                $('#player-btn-stop').hide();
                $('#player-btn-pause').hide();
                $('#player-btn-play').show();
            }
        }

        function parseTime(time) {
            var minutes = "0" + Math.floor(time / 60);
            var seconds = "0" +  Math.floor(time - minutes * 60);
            return minutes.substr(-2) + ":" + seconds.substr(-2);
        }

        $('#player-btn-play').on('click', function () {
            $('#player-btn-pause').show();
            $('#player-btn-stop').show();
            $('#player-btn-play').hide();

            if (!audio) {
                return;
            }

            audio.play();
        });
        $('#player-btn-pause').on('click', function () {
            $('#player-btn-pause').hide();
            $('#player-btn-play').show();

            if (!audio) {
                return;
            }

            audio.pause();
        });
        $('#player-btn-stop').on('click', function () {
            $('#player-btn-pause').hide();
            $('#player-btn-play').show();

            if (!audio) {
                return;
            }

            audio.pause();
            audio.currentTime = 0;
        });
        $('#player-range').on('change', function () {
            if (!audio) {
                return;
            }

            audio.currentTime = this.value;
        });

        $('form#validate').on('submit', function (e) {
            e.preventDefault();

            var frm = $(this),
                formData = new FormData(this),
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

                audio = new Audio();
                audio.oncanplay = handleCanPlay;
                audio.onplay = function () {
                    fetch(_p.web_ajax + 'document.ajax.php?a=show_safely_play&' + _p.web_cid_query, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                        cache: 'reload'
                    });
                };
                audio.onpause = function () {
                    fetch(_p.web_ajax + 'document.ajax.php?a=show_safely_pause&' + _p.web_cid_query, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                        cache: 'reload'
                    });
                };
                audio.ontimeupdate = handleTimeUpdate;
                audio.src = URL.createObjectURL(blob);

                frm.find('#validate_pass').parents('.form-group').remove();
                frm.find(':submit').parents('.form-group').remove();

                $('#player').show();

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