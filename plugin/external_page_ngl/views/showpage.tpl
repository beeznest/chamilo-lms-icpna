<div class="row">
    <div class ="span12">
        <div style="margin: 0;" class="page-header">{{ name }}</div>
        <div class="row-fluid" style="height: 100%">
            <div class="span6 offset3">
                <form id="loginForm" name="loginForm" method="post" action="{{ path }}" target="_blank">
                    <input name="time" value="" type="hidden">
                    <input name="prod" value="" type="hidden">
                    <input name="login" id="login" value="{{ username }}" type="hidden">
                    <input name="password" id="password" value="{{ password }}" type="hidden">
                    <input name="closePriorIfNotResumeSession" value="true" type="hidden">
                    <input name="resumeSession" value="false" type="hidden">
                    <input name="closePrior" value="true" type="hidden">
                </form>
                <p class="lead">{{ msg_wait }}</p>
                <div class="progress progress-striped active">
                    <div class="bar" style="width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    /**
     * Gets the client's timezone information for use in logging the user into iLrn.
     *
     * @return "<winter offset millis>;<winter to summer change millis | nothing>;<summer offset millis>;<summer to winter change millis | nothing>"
     */
    function getClientTime()
    {
        // The client's current time.
        var now = new Date();

        var begin = new Date(now.getFullYear(), 0, 1, 0, 0, 0, 0);
        var end = new Date(now.getFullYear() + 1, 0, 1, 0, 0, 0, 0);
        var summer = new Date(now.getFullYear(), 6, 1, 0, 0, 0, 0);

        // The date DST goes into effect
        var firstChange;
        // The date DST goes out of effect
        var lastChange;
        // The offset in the winter
        var winterOffset = getGMTOffset(begin);
        // The offset in the summer
        var summerOffset = getGMTOffset(summer);

        // If the timezone offsets are different, we found a DST status change
        if (winterOffset != summerOffset)
        {
            // Get the exact time of the first change
            firstChange = findDSTChange(begin, summer);

            // Get the exact time of the last change
            lastChange = findDSTChange(summer, end);
        }

        return winterOffset + ';'
                + (firstChange != null ? firstChange.getTime() : '') + ';'
                + summerOffset + ';'
                + (lastChange != null ? lastChange.getTime() : '');
    }

    /**
     * Gets the offset from GMT in milliseconds.
     *
     * @param date The date for which to retrieve the offset.
     * @return The offset of date in milliseconds from GMT.
     */
    function getGMTOffset(date)
    {
        // getTimezoneOffset() returns the number of seconds to add to the local
        // time to get the GMT time. The offset from GMT in milliseconds is then
        // the negative of that value multiplied by the number of milliseconds in
        // a minute (60000).
        return date.getTimezoneOffset() * -60000;
    }

    /**
     * Performs a recursive binary search for the second that the timezone offset changes.
     *
     * @param begin The lower date bound.
     * @param end The upper date bound.
     * @return The second of the timzeone offset change.
     */
    function findDSTChange(begin, end)
    {
        begin.setTime(getMinuteFloorMillis(begin));
        end.setTime(getMinuteFloorMillis(end));

        // Get the difference (in minutes) between the two endpoints.
        var diffMinutes = (end.getTime() - begin.getTime()) / 60000;

        // If the endpoints are the same
        if (diffMinutes == 0)
            // return one of the dates (both are correct)
            return begin;
        // If the endpoints are one minute apart
        else if (diffMinutes == 1)
            // We know the time change happens between begin and end,
            // so end is the minute of the time change.
            return end;

        // Compute the the halfway point
        // Note: (diffMinutes / 2) * 60000 = diffMinutes * 30000 = number of milliseconds to add to begin
        var halfway = new Date(begin.getTime() + diffMinutes * 30000);

        // halfway point
        if (getGMTOffset(halfway) != getGMTOffset(begin))
            return findDSTChange(begin, halfway);
        // Otherwise it must change between the halfway point and the end point
        else
            return findDSTChange(halfway, end);
    }

    /**
     * Gets the milliseconds for the most recent whole second.
     * 
     * @param date The date to calculate
     * @returns The number of milliseconds for the most recent whole second.
     */
    function getMinuteFloorMillis(date)
    {
        var time = date.getTime();
        return time - time % 60000;
    }

    $(document).on('ready', function() {
        var $loginForm = $('#loginForm');

        var txtNormalLogin = $loginForm.find('#login').val();
        var txtNormalPassword = $loginForm.find('#password').val();

        txtNormalLogin = $.trim(txtNormalLogin);
        txtNormalPassword = $.trim(txtNormalPassword);

        if (txtNormalLogin && txtNormalPassword) {
            var clientTime = getClientTime();

            $loginForm.find('time').val(clientTime);

            $loginForm.submit();

            location.href = '{{ back_to }}';
        }
    });
</script>