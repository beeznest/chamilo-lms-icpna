<div class="row">
    <div class ="span12">
        <div class="row-fluid">
            <iframe id="content_id" marginheight="0" frameborder="0" src="{{ path }}">
            </iframe>
        </div>
    </div>
</div>
<script>
    $(document).on('ready', function () {
        $('body').css('overflow', 'hidden');

        $('#content_id').css({
            backgroundColor: '#FFF',
            height: 400,
            width: '100%'
        });
    });
</script>