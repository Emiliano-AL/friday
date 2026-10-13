<?php

namespace App\Enums;

enum ProjectIcon: string
{
    case Folder = 'folder';
    case Rocket = 'rocket';
    case Bug = 'bug_report';
    case Design = 'design_services';
    case Code = 'code';
    case Database = 'database';
    case Campaign = 'campaign';
    case Terminal = 'terminal';
}
