<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers with search, sorting, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search', $request->query('q'));
        $sortBy = $request->query('sort_by', 'id');
        $sortDir = strtolower($request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = $request->query('per_page', 10);
        $status = $request->query('status');

        $query = Supplier::query();

        if ($status !== null && $status !== '') {
            if ($status === 'active' || $status === '1' || $status === 'Aktif') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive' || $status === '0' || $status === 'Nonaktif') {
                $query->where('is_active', false);
            }
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('npwp', 'like', "%{$search}%");
            });
        }

        $validSortCols = ['id', 'code', 'name', 'address', 'contact_person', 'phone', 'email', 'npwp', 'created_at'];
        if (! in_array($sortBy, $validSortCols)) {
            $sortBy = 'id';
        }

        $query->orderBy($sortBy, $sortDir);

        if ($perPage === 'all' || (int) $perPage <= 0) {
            $suppliers = $query->get();
            return response()->json([
                'success' => true,
                'data' => $suppliers,
                'meta' => [
                    'total' => $suppliers->count(),
                    'per_page' => $suppliers->count(),
                    'current_page' => 1,
                    'last_page' => 1,
                ],
            ]);
        }

        $paginated = $query->paginate((int) $perPage);

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    /**
     * Store a newly created supplier.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50|unique:suppliers,code',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:500',
            'npwp' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($validated['code'])) {
            $latestSupplier = Supplier::orderBy('id', 'desc')->first();
            $nextNum = $latestSupplier ? ($latestSupplier->id + 1) : 1;
            $validated['code'] = 'SUP'.str_pad((string) $nextNum, 5, '0', STR_PAD_LEFT);
        }

        if (! isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }

        $supplier = Supplier::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Supplier berhasil ditambahkan.',
            'data' => $supplier,
        ], 201);
    }

    /**
     * Display the specified supplier.
     */
    public function show($id): JsonResponse
    {
        $supplier = Supplier::where('id', $id)
            ->orWhere('code', $id)
            ->first();

        if (! $supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $supplier,
        ]);
    }

    /**
     * Update the specified supplier.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $supplier = Supplier::where('id', $id)
            ->orWhere('code', $id)
            ->first();

        if (! $supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50', Rule::unique('suppliers', 'code')->ignore($supplier->id)],
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:500',
            'npwp' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $supplier->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data supplier berhasil diperbarui.',
            'data' => $supplier,
        ]);
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy($id): JsonResponse
    {
        $supplier = Supplier::where('id', $id)
            ->orWhere('code', $id)
            ->first();

        if (! $supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier tidak ditemukan.',
            ], 404);
        }

        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supplier berhasil dihapus.',
        ]);
    }
}

