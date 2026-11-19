<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Services\SpjLetterhead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $query = Customer::query()->orderByDesc('id');
        if (($search = trim($data['search'] ?? '')) !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%'));
        }

        return response()->json(['data' => $query->get()->map(fn ($customer) => $customer->present())]);
    }

    public function show(string $identifier): JsonResponse
    {
        return response()->json(['data' => $this->find($identifier)->present()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);
        $customer = Customer::create([...$this->attributes($data), 'code' => 'CS-'.Str::ulid(), 'created_by' => $request->user()->id]);

        return response()->json(['data' => $customer->fresh()->present()], 201);
    }

    public function update(Request $request, string $identifier): JsonResponse
    {
        $data = $this->validateData($request, true);
        $customer = DB::transaction(function () use ($identifier, $data) {
            $customer = $this->find($identifier, true);
            abort_unless($customer->version === $data['version'], 409, 'Data customer telah berubah. Muat ulang sebelum menyimpan.');
            $customer->fill($this->attributes($data));
            $customer->version++;
            $customer->save();

            return $customer;
        }, 3);

        return response()->json(['data' => $customer->present()]);
    }

    public function destroy(string $identifier): JsonResponse
    {
        DB::transaction(function () use ($identifier) {
            $customer = $this->find($identifier, true);
            $legacyReference = SalesOrder::whereNull('customer_id')->where(function ($q) use ($customer) {
                $q->where('details->clientName', $customer->name)->orWhere('details->customerProfile->code', $customer->code);
            })->exists();
            abort_if($customer->orders()->exists() || $legacyReference, 409, 'Customer masih dipakai pesanan dan tidak dapat dihapus. Referensi transaksi harus dipertahankan.');
            $customer->delete();
        }, 3);

        return response()->json(['success' => true]);
    }

    private function find(string $identifier, bool $lock = false): Customer
    {
        $query = Customer::where(fn ($q) => $q->where('id', $identifier)->orWhere('code', $identifier));

        return ($lock ? $query->lockForUpdate() : $query)->firstOrFail();
    }

    private function validateData(Request $request, bool $updating = false): array
    {
        $rules = ['name' => ['required', 'string', 'max:255'], 'contactPerson' => ['required', 'string', 'max:255'],
            'phone' => ['bail', 'required', 'string', 'max:50', 'regex:/^\+?[0-9][0-9() .-]{5,48}[0-9]$/', function ($attribute, $value, $fail) {
                $digits = strlen(preg_replace('/\D/', '', $value));
                if ($digits < 7 || $digits > 15) {
                    $fail('Nomor telepon harus berisi 7 sampai 15 digit.');
                }
            }],
            'address' => ['required', 'string', 'max:2000'], 'city' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'], 'status' => ['nullable', Rule::in(['Aktif', 'Nonaktif'])]];
        foreach (['orgFullName', 'orgShortName', 'type', 'province', 'district', 'subdistrict', 'postalCode', 'npwp', 'letterheadCity', 'orgPhone', 'fax', 'governmentName', 'educationOffice'] as $field) {
            $rules[$field] = ['nullable', 'string', 'max:255'];
        }
        $rules['letterheadAddress'] = ['nullable', 'string', 'max:2000'];
        $rules['website'] = ['nullable', 'url:http,https', 'max:255'];
        $rules['logoDataUrl'] = SpjLetterhead::rules('profile')['profile.logoDataUrl'];
        if ($updating) {
            $rules['version'] = ['required', 'integer', 'min:1'];
        }

        return $request->validate($rules);
    }

    private function attributes(array $data): array
    {
        $core = ['name', 'phone', 'email', 'address', 'city'];
        $attributes = collect($data)->only($core)->all();
        $attributes['contact_person'] = $data['contactPerson'];
        $attributes['profile'] = collect($data)->except([...$core, 'contactPerson', 'version'])->all();
        $attributes['profile']['orgFullName'] = $data['orgFullName'] ?? $data['name'];
        $attributes['profile']['status'] = $data['status'] ?? 'Aktif';

        return $attributes;
    }
}
