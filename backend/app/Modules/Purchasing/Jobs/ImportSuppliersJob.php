<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Jobs;

use App\Core\Tenancy\Rules\ValidCnpj;
use App\Modules\Purchasing\Actions\CreateSupplierAction;
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

final class ImportSuppliersJob implements ShouldQueue
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

    public function handle(CreateSupplierAction $action): void
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
        CreateSupplierAction $action,
        int &$successCount,
        array &$errorDetails,
    ): void {
        $personType = strtoupper(trim($row['person_type'] ?? 'COMPANY'));
        $isCompany  = $personType === 'COMPANY';

        $validator = Validator::make($row, [
            'name'        => ['required', 'string', 'max:200'],
            'person_type' => ['required', 'in:INDIVIDUAL,COMPANY'],
            'document'    => ['nullable', 'string', 'max:20', ...($isCompany ? [new ValidCnpj()] : [])],
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
            $action->execute([
                'tenant_id'   => $this->tenantId,
                'person_type' => $personType,
                'name'        => trim($row['name']),
                'trade_name'  => $row['trade_name'] ?? null,
                'document'    => $row['document']   ?? null,
                'email'       => $row['email']      ?? null,
                'phone'       => $row['phone']      ?? null,
                'is_active'   => true,
            ]);

            $successCount++;
        } catch (Throwable $e) {
            $errorDetails[] = [
                'line'    => $line,
                'field'   => 'general',
                'message' => $e->getMessage(),
            ];
        }
    }
}
