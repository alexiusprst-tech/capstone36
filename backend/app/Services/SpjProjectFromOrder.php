<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Models\SpjProject;

class SpjProjectFromOrder
{
    public function sync(SalesOrder $order): void
    {
        $details = $order->details ?? [];
        $id = $details['projectId'] ?? '';
        if ($id === '') {
            return;
        }
        $project = SpjProject::firstOrNew(['project_id' => $id]);
        $metadata = $project->metadata ?? [];
        // Several orders may share a project, provided they belong to the same institution.
        $anotherOrder = ! empty($metadata['source_order_number']) && $metadata['source_order_number'] !== $order->order_number;
        if ($project->exists && (empty($metadata['source_order_number']) || $anotherOrder)) {
            $normalize = fn ($name) => preg_replace('/[^a-z0-9]/', '', str_replace('negeri', 'negri', mb_strtolower($name ?? '')));
            $existingNames = array_map($normalize, [$project->customer_name, $metadata['customer_profile']['name'] ?? '', $metadata['customer_profile']['orgFullName'] ?? '']);
            $incomingNames = array_map($normalize, [$details['clientName'], $details['customerProfile']['name'] ?? '', $details['customerProfile']['orgFullName'] ?? '']);
            if (! array_intersect(array_filter($existingNames), array_filter($incomingNames))) {
                abort(409, 'ID Proyek '.$id.' sudah digunakan untuk instansi lain.');
            }
        }
        if ($anotherOrder) {
            return;
        }
        $metadata['source_order_number'] = $order->order_number;
        if (array_key_exists('customerProfile', $details) && (! $project->exists || $project->customer_name !== $details['clientName'] || ($metadata['customer_profile_source'] ?? 'order') !== 'project')) {
            $metadata['customer_profile'] = $details['customerProfile'] ?? [];
            $metadata['customer_profile_source'] = 'order';
        }
        $values = ['project_name' => $details['projectName'], 'customer_name' => $details['clientName'], 'metadata' => $metadata,
            'items' => array_map(fn ($item) => ['code' => $item['sku'], 'item_name' => $item['name'], 'quantity' => $item['qty'], 'unit' => $item['unit'], 'price' => $item['price'], 'specs' => $item['specs'] ?? ''], $order->items ?? [])];
        $mapping = ['company' => 'company_name', 'marketingName' => 'marketing_name', 'namaKegiatan' => 'activity_name', 'rincianKegiatan' => 'activity_detail', 'kodeRekening' => 'account_code', 'tahunAnggaran' => 'fiscal_year', 'sumberDana' => 'funding_source', 'paguAnggaran' => 'budget_ceiling', 'tglMulai' => 'start_date', 'estimasiSelesai' => 'end_date', 'penanggungJawabNama' => 'pic_name', 'penanggungJawabJabatan' => 'pic_title', 'penanggungJawabNip' => 'pic_nip', 'kotaTandaTangan' => 'city_signature'];
        foreach ($mapping as $source => $target) {
            if (isset($details[$source])) {
                $values[$target] = $details[$source];
            }
        }
        if (! empty($details['customerProfile']['code'])) {
            $values['customer_code'] = $details['customerProfile']['code'];
        }
        if (! $project->exists) {
            $values += ['project_value' => $order->grand_total, 'status' => 'DRAFT', 'warehouse_status' => 'PENDING', 'finance_status' => 'MENUNGGU_PEMBAYARAN', 'city_signature' => $details['customerProfile']['letterheadCity'] ?? ''];
        }
        $project->fill($values)->save();
        foreach (SpjDocumentContent::TITLES as $key => $title) {
            $project->documents()->firstOrCreate(['doc_key' => $key], ['doc_order' => array_search($key, array_keys(SpjDocumentContent::TITLES), true) + 1, 'doc_name' => $title, 'status' => 'DRAFT', 'is_external' => in_array($key, ['spph', 'permintaan_penawaran', 'bukti_foto'], true)]);
        }
    }
}
