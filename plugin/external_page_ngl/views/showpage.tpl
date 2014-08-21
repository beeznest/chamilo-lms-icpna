<div class="row">
    <div class ="span12">
        <div style="margin: 0;" class="page-header">{{ name }}</div>
        <div class="row-fluid" style="height: 100%">
            <iframe id="content_id" marginheight="0" frameborder="0"
                    style="background-color: white; height: 500px; width: 100%;"
                    src="{{ path }}">
            </iframe>
        </div>
    </div>
</div>

<script>
    // Resize right and left pane to full height (HUB 20-05-2010).
    function updateContentHeight() {
        document.body.style.overflow = 'hidden';
        var IE = window.navigator.appName.match(/microsoft/i);
        var innerHauteur = (IE) ? document.body.clientHeight : window.innerHeight;
        if (document.getElementById('content_id')) {
            document.getElementById('content_id').style.height = innerHauteur - ($("footer").height() + 48) + 'px';
        }
    }
    window.onload = updateContentHeight;
    window.onresize = updateContentHeight;
</script>