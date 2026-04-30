<?php

namespace App\Contracts;

use App\Enums\PRFAppTopics;

interface HasTargetApp
{
    public function targetApp(object $notifiable): PRFAppTopics;
}
