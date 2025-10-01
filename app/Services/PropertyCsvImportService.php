<?php

namespace App\Services;

use App\Models\Property;
use App\DataTransferObjects\PropertyImportResult;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PropertyCsvImportService
{
    private const EXPECTED_HEADERS = ['property_address', 'owner_name', 'owner_address'];
    private const REQUIRED_HEADERS = ['property_address'];

    public function import(UploadedFile $file, bool $dryRun = false): PropertyImportResult
    {
        $result = new PropertyImportResult(dryRun: $dryRun);
        
        try {
            // Open and parse the CSV file
            $handle = fopen($file->getPathname(), 'r');
            if (!$handle) {
                $result->errors++;
                $result->errorMessages[] = 'Unable to read the uploaded file.';
                return $result;
            }

            $headers = fgetcsv($handle);
            if (!$headers || !$this->validateHeaders($headers, $result)) {
                fclose($handle);
                return $result;
            }

            $headerMap = $this->createHeaderMap($headers);
            $rowNumber = 1; // Start at 1 since headers are row 0

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                
                if ($this->isEmptyRow($row)) {
                    $result->skipped++;
                    continue;
                }

                $this->processRow($row, $headerMap, $rowNumber, $result, $dryRun);
            }

            fclose($handle);
            
        } catch (\Exception $e) {
            $result->errors++;
            $result->errorMessages[] = 'An error occurred while processing the file: ' . $e->getMessage();
        }

        return $result;
    }

    private function validateHeaders(array $headers, PropertyImportResult $result): bool
    {
        $headers = array_map('trim', array_map('strtolower', $headers));
        $expectedHeaders = array_map('strtolower', self::EXPECTED_HEADERS);
        $requiredHeaders = array_map('strtolower', self::REQUIRED_HEADERS);

        // Check for required headers
        foreach ($requiredHeaders as $required) {
            if (!in_array($required, $headers)) {
                $result->errors++;
                $result->errorMessages[] = "Missing required column: {$required}";
                return false;
            }
        }

        return true;
    }

    private function createHeaderMap(array $headers): array
    {
        $headers = array_map('trim', array_map('strtolower', $headers));
        $map = [];
        
        foreach ($headers as $index => $header) {
            if (in_array($header, array_map('strtolower', self::EXPECTED_HEADERS))) {
                $map[$header] = $index;
            }
        }

        return $map;
    }

    private function isEmptyRow(array $row): bool
    {
        return empty(array_filter(array_map('trim', $row)));
    }

    private function processRow(array $row, array $headerMap, int $rowNumber, PropertyImportResult &$result, bool $dryRun): void
    {
        try {
            // Extract data from row
            $data = $this->extractDataFromRow($row, $headerMap);
            
            // Validate the data
            $validator = Validator::make($data, [
                'property_address' => 'required|string|max:255',
                'owner_name' => 'nullable|string|max:255',
                'owner_address' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                $result->errors++;
                $result->errorMessages[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                return;
            }

            // Normalize property address for matching
            $normalizedAddress = trim(strtolower($data['property_address']));

            if (!$dryRun) {
                DB::transaction(function () use ($data, $normalizedAddress, &$result) {
                    // Try to find existing property by address
                    $existingProperty = Property::whereRaw('LOWER(TRIM(property_address)) = ?', [$normalizedAddress])->first();

                    if ($existingProperty) {
                        // Update existing property
                        $existingProperty->update($data);
                        $result->updated++;
                    } else {
                        // Create new property
                        Property::create($data);
                        $result->created++;
                    }
                });
            } else {
                // Dry run - just check if property exists
                $existingProperty = Property::whereRaw('LOWER(TRIM(property_address)) = ?', [$normalizedAddress])->first();
                
                if ($existingProperty) {
                    $result->updated++;
                } else {
                    $result->created++;
                }
            }

        } catch (\Exception $e) {
            $result->errors++;
            $result->errorMessages[] = "Row {$rowNumber}: " . $e->getMessage();
        }
    }

    private function extractDataFromRow(array $row, array $headerMap): array
    {
        $data = [];
        
        // Extract known fields
        foreach (self::EXPECTED_HEADERS as $field) {
            $fieldLower = strtolower($field);
            if (isset($headerMap[$fieldLower])) {
                $value = isset($row[$headerMap[$fieldLower]]) ? trim($row[$headerMap[$fieldLower]]) : null;
                $data[$field] = $value === '' ? null : $value;
            } else {
                $data[$field] = null;
            }
        }

        return $data;
    }

    public function generateTemplate(): string
    {
        $headers = self::EXPECTED_HEADERS;
        $sampleData = [
            ['123 Main St, City, State 12345', 'John Doe', '456 Owner Ave, City, State 12345'],
            ['789 Oak Ave, City, State 67890', 'Jane Smith', '321 Landlord Blvd, City, State 67890'],
        ];

        $output = fopen('php://temp', 'w+');
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write sample data
        foreach ($sampleData as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }
}