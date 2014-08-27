{% if icpna_number_messages.show_script is not null and _u.logged == 1 %}
<script>
    $(document).on('ready', function() {
        $(".nav li a:contains('{{icpna_number_messages.variable}}')").html(function() {
            var tabHTML = '{{ icpna_number_messages.tab_name }}';
            tabHTML += ' ';
            tabHTML += '<span class="badge badge-warning">{{ icpna_number_messages.number_messages }}</span>';

            return tabHTML;
        });
    });
</script>
{% endif %}