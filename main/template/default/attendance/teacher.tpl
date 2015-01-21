<h1 class="page-header">{{ 'MyAttendance' | get_lang }}</h1>
{% if attendances is not empty %}
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>{{ 'Schedule' | get_lang }}</th>
                <th>{{ 'Room' | get_lang }}</th>
                <th>{{ 'Course' | get_lang }}</th>
                <th>{{ 'In at' | get_lang }}</th>
                <th>{{ 'Out at' | get_lang }}</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th>{{ 'Schedule' | get_lang }}</th>
                <th>{{ 'Room' | get_lang }}</th>
                <th>{{ 'Course' | get_lang }}</th>
                <th>{{ 'In at' | get_lang }}</th>
                <th>{{ 'Out at' | get_lang }}</th>
            </tr>
        </tfoot>
        <tbody>
            {% for attendance in attendances %}
                <tr>
                    <td>{{ attendance.schedule }}</td>
                    <td>{{ attendance.room }}</td>
                    <td>{{ attendance.course }}</td>
                    <td>{{ attendance.inAt }}</td>
                    <td>{{ attendance.outAt }}</td>
                </tr>
            {% endfor %}
        </tbody>
    </table>
{% else %}
    <div class="alert alert-info">
        {{ 'NoResults' | get_lang }}
    </div>
{% endif %}
