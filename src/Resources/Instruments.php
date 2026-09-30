<?php

namespace Groww\API\Resources;

use Groww\API\Constants;
use Groww\API\Exceptions\GrowwApiException;

class Instruments extends Resource
{
    /**
     * Download the instruments CSV and parse it into associative rows.
     *
     * @return array<int, array<string, string>>
     * @throws GrowwApiException
     */
    public function download(): array
    {
        $csv = $this->client->getRaw(Constants::INSTRUMENTS_CSV_URL);
        return $this->parseCsv($csv);
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function parseCsv(string $csv): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($csv));
        if ($lines === false || $lines === []) {
            return [];
        }

        $headerLine = array_shift($lines);
        $headers = str_getcsv($headerLine);
        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $values = str_getcsv($line);
            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = $values[$index] ?? '';
            }
            $rows[] = $row;
        }

        return $rows;
    }
}
