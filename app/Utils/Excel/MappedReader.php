<?php

namespace App\Utils\Excel;

use Illuminate\Support\Collection;
use App\Utils\Excel\Concerns\ToArray;
use App\Utils\Excel\Concerns\ToCollection;
use App\Utils\Excel\Concerns\ToModel;
use App\Utils\Excel\Concerns\WithCalculatedFormulas;
use App\Utils\Excel\Concerns\WithFormatData;
use App\Utils\Excel\Concerns\WithMappedCells;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MappedReader
{
    /**
     * @param  WithMappedCells  $import
     * @param  Worksheet  $worksheet
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function map(WithMappedCells $import, Worksheet $worksheet)
    {
        $mapped = [];
        foreach ($import->mapping() as $name => $coordinate) {
            $cell = Cell::make($worksheet, $coordinate);

            $mapped[$name] = $cell->getValue(
                null,
                $import instanceof WithCalculatedFormulas,
                $import instanceof WithFormatData
            );
        }

        if ($import instanceof ToModel) {
            $model = $import->model($mapped);

            if ($model) {
                $model->saveOrFail();
            }
        }

        if ($import instanceof ToCollection) {
            $import->collection(new Collection($mapped));
        }

        if ($import instanceof ToArray) {
            $import->array($mapped);
        }
    }
}
