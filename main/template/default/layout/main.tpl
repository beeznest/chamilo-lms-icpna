<!DOCTYPE html>
<!--[if lt IE 7]> <html lang="{{ document_language }}" class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>    <html lang="{{ document_language }}" class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>    <html lang="{{ document_language }}" class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--><html lang="{{ document_language }}" class="no-js"> <!--<![endif]-->
<head>
    {% include "default/layout/head.tpl" %}
</head>
<body>
{% block header %}
{% endblock %}
<!-- START PAGE -->
<div id="page-wrap">
    <div class="container">
        <div class="row">
                {% block body %}
                {% endblock %}
        </div>
    </div>

</div>
<!-- FIN PAGE -->
{% block footer %}
{% include "default/layout/main_footer.tpl" %}
{% endblock %}
