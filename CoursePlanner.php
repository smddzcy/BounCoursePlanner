<?php

declare (strict_types = 1);
require_once 'Course.php';

class CoursePlanner
{
    private $courseList;
    private $allPlans;
    private $plan;
    private $unwantedHours;
    private $conflict = -1;
    private $year;
    private $semester;

    const DEFAULT_DAYS = ['M', 'T', 'W', 'Th', 'F', 'Sa'];

    public function __construct(int $year = null, int $semester = null)
    {
        $this->courseList = [];
        $this->allPlans = [];
        $this->plan = [];
        $this->unwantedHours = [];
        if (!is_null($year) && !is_null($semester)) {
            $this->year = (int) str_pad((String) $year, 4, '20', STR_PAD_LEFT);
            $this->semester = $semester;
        } else {
            $m = (int) date('m');
            $s = 1;
            if ($m >= 1 && $m <= 6) {
                $s = 2;
            } elseif ($m >= 6 && $m <= 8) {
                $s = 3;
            }

            $this->year = date('Y');
            if ($s !== 1) {
                --$this->year;
            }

            $this->semester = $s;
        }
    }

  /**
   * @param string $name Course name
   * @param int $code Course code
   * @param array $hours [Section=>[DayHour]] formatted course hours. ex.: ["01"=>["M11","Th10","W16"], "022=>...].
   * If not given, it'll be fetched.
   *
   * @throws Exception
   */
  public function addCourse(String $name, int $code = null, array $hours = null)
  {
      $credit = null;
      if (is_null($hours)) {
          if (!is_null($code)) {
              $name .= $code;
          }

          if (!file_exists('CourseFetcher.php')) {
              throw new Exception('This option requires course fetcher to work.');
          }

          require_once 'CourseFetcher.php';
          $courseFetcher = new CourseFetcher((int) $this->year, (int) $this->semester);
          if (strpos($name, '.') !== false) {
              // contains explicit section detail
              list($name, $section) = explode('.', $name);
              $courseDetails = $courseFetcher->getDetails($name);
              if (!array_key_exists($section, $courseDetails)) {
                  throw new Exception($name.'.'.$section.' cannot be added; course does not have that section for the semester.');
              }

              $courseDetails = [$section => $courseDetails[$section]];
          } else {
              $courseDetails = $courseFetcher->getDetails($name);
          }
          if ($courseDetails === false) {
              throw new Exception($name." cannot be added; if you've spelled it correctly, the course is not available for the semester.");
          }

          $hours = [];
          foreach ($courseDetails as $section => $details) {
              // Set the course credit
              if (empty($credit)) {
                $credit = $details[4];
              }
              // 0 days 1 hours
              $courseDays = preg_split('/([A-Z][a-z]*)/', $details[0], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
              if (empty($courseDays)) {
                  throw new Exception($name.' has no schedule details or corrupt details.');
              }
              $courseHours = [];
              if (strlen(preg_replace('![^A-Z]+!', null, $details[0])) == strlen($details[1])) {
                  $courseHours = str_split($details[1]);
              } else {
                  for ($i = 0; $i < strlen($details[1]); ++$i) {
                      if (!empty($courseHours)) {
                          $el = (int) array_pop($courseHours);
                          if (($newEl = 10 * $el + $details[1]{ $i}) > 11) {
                              array_push($courseHours, $el);
                              array_push($courseHours, $details[1]{ $i});
                          } else {
                              array_push($courseHours, $newEl);
                          }
                      } else {
                          array_push($courseHours, $details[1]{ $i});
                      }
                  }
              }
              for ($j = 0; $j < count($courseHours); ++$j) {
                  $courseDays[$j] .= (int) $courseHours[$j] + 8;
              }

              $hours[] = [$section => $courseDays];
          }
      }
      $this->courseList[] = new Course($name, $hours, (int) $credit);
  }

  /**
   * @param string $hour DayHour formatted hour. ex.: M11, F12, Th13
   * If only an integer is given, all days will be automatically added.
   */
  public function addUnwantedHour(String $hour)
  {
      if (!preg_match('#[^0-9]#i', $hour)) {
          foreach (self::DEFAULT_DAYS as $day) {
              $this->unwantedHours[] = $day.$hour;
          }
      } else {
          $this->unwantedHours[] = $hour;
      }
  }

    public function getConflict(): int
    {
        return $this->conflict;
    }

  /**
   * @param int $j
   * @param bool $duplicateHours Include the sections with same hours or not
   */
  private function generatePlans(int $j, bool $duplicateHours = false)
  {
      $course = $this->courseList[$j];
      $hours = $course->getHours();
      if ($duplicateHours === false) {
          $newHours = [];
          foreach ($hours as $hourSet) {
              $section = array_keys($hourSet)[0];
              $newHours[$section] = $hourSet[$section];
          }
          $hours = [];

          foreach (array_unique($newHours, SORT_REGULAR) as $section => $newHourSet) {
              $hours[] = [$section => $newHourSet];
          }
      }
      for ($i = 0; $i < count($hours); ++$i) {
          array_push($this->plan, new Course($course->getName(), (array) $hours[$i], $course->getCredit()));
          if ($j !== count($this->courseList) - 1) {
              $this->generatePlans($j + 1, $duplicateHours);
          } elseif (!empty($this->plan)) {
              $this->allPlans[] = $this->plan;
              array_pop($this->plan);
          }
      }
      array_pop($this->plan);
  }

    public function getAllPlans(bool $duplicateHours = false): array
    {
        if (empty($this->allPlans)) {
            $this->generatePlans(0, $duplicateHours);
        }

        return $this->allPlans;
    }

    public function getBestPlans(bool $duplicateHours = false): array
    {
        $bestPlans = [];
        $plans = $this->getAllPlans($duplicateHours);

        for ($i = 0; $i < count($plans); ++$i) {
            $hours = [];
            $points = 0;
            foreach ($plans[$i] as $course) {
                foreach ($course->getHours() as $courseHours) {
                    foreach ($courseHours as $hour) {
                        if (array_search($hour, $this->unwantedHours) !== false) {
                            ++$points;
                        }

                        if (array_search($hour, $hours) !== false) {
                            ++$points;
                        } else {
                            $hours[] = $hour;
                        }
                    }
                }
            }
            if (count($plans[$i]) == count($this->courseList)) {
                $bestPlans[$points][] = $plans[$i];
            }
        }

        ksort($bestPlans);

        $this->conflict = array_keys($bestPlans)[0];
        $best = array_shift($bestPlans);
        while (empty($best)) {
            $best = array_shift($bestPlans);
        }

        return $best;
    }
}
