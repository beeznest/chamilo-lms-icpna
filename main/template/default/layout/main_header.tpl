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
<!-- HEADER Y PERFIL DE USUARIO -->
<div class="row-fluid">
  <div class="span12">
    <div class="span3">
        <div class="home-ico">
          <a href="{{ _p.web }}">Inicio</a>
        </div>
      </div>
    <div class="span6">
          {# logo #} <!--LLama al logotipo -->
          {{ logo }}
    </div>
    <div class="span3">
         <div class="perfil-user">
            <div id='cssmenu'>
            <ul>
               <li class='has-sub'><a href='#'><span>
               <img src="http://icpna.chamilo.net/main/upload/users/1/1/medium_1_52cd7d4aa8bf5_USER.jpg" class="imagen-username" />
               <span class="welcome">Bienvenido</span><br>
               <span class="usermane">Aberto Cordero</span>
               </span></a>
                  <ul>
                     <li class="home"><a href='#'><span>Pagina Principal</span></a></li>
                     <li class="edit"><a href='#'><span>Actualización de datos</span></a></li>
                     <li class='closed'><a href='#'><span>Salir</span></a></li>
                  </ul>
               </li>
            </ul>
            </div>
            </div>
    </div>
  </div>
</div>

<!-- FIN DEL PERFIL DE USUARIOS -->
    {% if _u.logged == 1 %}
    <!-- DATOS DE LA FRANJA CELESTE DATOS DE UBICACION -->
    <div class="row-fluid">
        <div class="span12 celeste">
          <div class="row-fluid">
            <div class="span7"> <div class="main-subinicio">Curso V-Learning Adultos - <a href="#">Módulo 3</a></div></div>
            <div class="span5">           
              <div class="row-fluid">
                <div class="span9">
                    <a href="#">Proceso: Módulo 3</a>
                    <div id="animacion">
                    <div class="color-bar-process uno"><span>70%</span></div>
                </div>
                </div>
                <div class="span2">
                    <a href="#">
                      <img src="http://icpna.chamilo.net/main/css/test_ICPNA/images/help.png">
                    </a>
                </div>
              </div>
            </div>
          </div>
        </div>
    </div>
    <!-- FIN DE LA FRANJA CELESTE DE UBICACION -->
    {% endif %}
     <!--  {# menu #}
     {% include "default/layout/menu.tpl" %} -->
</header>


            <div id="top_main_content" class="row">
                  {# course navigation links/shortcuts need to be activated by the admin #}
            {% include "default/layout/course_navigation.tpl" %}
{% endif %}
