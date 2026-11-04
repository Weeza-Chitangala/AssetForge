<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EquipmentAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AssetLabelController extends Controller
{
    /**
     * Stream an SVG QR code for direct scanning and label printing.
     */
    public function qrCode(EquipmentAsset $asset): Response
    {
        $payload = json_encode([
            'id'        => $asset->id,
            'asset_tag' => $asset->asset_tag,
            'serial'    => $asset->serial_number,
            'tenant_id' => $asset->tenant_id,
            'url'       => config('app.url') . "/assets/{$asset->id}",
        ]);

        $svg = QrCode::format('svg')
            ->size(250)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($payload);

        return response($svg, 200, [
            'Content-Type'        => 'image/svg+xml',
            'Content-Disposition' => 'inline; filename="qr-' . $asset->asset_tag . '.svg"',
        ]);
    }

    /**
     * Return printable tag payload for barcode/thermal label printers.
     */
    public function printPayload(EquipmentAsset $asset): JsonResponse
    {
        $asset->load(['category', 'manufacturer', 'model', 'assignedTo', 'status']);

        return response()->json([
            'success' => true,
            'message' => 'Asset label print payload retrieved.',
            'data'    => [
                'asset_tag'     => $asset->asset_tag,
                'serial_number' => $asset->serial_number,
                'name'          => $asset->name,
                'manufacturer'  => $asset->manufacturer?->name,
                'model'         => $asset->model?->name,
                'category'      => $asset->category?->name,
                'status'        => $asset->status?->name,
                'assigned_to'   => $asset->assignedTo?->name ?? 'Unassigned / In Storage',
                'qr_endpoint'   => route('assets.qr', ['asset' => $asset->id]),
            ],
        ]);
    }
}