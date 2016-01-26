<?php

class Language
{
    private $userLang;
    public $lang = [];
    const VALIDLANGS = ["en", "tr"];

    public function __construct()
    {
    }

    public function setUserLanguage(String $lang): bool
    {
        if (!is_string($lang) || !in_array(strtolower($lang), self::VALIDLANGS)) return false;
        $this->userLang = $lang;
        $this->lang = parse_ini_file(dirname(__FILE__) . "/languages/{$lang}.ini");
        return true;
    }

    public function getUserLanguage(): String
    {
        return $this->userLang;
    }
}