<?php

namespace App\Misc;

final class Tasks
{
    const CREATE_SUBMODULE = 10;
    const CREATE_QUESTION = 20;
    const BIND_QUESTION_DEPENDENCY = 21;
    const CREATE_PROCESSOR = 30;
    const BIND_PROCESSOR_DEPENDENCY_ON_QUESTION = 31;
    const BIND_PROCESSOR_DEPENDENCY_ON_PROCESSOR = 32;
    const CREATE_PAGE = 40;

    public function __construct()
    {
        throw new \Exception('Instantiating the Tasks class is not allowed.');
    }
}