{% extends template ~ "/layout/page.tpl" %}

{% block body %}
    {% if plugin_main_top %}
        <div class="row">
            <div class="page-main-top" class="col-md-12">
                {{ plugin_main_top }}
            </div>
        </div>
    {% endif %}
    <div class="row">
        <div class="col-md-9 col-md-push-3">
            <div class="page-content">
                {% if plugin_content_top %}
                    <div class="page-content-top">
                        {{ plugin_content_top }}
                    </div>
                {% endif %}

                {{ sniff_notification }}

                {% if home_page_block %}
                    <article id="homepage-home">
                        {{ home_page_block }}
                    </article>
                {% endif %}

                {% block page_body %}
                    {% include template ~ "/layout/page_body.tpl" %}
                {% endblock %}

                {% if welcome_to_course_block %}
                    <article id="homepage-course">
                        {{ welcome_to_course_block }}
                    </article>
                {% endif %}

                {% block content %}
                    {% if content is not null %}
                        <section id="page" class="{{ course_history_page }}">
                            {{ content }}
                        </section>
                    {% endif %}
                {% endblock %}

                {% if announcements_block %}
                    <article id="homepage-announcements">
                        {{ announcements_block }}
                    </article>
                {% endif %}

                {% if course_category_block %}
                    <article id="homepage-course-category">
                        {{ course_category_block }}
                    </article>
                {% endif %}

                {% include template ~ "/layout/hot_courses.tpl" %}

                {% if plugin_content_bottom %}
                    <div id="plugin_content_bottom">
                        {{ plugin_content_bottom }}
                    </div>
                {% endif %}
            </div>
        </div>
        <div class="col-md-3 col-md-pull-9">
            <div class="sidebar">
                {% if plugin_menu_top %}
                    <div class="siderbar-menu-top">
                        {{ plugin_menu_top }}
                    </div>
                {% endif %}

                {% include template ~ "/layout/login_form.tpl" %}

                {% if _u.logged  == 1 %}
                    {{ user_image_block }}
                {% endif %}

                {% if course_block %}
                <!-- block course -->
                <div class="panel-group" id="course" role="tablist" aria-multiselectable="true">
                    <div class="panel panel-default" id="course_block">
                        <div class="panel-heading" role="tab">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#course" href="#courseCollapse" aria-expanded="true" aria-controls="courseCollapse">
                                    {{ 'Courses' | get_lang }}
                                </a>
                            </h4>
                        </div>
                        <div style="" aria-expanded="true" id="courseCollapse" class="panel-collapse collapse in" role="tabpanel">
                            <div class="panel-body">
                                <ul class="list-group">
                                    {% for item in course_block %}
                                    <li class="list-group-item {{ item.class }}">
                                        <span class="item-icon">
                                            {{ item.icon }}
                                        </span>
                                        <a href="{{ item.link }}">{{ item.title }}</a>
                                    </li>
                                    {% endfor %}
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end block course -->
                {% endif %}

                {% if plugin_menu_bottom %}
                    <div class="sidebar-menu-bottom">
                        {{ plugin_menu_bottom }}
                    </div>
                {% endif %}
            </div>
        </div>
    </div>
    {% if plugin_main_bottom %}
        <div class="row">
            <div class="page-main-bottom" class="col-md-12">
                {{ plugin_main_bottom }}
            </div>
        </div>
    {% endif %}
{% endblock %}
