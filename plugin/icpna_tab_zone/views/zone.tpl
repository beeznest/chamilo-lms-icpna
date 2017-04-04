<div class="row">
    <div class ="span12">
        <div class="row-fluid">
            <iframe id="content_id" marginheight="0" frameborder="0" allowfullscreen src="{{ path }}">
            </iframe>
        </div>
    </div>
</div>
<script>
    $(document).on('ready', function () {
        var $iframe = $(temp0);

        $iframe.on('load', function () {
            var height = this.scrollHeight + this.offsetTop;

            $iframe.height(height);
        });

        $(window).on('resize', function () {
            var ratioHeight = $iframe.width() / (4/3),
                height = this.scrollHeight + this.offsetTop;

            $iframe.height(ratioHeight > height ? ratioHeight : height);
        });

        $('#content_id').css({
            backgroundColor: '#FFF',
            width: '100%'
        });
    });
</script>