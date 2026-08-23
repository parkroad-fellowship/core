<?php

namespace App\Policies;

use App\Models\ChatBot;

class ChatBotPolicy extends BasePolicy
{
    protected string $modelClass = ChatBot::class;
}
