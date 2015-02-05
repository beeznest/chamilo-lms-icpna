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
                    <div id="header_left" class="span4">
                        {# logo #}
                        {{ logo }}

                        {# plugin_header left #}
                        {% if plugin_header_left is not null %}
                            <div id="plugin_header_left">
                                {{ plugin_header_left }}
                            </div>
                        {% endif %}
                    </div>
                    <div id="header_center" class="span3">
                        {# plugin_header center #}
                        {% if plugin_header_center is not null %}
                            <div id="plugin_header_center">
                                {{ plugin_header_center }}
                            </div>
                        {% endif %}
                        &nbsp;
                    </div>
                    <div id="header_right" class="span5">
                        {# NOTIFICATIONS #}
                        {# plugin_header right #}
                        {% if plugin_header_right is not null %}
                            <div id="plugin_header_right">
                                {{ plugin_header_right }}
                            </div>
                        {% endif %}
                        {# IN/OUT #}
                        {% if _u.status == 1 and _p.is_in_room and _u.coach_session_id > 0 %}
                            <div id="in-out-buttons" class="in-out-buttons-space">
                                <button class="btn btn-large btn-success btn-xlarge {% if _p.count_active_in > 0 %} hide-important {% endif %}" type="button" id="btn_in_session" name="btn_in_session">IN</button>
                                <button class="btn btn-large btn-danger btn-xlarge {% if _p.count_active_in == 0 %} hide-important {% endif %}" type="button" id="btn_out_session" name="btn_out_session">OUT</button>
                                <input type="hidden" name="in_course_id" id="in_course_id" value="{{ _u.coach_course_id }}" />
                                <input type="hidden" name="in_session_id" id="in_session_id" value="{{ _u.coach_session_id }}" />
                            </div>
                        {% endif %}
                        {# END IN/OUT #}
                        <div class="notifications-container">
                            <ul id="notifications" class="nav nav-pills pull-right">
                                {{ notification_menu }}
                            </ul>

                        </div>
                        {# END NOTIFICATIONS #}
                    </div>
                </div>

                {% if plugin_header_main %}
                    <div class="row">
                        <div class="span12">
                            <div id="plugin_header_main">
                                {{ plugin_header_main }}
                            </div>
                        </div>
                    </div>
                {% endif %}

                {# menu #}
                {% include "default/layout/menu.tpl" %}

                {# breadcrumb #}
                {{ breadcrumb }}
            </header>

            <div id="top_main_content" class="row">
            {# course navigation links/shortcuts need to be activated by the admin #}
            {% include "default/layout/course_navigation.tpl" %}
{% endif %}