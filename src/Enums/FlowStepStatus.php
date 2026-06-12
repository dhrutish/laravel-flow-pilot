<?php

namespace FlowPilot\LaravelFlowPilot\Enums;

enum FlowStepStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
