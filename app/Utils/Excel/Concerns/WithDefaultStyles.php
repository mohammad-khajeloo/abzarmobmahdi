<?php

namespace App\Utils\Excel\Concerns;

use PhpOffice\PhpSpreadsheet\Style\Style;

interface WithDefaultStyles
{
    /**
     * @return array|void
     */
    public function defaultStyles(Style $defaultStyle);
}
