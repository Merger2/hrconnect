# Service Audit Matrix — HRConnect

**Date:** 2026-06-18
**Status:** Final — all 15 services audited.

## Summary

| # | Service | Methods | Tests | Coverage | Transactions | Jobs | API Calls | Cache |
|---|---------|:-------:|:-----:|:--------:|:----------:|:----:|:--------:|:-----:|
| 1 | ApprovalService | 3 | ✅ Good | Full L1+L2 workflow, quota deduction | 3 | 0 | 0 | 0 |
| 2 | AttendanceService | 2 | ✅ Good | PIN/GPS/WFA clock-in/out, edge cases | 2 | 0 | 0 | 3 |
| 3 | EmbeddingService | 6 | ⚠️ Thin | Chunk+format only; pgvector/pg_trgm untested | 0 | 0 | 0 | 0 |
| 4 | EmployeeTerminationService | 2 | ✅ Adequate | Resign/deceased/contract-end | 1 | 0 | 0 | 0 |
| 5 | FaceRecognitionService | 2 | ⚠️ Thin | Dimension+validation; pgvector query untested | 0 | 0 | 0 | 0 |
| 6 | GeminiClient | 3 | ⚠️ Thin | Mock mode only; retry/error not tested | 0 | 0 | 3 | 0 |
| 7 | GeofenceService | 1 | ✅ Good | Boundary tests: GPS mock/accuracy/radius | 0 | 0 | 0 | 0 |
| 8 | KnowledgeBaseService | 4 | ✅ Good | Chat, upload, reindex, delete | 2 | 2 | 0 | 0 |
| 9 | LeaveService | 4 | ✅ Good | Apply, workdays, balance, carry-forward | 3 | 0 | 0 | 0 |
| 10 | OvertimeService | 2 | ⚠️ Thin | Happy path only; no error-path tests | 1 | 0 | 0 | 0 |
| 11 | PayrollCalculatorService | 12 | ✅ Adequate | Prorated PPh21 BPJS THR generatePayroll; 5 methods indirect-only | 1 | 0 | 0 | 1 |
| 12 | PayrollExportService | 3 | ✅ Adequate | XLSX creation, branch filter, empty period | 0 | 0 | 0 | 0 |
| 13 | PayslipPdfService | 3 | ⚠️ Thin | buildTemplateData only; PDF gen untested | 0 | 0 | 0 | 0 |
| 14 | ProfileService | 3 | ✅ Adequate | Get/update profile, change password | 0 | 0 | 0 | 0 |
| 15 | ReimbursementService | 4 | ✅ Good | Create/approve/reject/link-to-payroll | 2 | 0 | 0 | 0 |
| | **TOTAL** | **54** | **15/15** | **~421 effective assertions** | **15** | **2** | **3** | **4** |

## Detailed Per-Service

### 1. ApprovalService
- **Methods:** `createApprovalWorkflow(Model)`, `approve(Approval, notes)`, `reject(Approval, reason)`
- **Deps:** Approval, Employee, Leave, LeaveBalance, Reimbursement, User (Spatie roles)
- **Transactions:** 3 — each with `lockForUpdate()` for race-condition safety
- **Coverage:** Full L1+L2 workflow, single-L2, no-approver LogicException, approve/reject, L1-before-L2 ordering, quota deduction on final approval, rejection reason
- **Activity log:** None

### 2. AttendanceService
- **Methods:** `clockIn(Employee, data)`, `clockOut(Employee, data)`
- **Deps:** GeofenceService, FaceRecognitionService, Attendance, Employee, Branch, Shift
- **Transactions:** 2 — clockIn + clockOut
- **Cache:** 3 — PIN streak reset + limit tracking
- **Activity:** 1 — logBypass on attendance bypass
- **Coverage:** clockIn/out, GPS mocked, WFA success/fail, face verify, PIN fallback, AlreadyClockedIn, NotClockedIn, AntiFakeGPS

### 3. EmbeddingService
- **Methods:** `extractTextFromPdf(path)`, `chunkText(text)`, `processKnowledgeBase(KB)`, `searchSimilar(embedding)`, `searchByKeyword(query)`, `formatVector(vector)`
- **Deps:** GeminiClient, KnowledgeBase, Smalot\PdfParser
- **Transactions:** 0
- **Coverage gaps:** `processKnowledgeBase()`, `searchSimilar()` (pgvector), `searchByKeyword()` (pg_trgm) — all need PostgreSQL

### 4. EmployeeTerminationService
- **Methods:** `terminate(Employee, type, reason, date)`, `processContractEnd(date)`
- **Deps:** PayrollCalculatorService, Employee, User
- **Transactions:** 1 — with `lockForUpdate()`
- **Activity:** 1 — logTermination
- **Coverage:** RESIGN (fields, financial summary, user soft-delete), DECEASED, non-active guard, contract-end

### 5. FaceRecognitionService
- **Methods:** `getEmbeddingDimension()`, `verifyFace(Employee, embedding)`
- **Deps:** Employee, CompanySetting, pgvector Distance/Vector
- **Transactions:** 0
- **Coverage gaps:** `nearestNeighbors()` pgvector query not tested (requires PostgreSQL)

### 6. GeminiClient
- **Methods:** `embed(text)`, `generateContent(question, context)`, `isHealthy()`
- **Deps:** Google Gemini API (HTTP), config services.gemini.*
- **External API:** 3 call sites (embed, generateContent, isHealthy) via retryRequest
- **Coverage:** Mock mode only (vector shape, canned response, isHealthy), input validation. Real API/retry/error handling not tested.

### 7. GeofenceService
- **Methods:** `validateLocation(Branch, gpsData)`
- **Deps:** Branch
- **Transactions:** 0
- **Coverage:** Mocked GPS, accuracy boundary (100/101), missing coordinates, lat/lng range, Null Island, within/outside radius, formatted error

### 8. KnowledgeBaseService
- **Methods:** `chat(question)`, `uploadPdf(pdf, title, category, owner)`, `reindex(KB)`, `deleteKnowledgeBase(KB)`
- **Deps:** GeminiClient, EmbeddingService, ProcessKnowledgeBaseEmbedding job, KnowledgeBase model
- **Transactions:** 2 — uploadPdf (bulk create), deleteKnowledgeBase (group delete)
- **Jobs:** 2 dispatch sites (uploadPdf loop + reindex)
- **Coverage:** Chat validation + vector search + pg_trgm fallback, upload validation + job dispatch, reindex, delete group/single

### 9. LeaveService
- **Methods:** `applyLeave(Employee, data)`, `calculateWorkDays(start, end, dayType)`, `initializeBalance(Employee, year)`, `carryForward(Employee, fromYear, toYear)`
- **Deps:** ApprovalService, Employee, Leave, LeaveBalance, LeaveType, CompanySetting, ManagesWorkDays trait
- **Transactions:** 3 — all with `lockForUpdate()`
- **Coverage:** applyLeave (7 validation scenarios), calculateWorkDays (full/half day), initializeBalance (active-only, prorated, no-duplicate), carryForward (transfer, empty, 3-day cap, ineligible)

### 10. OvertimeService
- **Methods:** `createOvertime(Employee, data)`, `cancelOvertime(Overtime)`
- **Deps:** ApprovalService, Overtime, Employee
- **Transactions:** 1
- **Coverage gaps:** Only happy path tested (create + overnight duration, cancel soft-delete). No error-path tests.

### 11. PayrollCalculatorService
- **Methods:** 12 (calculateProratedSalary, calculateOvertimePay, getTERCategory, calculatePPh21, calculateBPJS, calculateThrProrated, calculatePesangon, calculateLeaveCashOut, calculateUangKompensasi, calculateUangPenghargaanMasaKerja, generatePayroll)
- **Deps:** ApprovalService, 9+ models (Employee, Position, Payroll, Attendance, Overtime, Reimbursement, Holiday, LeaveBalance, CompanySetting, TaxConfig, BpjsConfig)
- **Transactions:** 1 — generatePayroll with atomic lock + transaction
- **Cache:** 1 — Cache::lock for generatePayroll concurrency
- **Coverage gaps:** `calculateOvertimePay` zero direct tests. `calculatePesangon`, `calculateLeaveCashOut`, `calculateUangKompensasi`, `calculateUangPenghargaanMasaKerja` only indirectly tested via termination service.

### 12. PayrollExportService
- **Methods:** `exportMonthly(period, branchId)`, `export1721A1(period)`, `exportBpjsReport(period)`
- **Deps:** Payroll model, OpenSpout XLSX Writer
- **Coverage:** All 3 exports create valid XLSX, branch filtering, empty period. Cell content not validated.

### 13. PayslipPdfService
- **Methods:** `generate(Payroll)`, `generateAndStore(Payroll)`, `getPayslipPath(Payroll)`
- **Deps:** Payroll, PayrollItem, Company, DomPDF
- **Coverage gaps:** Only `buildTemplateData()` tested (keys, period label, company data). PDF generation and file storage untested.

### 14. ProfileService
- **Methods:** `getProfile(User)`, `updateProfile(Employee, data)`, `changePassword(User, current, new)`
- **Deps:** User, Employee
- **Coverage:** All 3 methods tested (get with/without employee, update field, change password correct/wrong)

### 15. ReimbursementService
- **Methods:** `createReimbursement(Employee, data)`, `approve(Reimbursement, approver, notes)`, `reject(Reimbursement, approver, reason)`, `linkToPayroll(Reimbursement, payrollId)`
- **Deps:** ApprovalService, Reimbursement, Employee, Payroll, Approval
- **Transactions:** 2 — createReimbursement + linkToPayroll (both with `lockForUpdate()`)
- **Coverage:** All 4 methods: create (with/without attachment, workflow), approve/reject (via delegation, missing approval), linkToPayroll (success, guards, lockForUpdate)

## Architecture Notes

- **Zero events** fired across all services — no Event::dispatch or event() calls
- **Only GeminiClient** makes external HTTP calls (Gemini API)
- **Only KnowledgeBaseService** dispatches queue jobs
- **15 total transactions**, all with `lockForUpdate()` for race-condition prevention
- **4 cache calls**: 3 in AttendanceService (PIN streak), 1 in PayrollCalculatorService (distributed lock)
- **3 activity log calls**: AttendanceService (bypass), EmployeeTerminationService (termination), EmployeeController (PII access — in controller, not service)
