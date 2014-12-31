{% extends "default/layout/main.tpl" %}

{% block body %}
    {# topbar #}
    {% include "default/layout/topbar.tpl" %}


<div class="span3">

    {# if user is not login show the login form #}
    {% if _u.logged  == 0 %}
    {% include "default/layout/login_form.tpl" %}
    {% endif %}
    <div class="bloque-user">
        <div class="header-logo">
            <img src="{{ _p.web_css }}nuevo_vlearning/img/logo-vlearning.png">
        </div>
        <div class="profile">
            <div class="home"><a href="{{ _p.web_modules }}"><img src="{{ _p.web_css }}nuevo_vlearning/img/userlogin/home.png"></a></div>
            <div class="user-datos">
                <div class="image-user">
                    <div class="img-user-width">
                        <img src="{{ _u.avatar}}" class="img-circle">
                    </div>
                </div>
                <div class="name">{{_u.firstname}}</div>
                <div class="lastname">{{_u.lastname}}</div>
                <div class="cuenta"><a href="{{ _p.web_main }}social/home.php" class="item-cuenta">Mi cuenta</a></div>
                <div class="options">
                    <div class="row-fluid">
                        <div class="span6 help"><a herf="#" data-toggle="modal" data-target="#FAQ"><img src="{{ _p.web_css }}nuevo_vlearning/img/userlogin/icon_help.png">{{"Help"|get_lang}}</a></div>
                        <div class="span6 closed"><a href="{{ logout_link }}">{{ "Logout" |get_lang }}<img src="{{ _p.web_css }}nuevo_vlearning/img/userlogin/icon_closed.png"/> </a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="span9">
    {% for block_item in blocks %}
    <div id="tabs-{{loop.index}}" class="span4">
        <div class="block-settings">
            <h4>{{block_item.icon}} {{block_item.label}}</h4>
            <div style="list-style-type:none">
                {{ block_item.search_form }}
            </div>
            {% if block_item.items is not null %}
            <ul class="options">
                {% for url in block_item.items %}
                <li><a href="{{url.url}}">{{ url.label }}</a></li>
                {% endfor %}
            </ul>
            {% endif %}

            {% if block_item.extra is not null %}
            <div>
                {{ block_item.extra }}
            </div>
            {% endif %}
        </div>
    </div>
    {% endfor %}
</div>
{% endblock %}