<?php

namespace App\Services;

use App\Models\EquipmentAsset;
use App\Models\User;

/**
 * AssetCustodyService
 *
 * Centralises authorisation checks for equipment asset custody and assignment operations
 * under the dedicated 'assets.transfer' permission, ensuring ordinary 'assets.update'
 * users cannot alter custody state directly or via crafted update payloads.
 */
class AssetCustodyService
{
    public const PERMISSION_TRANSFER = 'assets.transfer';

    /**
     * Direct custodian assignment fields protected on both create and update requests.
     *
     * @var array<int, string>
     */
    public const ASSIGNMENT_FIELDS = [
        'assigned_to_user_id',
        'assigned_date',
    ];

    /**
     * Placement fields protected against custody-transfer bypass on ordinary asset updates.
     *
     * @var array<int, string>
     */
    public const PLACEMENT_FIELDS = [
        'location_id',
        'department_id',
        'company_id',
    ];

    public function canManageCustody(User $actor): bool
    {
        return $actor->can(self::PERMISSION_TRANSFER);
    }

    /**
     * Identify custody-related fields in the payload that the actor is not authorised to modify.
     *
     * @param  array<string, mixed>  $payload
     * @return array<int, string>
     */
    public function unauthorizedCustodyFields(User $actor, array $payload, ?EquipmentAsset $existingAsset = null): array
    {
        if ($this->canManageCustody($actor)) {
            return [];
        }

        $blocked = [];

        if ($existingAsset === null) {
            // Initial assignment and initial placement both require assets.transfer.
            foreach (array_merge(self::ASSIGNMENT_FIELDS, self::PLACEMENT_FIELDS) as $field) {
                if (array_key_exists($field, $payload) && $payload[$field] !== null && $payload[$field] !== '') {
                    $blocked[] = $field;
                }
            }

            return array_values(array_unique($blocked));
        }

        foreach (self::ASSIGNMENT_FIELDS as $field) {
            if (array_key_exists($field, $payload)) {
                $blocked[] = $field;
            }
        }

        foreach (self::PLACEMENT_FIELDS as $field) {
            if (
                array_key_exists($field, $payload)
                && (string) ($payload[$field] ?? '') !== (string) ($existingAsset->{$field} ?? '')
            ) {
                $blocked[] = $field;
            }
        }

        return array_values(array_unique($blocked));
    }
}
