{% extends "default/layout/main.tpl" %}

{% block body %}    
    {# Main content #}
   
    {#  Right column  #}
    <div class="span3 menu-column"> 
        
        {# if user is not login show the login form #}
        {% if _u.logged  == 0 %}
            {% include "default/layout/login_form.tpl" %}
        {% endif %}
        <div class="bloque-user">
            <div class="header-logo">
                <img src="{{ _p.web_css }}nuevo_vlearning/img/logo-vlearning.png">
            </div>
            <div class="profile">
                <div class="home"><a href="#"><img src="{{ _p.web_css }}nuevo_vlearning/img/userlogin/home.png"></a></div>
                <div class="user-datos">
                    <div class="image-user">
                        <div class="img-user-width">
                            <img src="{{ _u.avatar}}" class="img-circle">
                        </div>
                    </div>
                    <div class="name">{{_u.firstname}}</div>
                    <div class="lastname">{{_u.lastname}}</div>
                    <div class="cuenta"><a href="#" class="item-cuenta">Mi cuenta</a></div>
                    <div class="options">
                        <div class="row-fluid">
                            <div class="span6 help"><a herf="#"><img src="{{ _p.web_css }}nuevo_vlearning/img/userlogin/icon_help.png"> Ayuda</a></div>
                            <div class="span6 closedr"><a herf="{{ logout_link }}">{{ "Logout" |get_lang }}<img src="{{ _p.web_css }}nuevo_vlearning/img/userlogin/icon_closed.png"> </a></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    
               
    </div>
    <div class="span9 content-column">
        
        {#  Plugin bottom  #}
        {% if plugin_content_top %}
            <div id="plugin_content_top">
                {{ plugin_content_top }}
            </div>
        {% endif %}
        
        {#  Portal homepage  #}
        {% if home_page_block %}
            <section id="homepage">
                <div class="row">
                    <div class="span9">
                    {{ home_page_block }}
                    </div>
                </div>
            </section>
        {% endif %}
        
        {#  ??  #}
        {{ sniff_notification }}
        
        {% include "default/layout/page_body.tpl" %}
                
        {#  Welcome to course block  #}
        {% if welcome_to_course_block %}      
            <section id="welcome_to_course">
            {{ welcome_to_course_block }}
            </section>
        {% endif %}
                
        {% if content is not null %}
            <section id="main_content">
                {{ content }}
            </section>
        {% endif %}
                
        {#  Announcements  #}
        {% if announcements_block %}      
            <section id="announcements">
            {{ announcements_block }}
            </section>
        {% endif %}
        
        {# Course categories (must be turned on in the admin settings) #}
        {% if course_category_block %}
            <section id="course_category">
                <div class="row">
                    <div class="span9">
                    {{ course_category_block }}
                    </div>
                </div>
            </section>
        {% endif %}
                    
        {#  Hot courses template  #}        
        {% include "default/layout/hot_courses.tpl" %}        
        
        {#  Content bottom  #}
        {% if plugin_content_bottom %}       
            <div id="plugin_content_bottom">
                {{plugin_content_bottom}}
            </div>
        {% endif %}
        &nbsp;
    </div>
        
{% endblock %}
