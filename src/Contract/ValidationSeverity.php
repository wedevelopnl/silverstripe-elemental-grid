<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Contract;

enum ValidationSeverity: string
{
    case Error = 'error';
    case Warning = 'warning';
}
