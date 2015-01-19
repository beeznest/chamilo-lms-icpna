<div class="row">
    <div class ="span12">
        <div style="margin: 0;" class="page-header">{{ name }}</div>
        <div class="row-fluid" style="height: 100%">
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
            height: 380,
            width: '100%'
        });
    });
</script>
