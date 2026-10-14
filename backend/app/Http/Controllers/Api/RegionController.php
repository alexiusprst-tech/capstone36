<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $kota = $request->query('kota');
        $kecamatan = $request->query('kecamatan');

        $query = Region::query();

        if ($kota) {
            $query->where('kota', $kota);
        }

        if ($kecamatan) {
            $query->where('kecamatan', $kecamatan);
        }

        $regions = $query->orderBy('kota')->orderBy('kecamatan')->orderBy('kelurahan')->get();

        return response()->json([
            'success' => true,
            'data' => $regions,
        ]);
    }

    public function tree(): JsonResponse
    {
        $regions = Region::orderBy('kota')->orderBy('kecamatan')->orderBy('kelurahan')->get();

        $tree = [];
        foreach ($regions as $row) {
            $tree[$row->kota][$row->kecamatan][] = $row->kelurahan;
        }

        return response()->json([
            'success' => true,
            'provinsi' => 'DKI Jakarta',
            'data' => $tree,
        ]);
    }
}
