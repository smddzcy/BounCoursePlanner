<?php

require_once "CourseFetcher.php";

class FreeRoomFinder
{

    private $schedule = [];
    private $nl;
    private $day = null;
    private $hour = null;
    const HOURS = [9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19];

    public function __construct()
    {
        $this->nl = php_sapi_name() == "cli" ? PHP_EOL : "<br/>" . PHP_EOL;
        $courseDetails = (new CourseFetcher)->getAllDetails();
        foreach ($courseDetails as $course) foreach ($course as $details) {
            if (!array_key_exists(2, $details) || empty($details[2])) continue;
            $courseRooms = array_map('trim', explode("|", $details[2]));
            $courseDays = preg_split("#(Th)|(St)|(M)|(T)|(W)|(F)#si", $details[0], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            $courseHours = [];
            if (strlen(preg_replace('![^A-Z]+!', null, $details[0])) == strlen($details[1])) {
                $courseHours = str_split($details[1]);
            } else {
                for ($i = 0; $i < strlen($details[1]); ++$i) {
                    if (!empty($courseHours)) {
                        $el = (int)array_pop($courseHours);
                        if (($newEl = 10 * $el + $details[1]{$i}) > 11) {
                            array_push($courseHours, $el);
                            array_push($courseHours, $details[1]{$i});
                        } else {
                            array_push($courseHours, $newEl);
                        }
                    } else {
                        array_push($courseHours, $details[1]{$i});
                    }
                }
            }

            $courseDays = array_map('strtoupper', $courseDays);
            for ($j = 0; $j < count($courseHours); $j++) {
                if (!array_key_exists($j, $courseDays) || empty($courseDays[$j]) || !array_key_exists($j, $courseRooms)) continue;
                $this->schedule[$courseRooms[$j]][$courseDays[$j]][] = (int)$courseHours[$j] + 8;
            }
        }
    }

    /**
     * Finds the free hour schedule of the given room
     *
     * @param String $str Full or part of the room code. (NH,NH1,NH101 etc.)
     * @param bool $echo Print free hours of the room onto the screen
     * @return array|false Free hour schedule of the room, or FALSE if the room couldn't be found
     */
    public function searchRoom(String $str, $echo = true)
    {
        $empty = [];
        foreach ($this->schedule as $room => $days) {
            if (stripos($room, $str) === false) continue;
            foreach ($days as $day => $hours) {
                $empty[$room][$day] = array_values(array_diff(self::HOURS, $hours));
            }
        }
        if (empty(array_keys($empty))) {
            return false;
            // Room couldn't be found
        }
        $this->applyFilters($empty);
        if ($echo != true) return $empty;
        foreach ($empty as $room => $days) {
            echo $room . $this->nl;
            foreach ($days as $day => $hours) {
                echo $day . ":" . implode(" ", $hours) . $this->nl;
            }
            echo $this->nl;
        }
        return $empty;
    }

    /**
     * Filters rooms by days
     *
     * @param String $day
     * @return bool
     */
    public function filterByDay(String $day): bool
    {
        $day = strtoupper(trim($day, "\xC2\xA0\n"));
        if (empty($day)) return false;
        if ($day == "MON" || $day == "MONDAY") $day = "M";
        if ($day == "TUE" || $day == "TUESDAY" || $day == "TU" || $day == "TUES") $day = "T";
        if ($day == "WED" || $day == "WEDNESDAY") $day = "W";
        if ($day == "THU" || $day == "THURSDAY" || $day == "THUR" || $day == "THURS") $day = "TH";
        if ($day == "FRI" || $day == "FRIDAY") $day = "F";
        if ($day == "SAT" || $day == "SATURDAY" || $day == "SA") $day = "ST";
        $this->day = $day;
        return true;
    }

    /**
     * Filter rooms by hours
     *
     * @param String|int $hour UTC+2 hour, not lecture hour
     * @return bool False if parameter is not valid, true otherwise
     */
    public function filterByHour($hour): bool
    {

        if ((!is_string($hour) && !is_int($hour)) || $hour < 9 || $hour > 19) return false;
        $this->hour = (int)$hour;
        return true;
    }

    /**
     * Applies the filters
     *
     * @param array $empty
     * @return array Filtered schedule
     */
    private function applyFilters(array &$empty)
    {
        foreach ($empty as &$roomSchedule) {
            if (!is_null($this->day)) {
                foreach ($roomSchedule as $day => $hours) {
                    if ($day != $this->day || (!is_null($this->hour) && !in_array($this->hour, $hours))) {
                        unset($roomSchedule[$day]);
                    }
                }
            }

        }
        foreach ($empty as $i => &$v) if (empty($empty[$i])) unset($empty[$i]);
        return $empty;
    }

    /**
     * Convert all elements of an array or a string to UTF8
     *
     * @param array|string $d Array to UTF8ize
     * @return array|string
     */
    private function utf8ize($d)
    {
        if (is_array($d)) {
            foreach ($d as $k => $v) {
                $d[$k] = $this->utf8ize($v);
            }
        } else if (is_string($d)) {
            return utf8_encode($d);
        }
        return $d;
    }

    /**
     * Removes any filter applied
     */
    public function removeFilters()
    {
        $this->day = null;
        $this->hour = null;
    }

}
