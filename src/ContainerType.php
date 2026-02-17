<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid;

enum ContainerType: string
{
    case Section = 'section';
    case Row = 'row';
    case Column = 'column';
}
