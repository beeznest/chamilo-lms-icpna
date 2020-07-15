{# Displayed from exercise_result.php #}

{{ page_top }}

{{ page_content }}

{% if not adaptive_result is empty %}
    <hr>

    <div class="row">
        <div class="col-md-8 col-md-offset-2 col-lg-6 col-lg-offset-3">
            <img src="{{ adaptive_result.quiz_dir_web ~ adaptive_result.destination_result.hash }}.png" class="pull-right">
            <p class="lead">{{ 'LevelReachedX'|get_lang|format(adaptive_result.destination_result.achievedLevel) }}</p>
            <p>{{ adaptive_result.user_complete_name }}</p>
            <p>{{ 'ResultHashX'|get_lang|format(adaptive_result.destination_result.hash) }}</p>
            <p>
                <a href="{{ _p.web_main }}exercise/progressive_adaptive_results.php?{{ { 'hash':adaptive_result.destination_result.hash, 'origin': adaptive_result.origin }|url_encode }}" target="_blank">
                    {{ 'SeeResults'|get_lang }}
                </a>
            </p>
            {% if adaptive_result.mail_sent %}
                <hr>
                <div class="alert alert-info">{{ 'TheQuizResultsWereSentToYourEmail'|get_lang }}</div>
            {% endif %}
        </div>
    </div>
{% endif %}

{{ page_bottom }}
