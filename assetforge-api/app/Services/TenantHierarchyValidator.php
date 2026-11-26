<?php

namespace App\Services;

use App\Models\AssetModel;
use App\Models\Company;
use App\Models\Department;
use App\Models\Location;
use App\Models\Team;
use App\Scopes\TenantScope;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

/**
 * TenantHierarchyValidator
 *
 * Validates parent-child consistency across organizational hierarchy tables
 * (Organization -> Company -> Department -> Team) and equipment catalog links
 * (Manufacturer/Category -> AssetModel, Company -> Location).
 */
class TenantHierarchyValidator
{
    /**
     * Validate that supplied organizational foreign keys form a consistent parent-child chain.
     *
     * @param  Validator    $validator       Active Laravel validator instance
     * @param  string|null  $organizationId  Effective organization UUID
     * @param  string|null  $companyId       Effective company UUID
     * @param  string|null  $departmentId    Effective department UUID
     * @param  string|null  $teamId          Effective team UUID
     */
    public function validateOrganizationHierarchy(
        Validator $validator,
        ?string $organizationId,
        ?string $companyId,
        ?string $departmentId,
        ?string $teamId = null,
        ?array $ruleErrors = null,
    ): void {
        // Use rule-level errors (e.g. malformed UUID or TenantRecordExists failure)
        // so one hierarchy mismatch does not suppress sibling hierarchy checks.
        $ruleErrors ??= $validator->errors()->keys();

        if (
            $companyId && $organizationId
            && ! in_array('company_id', $ruleErrors, true)
            && ! in_array('organization_id', $ruleErrors, true)
            && Str::isUuid($companyId) && Str::isUuid($organizationId)
        ) {
            $belongs = Company::where('id', $companyId)
                ->where('organization_id', $organizationId)
                ->exists();

            if (! $belongs) {
                $validator->errors()->add('company_id', 'The selected company does not belong to the specified organization.');
            }
        }

        if (
            $departmentId && $companyId
            && ! in_array('department_id', $ruleErrors, true)
            && ! in_array('company_id', $ruleErrors, true)
            && Str::isUuid($departmentId) && Str::isUuid($companyId)
        ) {
            $belongs = Department::where('id', $departmentId)
                ->where('company_id', $companyId)
                ->exists();

            if (! $belongs) {
                $validator->errors()->add('department_id', 'The selected department does not belong to the specified company.');
            }
        }

        if (
            $teamId && $departmentId
            && ! in_array('team_id', $ruleErrors, true)
            && ! in_array('department_id', $ruleErrors, true)
            && Str::isUuid($teamId) && Str::isUuid($departmentId)
        ) {
            $belongs = Team::where('id', $teamId)
                ->where('department_id', $departmentId)
                ->exists();

            if (! $belongs) {
                $validator->errors()->add('team_id', 'The selected team does not belong to the specified department.');
            }
        }

        // Validate transitive ancestry when intermediate parent links are omitted.
        if (
            $departmentId && $organizationId && ! $companyId
            && ! in_array('department_id', $ruleErrors, true)
            && ! in_array('organization_id', $ruleErrors, true)
            && Str::isUuid($departmentId) && Str::isUuid($organizationId)
        ) {
            $belongs = Department::where('id', $departmentId)
                ->whereHas('company', fn ($q) => $q->where('organization_id', $organizationId))
                ->exists();

            if (! $belongs) {
                $validator->errors()->add('department_id', 'The selected department does not belong to the specified organization.');
            }
        }

        if (
            $teamId && $companyId && ! $departmentId
            && ! in_array('team_id', $ruleErrors, true)
            && ! in_array('company_id', $ruleErrors, true)
            && Str::isUuid($teamId) && Str::isUuid($companyId)
        ) {
            $belongs = Team::where('id', $teamId)
                ->whereHas('department', fn ($q) => $q->where('company_id', $companyId))
                ->exists();

            if (! $belongs) {
                $validator->errors()->add('team_id', 'The selected team does not belong to the specified company.');
            }
        }

        if (
            $teamId && $organizationId && ! $companyId && ! $departmentId
            && ! in_array('team_id', $ruleErrors, true)
            && ! in_array('organization_id', $ruleErrors, true)
            && Str::isUuid($teamId) && Str::isUuid($organizationId)
        ) {
            $belongs = Team::where('id', $teamId)
                ->whereHas('department.company', fn ($q) => $q->where('organization_id', $organizationId))
                ->exists();

            if (! $belongs) {
                $validator->errors()->add('team_id', 'The selected team does not belong to the specified organization.');
            }
        }
    }

    /**
     * Validate that an AssetModel matches any supplied Manufacturer and Category.
     *
     * @param  Validator         $validator       Active Laravel validator instance
     * @param  string|null       $assetModelId    Effective asset model UUID
     * @param  string|null       $manufacturerId  Effective manufacturer UUID
     * @param  string|null       $categoryId      Effective category UUID
     * @param  array<int,string>|null $ruleErrors Snapshot of rule-level error keys
     */
    public function validateAssetCatalogHierarchy(
        Validator $validator,
        ?string $assetModelId,
        ?string $manufacturerId,
        ?string $categoryId,
        ?array $ruleErrors = null,
    ): void {
        $ruleErrors ??= $validator->errors()->keys();

        if (! $assetModelId || in_array('asset_model_id', $ruleErrors, true) || ! Str::isUuid($assetModelId)) {
            return;
        }

        $model = AssetModel::withoutGlobalScope(TenantScope::class)->find($assetModelId);
        if (! $model) {
            return;
        }

        if ($manufacturerId && ! in_array('manufacturer_id', $ruleErrors, true) && Str::isUuid($manufacturerId) && $model->manufacturer_id !== $manufacturerId) {
            $validator->errors()->add('asset_model_id', 'The selected asset model does not belong to the specified manufacturer.');
        }

        if ($categoryId && ! in_array('category_id', $ruleErrors, true) && Str::isUuid($categoryId) && $model->category_id !== null && $model->category_id !== $categoryId) {
            $validator->errors()->add('asset_model_id', 'The selected asset model does not belong to the specified category.');
        }
    }

    /**
     * Validate that a company-scoped Location belongs to the specified Company.
     *
     * @param  Validator              $validator   Active Laravel validator instance
     * @param  string|null            $locationId  Effective location UUID
     * @param  string|null            $companyId   Effective company UUID
     * @param  array<int,string>|null $ruleErrors  Snapshot of rule-level error keys
     */
    public function validateLocationCompany(
        Validator $validator,
        ?string $locationId,
        ?string $companyId,
        ?array $ruleErrors = null,
    ): void {
        $ruleErrors ??= $validator->errors()->keys();

        if (
            ! $locationId || ! $companyId
            || in_array('location_id', $ruleErrors, true)
            || in_array('company_id', $ruleErrors, true)
            || ! Str::isUuid($locationId) || ! Str::isUuid($companyId)
        ) {
            return;
        }

        $location = Location::withoutGlobalScope(TenantScope::class)->find($locationId);
        if ($location && $location->company_id !== null && $location->company_id !== $companyId) {
            $validator->errors()->add('location_id', 'The selected location does not belong to the specified company.');
        }
    }
}
