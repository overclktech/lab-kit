<?php

namespace Overclock\LabKit;

enum Ability: string
{
    case ViewUsers = 'users.view';
    case ViewSessions = 'sessions.view';
    case RevokeSessions = 'sessions.revoke';
}
