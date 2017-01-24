<?php
declare(strict_types = 1);

class Course
{
    private $name;
    private $fullName;
    private $hours;
    private $credit;
    private $inst;

    /**
     * @return mixed
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
    public function getCredit()
    {
        return $this->credit;
    }

    /**
     * @param int $credit
     */
    public function setCredit($credit)
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
     * @return mixed
     */
    public function getFullName()
    {
        return $this->fullName;
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

    public function __construct(String $name, array $hours = null, int $credit = null, $fullName = null, $inst = null)
    {
        $this->name = $name;
        $this->fullName = $fullName;
        $this->hours = $hours;
        $this->credit = $credit;
        $this->inst = $inst;
    }

}
