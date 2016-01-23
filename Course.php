<?php
declare(strict_types = 1);

class Course
{
    private $name;
    private $hours;
    private $credit;
    private $inst;

    /**
     * @return String
     */
    public function getInst()
    {
        return $this->inst;
    }

    /**
     * @param String $inst
     */
    public function setInst($inst)
    {
        $this->inst = $inst;
    }

    /**
     * @return int
     */
    public function getCredit(): int
    {
        return $this->credit;
    }

    /**
     * @param int $credit
     */
    public function setCredit(int $credit)
    {
        $this->credit = $credit;
    }

    /**
     * @return mixed
     */
    public function getName(): String
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getHours(): array
    {
        return $this->hours;
    }

    /**
     * @param array $hours
     */
    public function setHours(array $hours)
    {
        $this->hours = $hours;
    }

    public function addHour(int $hour)
    {
        $this->hours[] = $hour;
    }

    public function __construct(String $name, array $hours = null, int $credit = null, String $inst = null)
    {
        $this->name = $name;
        $this->hours = $hours;
        $this->credit = $credit;
        $this->inst = $inst;
    }

}