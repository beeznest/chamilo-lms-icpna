<!DOCTYPE html>
<!--[if lt IE 7]> <html lang="{{ document_language }}" class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>    <html lang="{{ document_language }}" class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>    <html lang="{{ document_language }}" class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--><html lang="{{ document_language }}" class="no-js"> <!--<![endif]-->

    <head>
        {% include "default/layout/head.tpl" %}

    </head>
    <body {% if social is not defined %} class="no-background" {% endif %}>
        {% if social is defined %}
            {% include "default/layout/main_header.tpl" %}
            {#
                show_header and show_footer templates are only called when using the Display::display_header and Display::display_footer
                for backward compatibility we suppose that the default layout is one column which means using a div with class span12
            #}
            <div id="page-wrap">
                <div class="container">
                    <div class="row">
                        <div id="header-container" {% if isInLP %} style="display: none;" {% endif %}>
                            <div class="span3 fulluser header-bar">
                                <div class="bloque-user">
                                    <div class="header-logo">
                                        <a href="{{ _p.web_modules }}">
                                            <img src="{{ _p.web_css }}nuevo_vlearning/img/logo-vlearning.png">
                                        </a>
                                    </div>
                                    {% if social >= 1 or isInLP %}
                                        <div class="profile profile-block-mobile" id="profile-block">
                                            <div class="home"><a href="{{ _p.web_modules }}"><img src="{{ _p.web_css }}nuevo_vlearning/img/userlogin/home.png"></a></div>
                                            <div class="logo-icpna-movil">
                                                <img src="{{ _p.web_css }}nuevo_vlearning/img/logo-icpna.png">
                                            </div>
                                            <div class="user-datos">
                                                <div class="image-user">
                                                    <div class="img-user-width">
                                                        <img src="{{ _u.avatar}}" class="img-circle">
                                                    </div>
                                                </div>
                                                <div class="name">{{_u.firstname}}</div>
                                                <div class="lastname">{{_u.lastname}}</div>
                                                <div class="cuenta">
                                                    <div class="boton-movil">
                                                        <a href="{{ _p.web_main }}social/home.php" class="item-cuenta">Mi cuenta</a>
                                                    </div>
                                                </div>
                                                {{ social_left_content }}
                                                <div class="options">
                                                    <div class="row-fluid">
                                                        <div class="span6 help">
                                                            <div class="boton-movil">
                                                                <a href="#help" data-toggle="modal"><img src="{{ _p.web_css }}nuevo_vlearning/img/userlogin/icon_help.png">{{"Help"|get_lang}}</a>
                                                            </div>
                                                        </div>
                                                        <div class="span6 closed">
                                                            <div class="boton-movil boton-exit">
                                                                <a href="{{ logout_link }}">{{ "Logout" |get_lang }}<img src="{{ _p.web_css }}nuevo_vlearning/img/userlogin/icon_closed.png"/> </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            {% if isInLP %}
                                            </div>
                                        {% endif %}
                                    {% endif %}
                                </div>
                            </div>
                            {% if isInLP %}
                                <div class="span9">
                                    <div class="row">
                                        <div class="span3">
                                            <div class="closed-scrom">
                                                 <a class="btn btn-white" href="{{ _p.web }}index.php?logout=logout&uid=4862">Logout<img src="{{ _p.web_css }}nuevo_vlearning/img/userlogin/icon_closed.png"> </a>
                                            </div>
                                        </div>
                                        <div class="span6">
                                            <div class="logo-icpna" style="text-align: right;">

                                                <img src="{{ _p.web_css }}nuevo_vlearning/img/logo-icpna.png">
                                            </div>
                                        </div>
                                    </div>
                                    {% if lesson_progress_bar is defined %}
                                        <div class="row-fluid lesson-top-buttons">
                                            <div class="span6">
                                                <div class="btn btn-large btn-white pull-left">
                                                    <a href="{{_p.web_course}}{{_c.code}}/?id_session={{_c.session_id}}">{{ _c.title }}</a>
                                                </div>
                                            </div>
                                            <div class="span6">
                                                <div class = "btn btn-large btn-white pull-right">
                                                    <a href="{{_p.web_modules}}">{{ "Vlearning" |get_lang }}</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row-fluid">
                                            <div class="span8 hidden-phone"></div>
                                            <div class="span4 user-advanced">
                                                <div class="lesson-bar">
                                                    {{lesson_progress_bar}}
                                                </div>
                                            </div>
                                        </div>
                                    {% endif %}
                                </div>
                            {% endif %}
                        </div>
                        {% if not isInLP %}
                            <div class="span12 visible-phone" id="hide-profile-bar">
                                <a href="#" id="hide-profile-toggle">
                                    <i class="icon-chevron-up"></i>
                                </a>
                            </div>
                        {% endif %}
                        {% if lesson_progress_bar %}
                            <div class="span12" id="hide-header-bar">
                                <a href="#" id="hide-header-toggle">
                                    <i class="icon-chevron-down"></i>
                                </a>
                            </div>
                        {% endif %}
                    {% endif %}
