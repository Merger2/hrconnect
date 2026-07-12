# HRConnect Database Encryption Policy

**Version:** 1.0  
**Date:** 2026-07-12  
**Scope:** Sensitive Employee Information

---

## Overview

HRConnect handles sensitive employee data including PII, salary information, and personal details. This document outlines the database encryption strategy.

---

## Encryption Strategy

### 1. Connection Encryption (In-Transit)

| Environment | SSL Mode | Status |
|-------------|----------|--------|
| Development | `prefer` | ✅ Active |
| Production | `require` | ⏳ Configure via environment variable |

**Configuration (.env):**
```env
DB_SSLMODE=require  # Production only
```

---

### 2. Column-Level Encryption (At-Rest)

**Implemented via `Encryptable` trait** (`app/Traits/Encryptable.php`)

**Encryptable Fields by Model:**

#### User Model
```php
protected $encryptable = [
    'phone_number',      // Emergency contact
    'address',           // Full address
];
```

#### Employee Model
```php
protected $encryptable = [
    'government_id',     // KTP/National ID
    'bank_account',      // Salary account number
    'emergency_contact',
    'emergency_phone',
    'home_address',
];
```

#### Payroll Model
```php
protected $encryptable = [
    'tax_registration_number',
    'bank_account',
    'salary_account_details',
];
```

#### Leave/Attendance Model
```php
// No sensitive fields - no encryption needed
```

---

## Usage Example

```php
// In Employee model
use App\Traits\Encryptable;

class Employee extends Model
{
    use Encryptable;
    
    protected $encryptable = [
        'government_id',
        'bank_account',
        'emergency_contact',
        'emergency_phone',
    ];
}

// When saving, attributes are automatically encrypted
$employee = Employee::create([
    'government_id' => '3171XXXXXXXXXX',  // Automatically encrypted
    'bank_account' => '1234567890',       // Automatically encrypted
]);

// When reading, attributes are automatically decrypted
echo $employee->government_id;  // Outputs: 3171XXXXXXXXXX (decrypted)
```

---

## Key Management

- **Laravel APP_KEY** used for encryption
- Rotate APP_KEY quarterly (update in .env, re-encrypt all fields)
- Back up APP_KEY securely (separate from database)

**Rotation procedure:**
1. Generate new APP_KEY: `php artisan key:generate`
2. Decrypt all encrypted fields with old key
3. Re-encrypt with new key
4. Update .env
5. Deploy

---

## Performance Considerations

- Encryption/decryption overhead is minimal (< 1ms per field)
- Avoid encrypting fields used in WHERE clauses (no index search)
- Use encrypted fields only for storage, not filtering/sorting

**Fields NOT to encrypt:**
- Email addresses (need exact match search)
- Names (need partial match search)
- IDs, timestamps, status flags

---

## Backup & Recovery

- Database backups include encrypted fields
- APP_KEY must be backed up separately
- Test restore + decryption annually

---

## Compliance

- **GDPR:** Encryption satisfies "appropriate technical measures" requirement
- **Local Data Protection:** Encrypt PII fields per local regulations
- **Auditing:** Log all access to encrypted fields

---

## Implementation Status

| Component | Status | Notes |
|-----------|--------|-------|
| Encryptable trait | ✅ Created | `app/Traits/Encryptable.php` |
| User encryption | ⏳ Pending | Add to User model |
| Employee encryption | ⏳ Pending | Add to Employee model |
| Payroll encryption | ⏳ Pending | Add to Payroll model |
| Migration guide | ✅ Documented | This file |

---

## Next Steps

1. Add `Encryptable` trait to relevant models
2. Define `$encryptable` arrays
3. Test encryption/decryption flow
4. Document field encryption policy per model
5. Schedule quarterly APP_KEY rotation
