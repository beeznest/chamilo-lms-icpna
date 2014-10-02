<?php

function getClientTime()
{
    $year = date('Y');

    $begin = new DateTime();
    $begin->setDate($year, 1, 1);

    $end = new DateTime();
    $end->setDate($year + 1, 1, 1);

    $summer = new DateTime();
    $summer->setDate($year, 7, 1);

    $firstChange = null;
    $lastChange = null;

    $winterOffset = getGMTOffset($begin);
    $summerOffset = getGMTOffset($summer);

    if ($winterOffset != $summerOffset) {
        $firstChange = findDSTChange($begin, $summer);
        $lastChange = findDSTChange($summer, $end);
    }

    $clientTimeToString = "$winterOffset;";
    
    if ($firstChange != null) {
        $clientTimeToString .= "{$firstChange->getTimestamp()};";
    }
    
    $clientTimeToString .= "$summerOffset;";
    
    if ($lastChange != null) {
        $clientTimeToString .= "{$lastChange->getTimestamp()}";
    }

    return $clientTimeToString;
}

function getGMTOffset($date)
{
    return $date->getOffset() * -1000;
}

function findDSTChange($begin, $end)
{
    $begin->setTimestamp(getMinuteFloorMillis($begin));
    $end->setTimestamp(getMinuteFloorMillis($end));

    $diffMinutes = ($end->getTimestamp() - $begin->getTimestamp()) / 60;

    if ($diffMinutes == 0) {
        return $begin;
    } else if ($diffMinutes == 1) {
        return $end;
    }

    $halfway = new DateTime();
    $halfway->setTimestamp($begin->getTimestamp() + $diffMinutes * 30);

    if (getGMTOffset($halfway) != getGMTOffset($begin)) {
        return findDSTChange($begin, $halfway);
    }

    return findDSTChange($halfway, $end);
}

function getMinuteFloorMillis(DateTime $date)
{
    $time = $date->getTimestamp();

    return $time - $time % 60;
}
