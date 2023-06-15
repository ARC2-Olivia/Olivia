<?php

namespace App\Misc;

final class Tasks
{
    const CREATE_SUBMODULE = 10;
    const CREATE_QUESTION = 20;
    const CREATE_QUESTION_DEPENDENCY = 21;
    const CREATE_PROCESSOR = 30;
    const CREATE_PAGE = 40;

    public function __construct()
    {
        throw new \Exception('Instantiating the Tasks class is not allowed.');
    }
}