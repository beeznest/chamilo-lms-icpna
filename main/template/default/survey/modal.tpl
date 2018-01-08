<strong>{{ survey.title }}</strong>
{% if survey.availFrom or survey.availTill %}
    <ul>
        {% if survey.availFrom %}
            <li>{{ 'FromDateX'|get_lang|format(survey.availFrom|api_convert_and_format_date(1)) }}</li>
        {% endif %}
        {% if survey.availTill %}
            <li>{{ 'UntilDateX'|get_lang|format(survey.availTill|api_convert_and_format_date(1)) }}</li>
        {% endif %}
    </ul>
{% endif %}
<p>
    <a href="{{ _p.web_main }}survey/fillsurvey.php?{{ {"course": _c.code, "invitationcode":invitation.invitationCode }|url_encode }}&{{ _p.web_cid_query }}"
       class="btn btn-link">
        {{ 'ClickHereToOpenSurvey'|get_lang }}
        <span class="fa fa-arrow-right" aria-hidden="true"></span>
    </a>
</p>
