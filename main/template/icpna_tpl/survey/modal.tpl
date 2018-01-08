{% set tutors_names = get_tutors_names() %}
{% if tutors_names %}
    <p>{{ 'SurveyModalIntro'|get_lang|format(get_tutors_names()) }}</p>
{% endif %}
<p>{{ 'SurveyUntilDateX'|get_lang|format(survey.availTill|api_convert_and_format_date(1)) }}</p>
<p>{{ 'SurveyModalEnd'|get_lang }}</p>
<p class="text-center">
    {#<a href="{{ _p.web_main }}survey/fillsurvey.php?{{ {"course": _c.code, "invitationcode":invitation.invitationCode }|url_encode }}&{{ _p.web_cid_query }}"#}
    <a href="{{ _p.web_main }}survey/survey_list.php?{{ _p.web_cid_query }}"
       class="btn btn-primary">
        {{ 'ClickHereToOpenSurvey'|get_lang }}
        <span class="fa fa-arrow-right" aria-hidden="true"></span>
    </a>
</p>
