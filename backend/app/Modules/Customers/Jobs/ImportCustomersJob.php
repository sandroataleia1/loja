<?php

declare(strict_types=1);

namespace App\Modules\Customers\Jobs;

use App\Core\Rules\ValidCpf;
use App\Core\Tenancy\Rules\ValidCnpj;
use App\Modules\Customers\Actions\CreateCustomerAction;
use App\Modules\Customers\DTOs\CreateCustomerDTO;
use App\Modules\Customers\Enums\PersonTypeEnum;
use App\Shared\Models\ImportLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\LazyCollection;
use Throwable;

final class ImportCustomersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(
        private readonly string $tenantId,
        private readonly string $filePath,
        private readonly string $importLogUuid,
    ) {}

    public function handle(CreateCustomerAction $action): void
    {
        $importLog = ImportLog::where('uuid', $this->importLogUuid)->firstOrFail();

        $successCount = 0;
        $errorDetails = [];

        try {
            $path = Storage::path($this->filePath);

            LazyCollection::make(function () use ($path) {
                $file    = fopen($path, 'r');
                $headers = fgetcsv($file);

                while (($row = fgetcsv($file)) !== false) {
                    yield array_combine($headers, $row);
                }

                fclose($file);
            })
            ->chunk(100)
            ->each(function ($chunk) use ($action, &$successCount, &$errorDetails): void {
                foreach ($chunk as $lineNumber => $row) {
                    $this->processRow($row, $lineNumber + 2, $action, $successCount, $errorDetails);
                }
            });
        } catch (Throwable $e) {
            $importLog->update(['status' => 'failed', 'finished_at' => now()]);

            return;
        }

        $importLog->markCompleted($successCount, count($errorDetails), $errorDetails);
    }

    private function processRow(
        array $row,
        int $line,
        CreateCustomerAction $action,
        int &$successCount,
        array &$errorDetails,
    ): void {
        $personType = strtoupper(trim($row['person_type'] ?? 'INDIVIDUAL'));
        $isCompany  = $personType === 'COMPANY';

        $validator = Validator::make($row, [
            'name'        => ['required', 'string', 'max:200'],
            'person_type' => ['required', 'in:INDIVIDUAL,COMPANY'],
            'document'    => ['nullable', 'string', 'max:20', $isCompany ? new ValidCnpj() : new ValidCpf()],
            'email'       => ['nullable', 'email', 'max:254'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $field => $messages) {
                $errorDetails[] = [
                    'line'    => $line,
                    'field'   => $field,
                    'message' => implode(' ', $messages),
                ];
            }

            return;
        }

        try {
            $dto = new CreateCustomerDTO(
                personType:  PersonTypeEnum::from($personType),
                name:        trim($row['name']),
                tradeName:   $row['trade_name'] ?? null,
                document:    $row['document']   ?? null,
                rg:          null,
                ie:          null,
                im:          null,
                situation:   'active',
                creditLimit: null,
                email:       $row['email']      ?? null,
                birthDate:   null,
                notes:       null,
                addresses:   $this->buildAddress($row),
                contacts:    $this->buildContacts($row),
                tags:        [],
            );

            $action->execute($dto);
            $successCount++;
        } catch (Throwable $e) {
            $errorDetails[] = [
                'line'    => $line,
                'field'   => 'general',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function buildAddress(array $row): array
    {
        if (empty($row['zip_code'])) {
            return [];
        }

        return [[
            'zipcode'    => preg_replace('/\D/', '', $row['zip_code']),
            'street'     => $row['street']       ?? '',
            'number'     => $row['number']        ?? 's/n',
            'complement' => $row['complement']    ?? null,
            'district'   => $row['neighborhood']  ?? '',
            'city'       => $row['city']           ?? '',
            'state'      => strtoupper(substr($row['state'] ?? '', 0, 2)),
            'country'    => 'BR',
            'is_default' => true,
        ]];
    }

    private function buildContacts(array $row): array
    {
        $contacts = [];

        if (! empty($row['phone'])) {
            $contacts[] = ['type' => 'PHONE', 'value' => $row['phone'], 'is_primary' => true];
        }

        if (! empty($row['whatsapp'])) {
            $contacts[] = ['type' => 'WHATSAPP', 'value' => $row['whatsapp'], 'is_primary' => empty($row['phone'])];
        }

        return $contacts;
    }
}
