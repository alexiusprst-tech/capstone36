<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Models\SpjProject;
use Illuminate\Validation\ValidationException;

class SpjLetterhead
{
    public const FIELDS = ['code', 'name', 'orgFullName', 'orgShortName', 'type', 'province', 'city', 'address', 'letterheadAddress', 'letterheadCity', 'orgPhone', 'phone', 'fax', 'website', 'email', 'postalCode', 'governmentName', 'educationOffice', 'logoDataUrl'];

    public static function rules(string $prefix): array
    {
        $rules = [$prefix => ['sometimes', 'nullable', 'array:'.implode(',', self::FIELDS)]];
        foreach (self::FIELDS as $field) {
            $rules[$prefix.'.'.$field] = ['nullable', 'string', 'max:'.($field === 'logoDataUrl' ? 1400000 : ($field === 'address' || $field === 'letterheadAddress' ? 2000 : 255))];
        }
        $rules[$prefix.'.logoDataUrl'][] = function ($attribute, $value, $fail) {
            if ($value && ! self::validLogo($value)) {
                $fail('Gunakan logo JPG/PNG maksimal 1 MB dan resolusi maksimal 4 megapiksel.');
            }
        };

        return $rules;
    }

    public static function validLogo(string $value): bool
    {
        if (strlen($value) > 1400000 || ! preg_match('#^data:image/(png|jpeg);base64,([A-Za-z0-9+/=]+)$#', $value, $match)) {
            return false;
        }
        $bytes = base64_decode($match[2], true);
        $info = $bytes ? @getimagesizefromstring($bytes) : false;

        return $info && strlen($bytes) <= 1048576 && $info[0] * $info[1] <= 4000000 && in_array($info['mime'], ['image/png', 'image/jpeg'], true);
    }

    /**
     * Memperluas singkatan jenjang/nama sekolah untuk kop surat resmi
     * (contoh: SMA Negeri 2 Bogor -> SEKOLAH MENENGAH ATAS NEGERI 2 BOGOR)
     */
    public static function expandSchoolName(?string $name): string
    {
        if (empty($name)) {
            return '';
        }

        return trim(preg_replace('/\s+/u', ' ', $name));
    }

    public function profile(SpjProject $project): array
    {
        if (array_key_exists('customer_profile', $project->metadata ?? [])) {
            $raw = $project->metadata['customer_profile'] ?? [];
            if (empty($raw)) {
                return [];
            }
            $prof = collect($raw)->only(self::FIELDS)->map(fn ($value) => is_string($value) ? trim($value) : '')->all();
            if (empty(array_filter($prof))) {
                return [];
            }
            if (empty($prof['orgFullName']) && ! empty($project->customer_name)) {
                $prof['orgFullName'] = $project->customer_name;
            }

            return $prof;
        }
        $order = SalesOrder::where('details->projectId', $project->project_id)->orderByDesc('id')->first();
        $profile = $order?->details['customerProfile'] ?? [];
        $normalize = fn ($name) => mb_strtolower(preg_replace('/\s+/u', ' ', trim($name ?? '')));
        if (! in_array($normalize($project->customer_name), array_map($normalize, [$order?->details['clientName'] ?? '', $profile['name'] ?? '', $profile['orgFullName'] ?? '']), true)) {
            $profile = [];
        }

        if (empty($profile)) {
            return [];
        }

        $res = collect($profile)->only(self::FIELDS)->map(fn ($value) => is_string($value) ? trim($value) : '')->all();
        if (empty($res['orgFullName']) && ! empty($project->customer_name)) {
            $res['orgFullName'] = $project->customer_name;
        }

        return $res;
    }

    public function build(SpjProject $project): array
    {
        $profile = $this->profile($project);
        if (! empty($profile['orgFullName'])) {
            $name = mb_strtoupper(self::expandSchoolName($profile['orgFullName']));
        } else {
            $name = self::expandSchoolName($project->customer_name ?? '');
        }
        $school = ($profile['type'] ?? '') === 'Instansi Pendidikan' || preg_match('/\b(SMA|SMK|SMP|SD|SEKOLAH|MADRASAH|SMAN|SMKN|SMPN|SDN)\b/i', $name);
        $province = $profile['province'] ?? '';
        $government = $profile['governmentName'] ?? '';
        $address = ($profile['letterheadAddress'] ?? '') ?: (($profile['address'] ?? '') ?: ($project->metadata['customer_address'] ?? ''));

        if ($government === '' && $school) {
            if ($province === '') {
                $rawContext = mb_strtoupper($name.' '.($profile['city'] ?? '').' '.($profile['letterheadCity'] ?? '').' '.($project->city_signature ?? '').' '.$address);
                if (str_contains($rawContext, 'BOGOR') || str_contains($rawContext, 'JAWA BARAT')) {
                    $province = 'JAWA BARAT';
                } elseif (str_contains($rawContext, 'JAKARTA')) {
                    $province = 'DKI JAKARTA';
                }
            }
            if ($province !== '') {
                $government = 'PEMERINTAH PROVINSI '.(in_array(mb_strtoupper($province), ['DKI JAKARTA', 'DK JAKARTA'], true) ? 'DAERAH KHUSUS IBUKOTA JAKARTA' : mb_strtoupper($province));
            }
        }

        $department = ($profile['educationOffice'] ?? '') ?: ($school ? 'DINAS PENDIDIKAN' : '');
        $logo = $profile['logoDataUrl'] ?? '';
        if ($logo !== '' && ! self::validLogo($logo)) {
            throw ValidationException::withMessages(['metadata.customer_profile.logoDataUrl' => 'Logo kop surat tidak valid. Unggah ulang logo JPG/PNG.']);
        }
        $phone = ($profile['orgPhone'] ?? '') ?: ($profile['phone'] ?? '');
        $contacts = array_filter([
            $phone !== '' ? 'Telepon: '.$phone : '',
            ! empty($profile['fax']) ? 'Faksimile: '.$profile['fax'] : '',
        ]);
        $online = array_filter([! empty($profile['website']) ? 'Laman: '.$profile['website'] : '', ! empty($profile['email']) ? 'Surel: '.$profile['email'] : '']);

        return ['name' => $name, 'government' => $government, 'department' => $department,
            'address' => $address, 'contacts' => implode('  |  ', $contacts), 'online' => implode('  |  ', $online),
            'postal_code' => $profile['postalCode'] ?? '', 'province' => $province, 'logo' => $logo];
    }
}
