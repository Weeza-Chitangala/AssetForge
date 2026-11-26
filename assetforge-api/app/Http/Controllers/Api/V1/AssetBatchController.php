<?php

namespace App\Http\Controllers\Api\V1;

use App\Exports\AssetsExport;
use App\Http\Controllers\Controller;
use App\Imports\AssetsImport;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetBatchController extends Controller
{
    /**
     * Bulk import assets from Excel (.xlsx) or CSV.
     */
    public function import(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        abort_if(! $tenantId, 403, 'An active tenant context is required for this operation.');

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ]);

        try {
            $import = new AssetsImport(
                $tenantId,
                $request->user()->id,
                $request->user()
            );

            Excel::import($import, $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Assets bulk imported successfully.',
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Spreadsheet validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Spreadsheet validation failed.',
                'errors'  => $e->failures(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Import error: ' . $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Export all tenant assets to Excel or CSV.
     */
    #[OA\Get(
        path: '/assets/export',
        summary: 'Export tenant assets',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Asset export file')]
    )]
    public function export(Request $request): BinaryFileResponse
    {
        $format = $request->query('format', 'xlsx');
        $fileName = 'assetforge-assets-' . now()->format('Y-m-d') . '.' . $format;

        return Excel::download(
            new AssetsExport($request->user()->tenant_id),
            $fileName
        );
    }
}