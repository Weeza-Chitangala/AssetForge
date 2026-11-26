<?php

namespace App\Imports;

use App\Models\AssetModel;
use App\Models\AssetStatus;
use App\Models\Category;
use App\Models\EquipmentAsset;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\User;
use App\Scopes\TenantScope;
use App\Services\AssetCustodyService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AssetsImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected string $tenantId;
    protected int $userId;
    protected ?User $actor;

    public function __construct(string $tenantId, int $userId, ?User $actor = null)
    {
        $this->tenantId = $tenantId;
        $this->userId   = $userId;
        $this->actor    = $actor ?? User::find($userId);
    }

    public function collection(Collection $rows): void
    {
        $custodyService = app(AssetCustodyService::class);
        $canCreate = $this->actor?->can('assets.create') ?? false;
        $canUpdate = $this->actor?->can('assets.update') ?? false;
        $canTransfer = $this->actor ? $custodyService->canManageCustody($this->actor) : false;

        if (! $canCreate) {
            throw new AuthorizationException('You do not have permission (assets.create) to import equipment assets.');
        }

        $likeOp = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        DB::transaction(function () use ($rows, $canUpdate, $canTransfer, $likeOp): void {
            $preparedRows = [];
            $protectedColumns = array_merge(
                AssetCustodyService::ASSIGNMENT_FIELDS,
                AssetCustodyService::PLACEMENT_FIELDS,
            );

            // Pre-flight authorization & tenant-scoped validation across every row before any writes.
            foreach ($rows as $index => $row) {
                $rowNumber = (int) $index + 2;
                $serialNumber = trim((string) $row['serial_number']);

                $existing = EquipmentAsset::withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $this->tenantId)
                    ->where('serial_number', $serialNumber)
                    ->first();

                if ($existing !== null && ! $canUpdate) {
                    throw new AuthorizationException('You do not have permission (assets.update) to update existing assets via bulk import.');
                }

                foreach ($protectedColumns as $protectedColumn) {
                    if (isset($row[$protectedColumn]) && trim((string) $row[$protectedColumn]) !== '' && ! $canTransfer) {
                        throw new AuthorizationException(
                            "Row {$rowNumber}: You do not have permission (assets.transfer) to set or modify '{$protectedColumn}' via bulk import."
                        );
                    }
                }

                $locationName = trim((string) ($row['location'] ?? ''));
                $resolvedLocationId = $existing?->location_id;

                if ($locationName !== '') {
                    $loc = Location::withoutGlobalScope(TenantScope::class)
                        ->where('tenant_id', $this->tenantId)
                        ->where('name', $likeOp, $locationName)
                        ->first();

                    if (! $loc) {
                        throw ValidationException::withMessages([
                            'location' => ["Row {$rowNumber}: Location '{$locationName}' does not exist for the current tenant."],
                        ]);
                    }

                    $changesPlacement = $existing === null || (string) $loc->id !== (string) $existing->location_id;
                    if ($changesPlacement && ! $canTransfer) {
                        throw new AuthorizationException(
                            "Row {$rowNumber}: You do not have permission (assets.transfer) to set or change asset location via bulk import."
                        );
                    }

                    $resolvedLocationId = $loc->id;
                }

                $preparedRows[] = [
                    'row' => $row,
                    'serial_number' => $serialNumber,
                    'existing' => $existing,
                    'location_id' => $resolvedLocationId,
                ];
            }

            $defaultStatus = AssetStatus::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $this->tenantId)
                ->where('code', 'AVAILABLE')
                ->first()
                ?? AssetStatus::where('code', 'AVAILABLE')->first();

            foreach ($preparedRows as $item) {
                $row = $item['row'];
                /** @var EquipmentAsset|null $existing */
                $existing = $item['existing'];
                $serialNumber = $item['serial_number'];
                $locationId = $item['location_id'];

                $categoryName     = trim((string) $row['category']);
                $manufacturerName = trim((string) $row['manufacturer']);
                $modelName        = trim((string) $row['model']);

                // 1. Resolve or create Category
                $category = Category::firstOrCreate(
                    [
                        'tenant_id' => $this->tenantId,
                        'name'      => $categoryName,
                    ],
                    [
                        'code'   => strtoupper(substr($categoryName, 0, 3)),
                        'status' => 'active',
                    ]
                );

                // 2. Resolve or create Manufacturer
                $manufacturer = Manufacturer::firstOrCreate(
                    [
                        'tenant_id' => $this->tenantId,
                        'name'      => $manufacturerName,
                    ],
                    [
                        'code'   => strtoupper(substr($manufacturerName, 0, 3)),
                        'status' => 'active',
                    ]
                );

                // 3. Resolve or create Model
                $model = AssetModel::firstOrCreate(
                    [
                        'tenant_id'       => $this->tenantId,
                        'name'            => $modelName,
                        'manufacturer_id' => $manufacturer->id,
                    ],
                    [
                        'category_id'  => $category->id,
                        'model_number' => strtoupper(substr($modelName, 0, 8)),
                        'status'       => 'active',
                    ]
                );

                // 4. Resolve Asset Tag (preserve existing tag on update if empty)
                $assetTag = ! empty($row['asset_tag']) ? trim((string) $row['asset_tag']) : null;
                if (! $assetTag) {
                    if ($existing !== null) {
                        $assetTag = $existing->asset_tag;
                    } else {
                        $count    = EquipmentAsset::withoutGlobalScope(TenantScope::class)->where('tenant_id', $this->tenantId)->count() + 1;
                        $assetTag = sprintf('AF-%s-%06d', now()->year, $count);
                    }
                }

                // 5. Persist Asset while preserving lifecycle status on update
                EquipmentAsset::withoutGlobalScope(TenantScope::class)->updateOrCreate(
                    [
                        'tenant_id'     => $this->tenantId,
                        'serial_number' => $serialNumber,
                    ],
                    [
                        'asset_tag'            => $assetTag,
                        'name'                 => $row['name'] ?? $modelName,
                        'category_id'          => $category->id,
                        'manufacturer_id'      => $manufacturer->id,
                        'asset_model_id'       => $model->id,
                        'location_id'          => $locationId,
                        'status_id'            => $existing?->status_id ?? $defaultStatus?->id,
                        'purchase_cost'        => ! empty($row['purchase_cost']) ? (float) $row['purchase_cost'] : ($existing?->purchase_cost ?? 0.00),
                        'purchase_date'        => ! empty($row['purchase_date']) ? $row['purchase_date'] : ($existing?->purchase_date?->toDateString() ?? now()->toDateString()),
                        'warranty_expiry_date' => $row['warranty_expiry_date'] ?? $existing?->warranty_expiry_date?->toDateString(),
                        'processor'            => $row['processor'] ?? $existing?->processor,
                        'ram'                  => $row['ram'] ?? $existing?->ram,
                        'storage'              => $row['storage'] ?? $existing?->storage,
                        'operating_system'     => $row['operating_system'] ?? $existing?->operating_system,
                        'condition'            => $row['condition'] ?? $existing?->condition ?? 'good',
                        'notes'                => $row['notes'] ?? $existing?->notes ?? 'Imported via CSV/Excel onboarding',
                        'created_by'           => $existing?->created_by ?? $this->userId,
                        'updated_by'           => $existing !== null ? $this->userId : $existing?->updated_by,
                    ]
                );
            }
        });
    }

    public function rules(): array
    {
        return [
            '*.serial_number' => ['required'],
            '*.category'      => ['required'],
            '*.manufacturer'  => ['required'],
            '*.model'         => ['required'],
        ];
    }
}