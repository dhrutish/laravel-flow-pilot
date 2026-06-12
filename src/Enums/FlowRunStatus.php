<?php

namespace FlowPilot\LaravelFlowPilot\Enums;

enum FlowRunStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
