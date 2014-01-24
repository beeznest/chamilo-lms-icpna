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
        <!-- END HEADER, START USER PROFILE BLOCK -->

        {# only show user block and breadcrumb if user is logged in#}
        
        <div class="row-fluid">
        <div class="span12">
        <div class="span3">
            <div class="home-ico">
              <a href="{{ _p.web }}user_portal.php">{{"Home"|get_lang}}</a>
            </div>
        </div>
            <div class="span6">
              {# logo #} <!--LLama al logotipo -->
              {{ logo }}
            </div>
            <div class="span3">
                  {% if _u.logged == 1 %}                  
                  <div class="user-profile-block">
                  <div id='cssmenu'>
                  <ul>
                      <li class='has-sub'><a href='#'><span>
                      <img src="{{ _u.avatar}}" class="user-pic" />
                      <span class="welcome">{{"Welcome"|get_lang}}</span><br />
                      <span class="username">{{_u.complete_name}}</span></span></a>
                      <ul>
                         <li class="home"><a href='{{ _p.web_main }}social/home.php'><span>{{ "Profile" |get_lang }}</span></a></li>
                         <li class="email"><a href='{{ _p.web_main }}messages/inbox.php?f=social'><span>{{ "Inbox" |get_lang }}</span></a></li>
                         <li class="edit"><a href='{{ _p.web_main }}auth/profile.php'><span>{{ "EditProfile" |get_lang }}</span></a></li>
                         <li class="close"><a href='{{ logout_link }}'><span>{{ "Logout" |get_lang }}</span></a></li>
                      </ul>
                      </li>
                  </ul>
                </div>
                </div>
              {% endif %}
        </div>
    </div>

<!-- END OF USER PROFILE BLOCK -->
<!-- START OF LIGHT BLUE RIBBON -->
 
    <div class="row-fluid">
        <div class="span12 light-blue-bar">
          <div class="row-fluid">

              <div class="column-one">
                  {{ _s.site_name }}
                  {% if course_is_set %} - <a href="{{ breadcrumb_course_url }}">{{ breadcrumb_course_title }}</a>
                  {% endif %}
              </div>
              <div class="column-two">
                  {% if course_is_set %}
                  <a href="{{ breadcrumb_course_url }}">{{"Progress"|get_lang}}</a>
                  {% else %}
                    &nbsp;
                  {% endif %}
              </div>
              <div class="column-tree">
                        {% if course_is_set %}
                          <div id="bar-animation">
                          <div class="color-bar-process blue-back-bar"><span>{{ course_progress }}%</span></div>
                          </div>
                        {% else %}
                        &nbsp;
                        {% endif %}
              </div>
              <div class="column-four">
                        
                        <a href="{{ help_url }}" class="ajax ico-help">
                          <img src="{{ _p.web_css }}{{ css_style }}/images/help.png">
                        </a>
                        

              </div>

          </div>
        </div>
    </div>
    
    <!-- END OF LIGHT BLUE BREADCRUMB 
     {# menu #}
    {% include "default/layout/menu.tpl" %} -->
</header>
            <div id="top_main_content" class="row">
            {# course navigation links/shortcuts need to be activated by the admin #}
            {% include "default/layout/course_navigation.tpl" %}
{% endif %}