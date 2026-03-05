<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Contract;

enum ValidationSeverity: string
{
    case Error = 'error';
    case Warning = 'warning';
}
