{% extends "default/layout/main.tpl" %}

{#  1 column  #}
{% block body %}

{# topbar #}
{% include "default/layout/topbar.tpl" %}

<div class="span3 fulluser">

    {# if user is not login show the login form #}
    {% if _u.logged  == 0 %}
    {% include "default/layout/login_form.tpl" %}
    {% endif %}
    <div class="bloque-user">
        <div class="header-logo">
            <img src="{{ _p.web_css }}nuevo_vlearning/img/logo-vlearning.png">
        </div>
        <div class="profile" id="profile-block">
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
                        <a href="{{ _p.web_main }}social/home.php" class="item-cuenta">{{ "MyAccount" | get_lang }}</a>
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
        </div>
        <div class="span12 visible-phone" id="hide-profile-bar">
            <a href="#" id="hide-profile-toggle">
                <i class="icon-chevron-up"></i>
            </a>
        </div>
</div>
<div class="span9 fullpage">
    <div class="page-content">
        <div class="logo-icpna"><img src="{{ _p.web_css }}nuevo_vlearning/img/logo-icpna.png"> </div>
        {% block content %}

        <section id="main_content">
            {{ social_right_content}}
        </section>

        {% endblock %}
    </div>
</div>


{% endblock %}
