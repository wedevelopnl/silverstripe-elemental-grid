<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Contract;

enum ContainerType: string
{
    case Section = 'section';
    case Row = 'row';
    case Column = 'column';
}
