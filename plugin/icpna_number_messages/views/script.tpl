{% if icpna_number_messages.show_script is not null and _u.logged == 1 %}
<script>
    $(document).on('ready', function () {
        $(".nav li a:contains('{{icpna_number_messages.variable}}')").html('<span class="badge badge-warning">{{ icpna_number_messages.number_messages }}</span>');
    });
</script>
{% endif %}