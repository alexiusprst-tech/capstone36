# Data Schema & Eloquent Domain Models
## CV. Alphate Artha Jaya — Model Data & Relasi Eloquent

Dokumen ini mendokumentasikan model Eloquent pada direktori `backend/app/Models/` beserta relasi, fillable attributes, dan type-casting.

---

## 1. Daftar Model Eloquent Aktif

| Model | File Path | Tabel Basis Data |
| :--- | :--- | :--- |
| `Product` | `app/Models/Product.php` | `products` |
| `ProductCategory` | `app/Models/ProductCategory.php` | `product_categories` |
| `ProductUnit` | `app/Models/ProductUnit.php` | `product_units` |
| `WarehouseRequest` | `app/Models/WarehouseRequest.php` | `warehouse_requests` |
| `PurchaseTransaction` | `app/Models/PurchaseTransaction.php` | `purchase_transactions` |
| `Supplier` | `app/Models/Supplier.php` | `suppliers` |
| `SpjProject` | `app/Models/SpjProject.php` | `spj_projects` |
| `SpjDocument` | `app/Models/SpjDocument.php` | `spj_documents` |
| `DocumentTemplate` | `app/Models/DocumentTemplate.php` | `document_templates` |
| `OrderDocument` | `app/Models/OrderDocument.php` | `order_documents` |
| `Region` | `app/Models/Region.php` | `regions` |
| `User` | `app/Models/User.php` | `users` |

---

## 2. Definisi Model & Casts

### 2.1 `WarehouseRequest`
```php
protected $fillable = [
    'request_code', 'request_date', 'project_id', 'project_name',
    'purpose', 'requested_by', 'status', 'total_est_cost',
    'items', 'purchase_code', 'notes',
];

protected $casts = [
    'items' => 'array',
    'total_est_cost' => 'decimal:2',
];
```

### 2.2 `PurchaseTransaction`
```php
protected $fillable = [
    'purchase_code', 'transaction_date', 'invoice_number',
    'project_id', 'project_name', 'default_supplier',
    'warehouse_request_id', 'warehouse_request_code',
    'subtotal', 'discount', 'total_price',
    'payment_method', 'status', 'items', 'created_by', 'notes',
];

protected $casts = [
    'items' => 'array',
    'subtotal' => 'decimal:2',
    'discount' => 'decimal:2',
    'total_price' => 'decimal:2',
];
```

### 2.3 `Product`
```php
protected $fillable = [
    'sku', 'name', 'category', 'unit', 'description',
    'stock', 'min_stock', 'last_buy_price', 'hpp', 'sell_price',
    'status', 'created_by', 'updated_by',
];

protected function casts(): array {
    return [
        'stock' => 'integer',
        'min_stock' => 'integer',
        'last_buy_price' => 'float',
        'hpp' => 'float',
        'sell_price' => 'float',
    ];
}
```
