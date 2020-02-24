<table class="table table-hover table-striped">
    <thead>
    <tr>
        <th>{{ 'Title'|get_lang }}</th>
        <th>{{ 'IsVisibleFor'|get_plugin_lang('PopupsPlugin') }}</th>
        <th class="text-right" style="width: 95px;">{{ 'Actions'|get_lang }}</th>
    </tr>
    </thead>
    <tbody>

    {% for popup in pagination %}
        <tr>
            <td{% if not popup.visible %} class="text-muted"{% endif %}>{{ popup.title }}</td>
            <td>
                {{ popup.getStringVisibleFor }}
            </td>
            <td class="text-right">
                {% if popup.visible %}
                    <a href="{{ _p.web_self ~ '?' ~ {'id': popup.id, 'action': 'invisible'}|url_encode }}">
                        {{ 'visible.png'|img(22, 'MakeInvisible'|get_lang) }}
                    </a>
                {% else %}
                    <a href="{{ _p.web_self ~ '?' ~ {'id': popup.id, 'action': 'visible'}|url_encode }}">
                        {{ 'invisible.png'|img(22, 'MakeVisible'|get_lang) }}
                    </a>
                {% endif %}
                <a href="{{ _p.web_self ~ '?' ~ {'id': popup.id, 'action': 'edit'}|url_encode }}">
                    {{ 'edit.png'|img(22, 'Edit'|get_lang) }}
                </a>
                <a href="{{ _p.web_self ~ '?' ~ {'id': popup.id, 'action': 'delete'}|url_encode }}">
                    {{ 'delete.png'|img(22, 'Delete'|get_lang) }}
                </a>
            </td>
        </tr>
    {% endfor %}
    </tbody>
</table>

<nav aria-label="{{ 'Pagination'|get_plugin_lang('PopupsPlugin') }}">
    {{ pagination }}
</nav>