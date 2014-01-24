<!DOCTYPE html>
<!--[if lt IE 7]> <html lang="{{ document_language }}" class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>    <html lang="{{ document_language }}" class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>    <html lang="{{ document_language }}" class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--><html lang="{{ document_language }}" class="no-js"> <!--<![endif]-->
<head>
{% include "default/layout/head.tpl" %}
</head>
<body dir="{{ text_direction }}" class="{{ section_name }}">
<noscript>{{ "NoJavascript"|get_lang }}</noscript>

{% if show_header == true %}
    <div class="skip">
        <ul>
            <li><a href="#menu">{{ "WCAGGoMenu"|get_lang }}</a></li>
            <li><a href="#content" accesskey="2">{{ "WCAGGoContent"|get_lang }}</a></li>
        </ul>
    </div>
    <div id="wrapper">
        {# Bug and help notifications #}
        <ul id="navigation" class="notification-panel">
            {{ help_content }}
            {{ bug_notification_link }}
        </ul>
            
        {# topbar #}
        {% include "default/layout/topbar.tpl" %}

        <div id="main" class="container">
    <header>
	<div class="row">
		<div id="header_left" class="col-lg-4">
			<div class="main-inicio">
            <div class="home-ico">
            <a href="{{ _p.web }}">Inicio</a>
            </div>
            </div>
		</div> 
		<div id="header_center" class="col-lg-4">
        	<div class="main-inicio">
			{# logo #} 
            {{ logo }}
            </div>
		</div>
		<div id="header_right" class="col-lg-4">
			<div class="main-inicio">
            <div class="ayuda-ico">
            <a href="{{ _p.web }}">Ayuda</a>
            </div>
            </div>
		</div>
	</div>
    {% if _u.logged == 1 %}
    <div class="row">
        <div class="container franja-celeste">
            <div id="header_left" class="col-lg-8">
             <div class="main-subinicio">
                Curso V-Learning Adultos > <a href="#">Módulo 3</a>
             </div>
            </div>
            <div class="col-lg-4">
                <a href="#">Proceso: Módulo 3</a>
                <div id="animacion">
                    <div class="colores_y_demas uno">
                     <span>70%</span></div>
                    </div>
                </div>
        </div>
    </div>
    {% endif %}
     <!--  {# menu #}
     {% include "default/layout/menu.tpl" %} -->
</header>


            <div id="top_main_content" class="row">
                  {# course navigation links/shortcuts need to be activated by the admin #}
            {% include "default/layout/course_navigation.tpl" %}
{% endif %}
