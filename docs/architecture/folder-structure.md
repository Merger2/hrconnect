# HRConnect - Folder Structure Reference

> **Dokumen ini berisi struktur folder lengkap HRConnect sebagai referensi cepat.**
> Gunakan bersama `complete-file-blueprint.md` saat coding.

---

## COMPLETE FOLDER TREE

```
hrconnect/
├── app/
│   ├── Actions/
│   │   └── Fortify/
│   │       ├── CreateNewUser.php                    ✅
│   │       └── ResetUserPassword.php                ✅
│   ├── Concerns/                                    (Traits)
│   ├── Console/
│   │   └── Commands/
│   │       ├── ResetLeaveQuotas.php                 🆕
│   │       ├── GeneratePayroll.php                  🆕
│   │       ├── SendAttendanceReminders.php          🆕
│   │       ├── CleanupExpiredSessions.php           🆕
│   │       ├── SyncDeviceVerification.php           🆕
│   │       ├── ProcessLoanInstallments.php          🆕
│   │       └── KnowledgeBaseIndex.php               🆕
│   ├── Enums/
│   │   ├── ApprovalStatus.php                       ✅
│   │   ├── AttendanceStatus.php                     ✅
│   │   ├── BloodType.php                            ✅
│   │   ├── DayType.php                              ✅
│   │   ├── EducationLevel.php                       ✅
│   │   ├── EmployeeStatus.php                       ✅
│   │   ├── FamilyRelationship.php                   ✅
│   │   ├── Gender.php                               ✅
│   │   ├── LeaveStatus.php                          ❌ DIGANTI RequestStatus
│   │   ├── LoanStatus.php                           ✅
│   │   ├── MaritalStatus.php                        ✅
│   │   ├── OvertimeStatus.php                       ❌ DIGANTI RequestStatus
│   │   ├── PayrollItemType.php                      ✅
│   │   ├── PayrollStatus.php                        ✅
│   │   ├── ReimbursementStatus.php                  ✅
│   │   ├── RequestStatus.php                        ✅ (merge Leave + Overtime)
│   │   ├── SalaryType.php                           ✅
│   │   ├── EmploymentType.php                       🆕
│   │   ├── ResignationReason.php                    🆕
│   │   ├── HandoverCategory.php                     🆕
│   │   ├── ApprovalLevel.php                        🆕
│   │   ├── DeviceType.php                           🆕
│   │   ├── NotificationType.php                     🆕
│   │   ├── ShiftScheduleType.php                    🆕
│   │   ├── AttendanceException.php                  🆕
│   │   ├── LeaveQuotaReset.php                      🆕
│   │   ├── KnowledgeBaseCategory.php                🆕
│   │   └── CompanySettingType.php                   🆕
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Controller.php                       ✅
│   │   ├── Middleware/
│   │   │   ├── DeviceDetectionMiddleware.php        🆕
│   │   │   ├── ForcePasswordChangeMiddleware.php    🆕
│   │   │   ├── CheckRoleMiddleware.php              🆕
│   │   │   ├── CheckPermissionMiddleware.php        🆕
│   │   │   └── GeofenceMiddleware.php               🆕
│   │   └── Requests/
│   │       ├── StoreEmployeeRequest.php             🆕
│   │       ├── UpdateEmployeeRequest.php            🆕
│   │       ├── StoreAttendanceRequest.php           🆕
│   │       ├── StoreLeaveRequest.php                🆕
│   │       ├── UpdateLeaveRequest.php               🆕
│   │       ├── StorePayrollRequest.php              🆕
│   │       ├── UpdatePayrollRequest.php             🆕
│   │       ├── StoreLoanRequest.php                 🆕
│   │       ├── StoreReimbursementRequest.php        🆕
│   │       ├── StoreApprovalRequest.php             🆕
│   │       ├── StoreResignationRequest.php          🆕
│   │       ├── StoreKnowledgeBaseRequest.php        🆕
│   │       ├── UpdateProfileRequest.php             🆕
│   │       ├── UpdatePasswordRequest.php            🆕
│   │       └── StoreDeviceRequest.php               🆕
│   ├── Jobs/
│   │   ├── GenerateFaceEmbedding.php                🆕
│   │   ├── ProcessPayrollGeneration.php             🆕
│   │   ├── SendNotificationJob.php                  🆕
│   │   ├── SendAttendanceReminder.php               🆕
│   │   ├── GenerateLeaveQuotas.php                  🆕
│   │   ├── ProcessKnowledgeBaseEmbedding.php        🆕
│   │   ├── CleanupOldActivityLogs.php               🆕
│   │   ├── ProcessEmployeeHandover.php              🆕
│   │   └── SyncDeviceFingerprint.php                🆕
│   ├── Livewire/
│   │   ├── Actions/
│   │   │   └── Logout.php                           ✅
│   │   ├── Components/
│   │   │   ├── Notifications.php                    🆕
│   │   │   ├── Search.php                           🆕
│   │   │   ├── ApprovalTimeline.php                 🆕
│   │   │   ├── DataTable.php                        🆕
│   │   │   ├── FileUpload.php                       🆕
│   │   │   ├── FaceCapture.php                      🆕
│   │   │   └── GpsLocator.php                       🆕
│   │   ├── Employee/
│   │   │   ├── Attendance/
│   │   │   │   ├── ClockIn.php                      🆕
│   │   │   │   ├── ClockOut.php                     🆕
│   │   │   │   ├── History.php                      🆕
│   │   │   │   └── Summary.php                      🆕
│   │   │   ├── Leave/
│   │   │   │   ├── Create.php                       🆕
│   │   │   │   ├── History.php                      🆕
│   │   │   │   └── Quota.php                        🆕
│   │   │   ├── Finance/
│   │   │   │   ├── LoanRequest.php                  🆕
│   │   │   │   ├── ReimbursementRequest.php         🆕
│   │   │   │   └── PayrollSlip.php                  🆕
│   │   │   └── Profile/
│   │   │       ├── PersonalInfo.php                 🆕
│   │   │       ├── FamilyDetails.php                🆕
│   │   │       ├── FaceRegistration.php             🆕
│   │   │       └── Devices.php                      🆕
│   │   ├── Hrd/
│   │   │   ├── Dashboard/
│   │   │   │   ├── Overview.php                     🆕
│   │   │   │   └── AttendanceToday.php              🆕
│   │   │   ├── Employees/
│   │   │   │   ├── Index.php                        🆕
│   │   │   │   ├── Create.php                       🆕
│   │   │   │   ├── Edit.php                         🆕
│   │   │   │   ├── Show.php                         🆕
│   │   │   │   └── BulkUpload.php                   🆕
│   │   │   ├── Approvals/
│   │   │   │   ├── Pending.php                      🆕
│   │   │   │   ├── All.php                          🆕
│   │   │   │   └── Escalated.php                    🆕
│   │   │   ├── Leaves/
│   │   │   │   ├── Pending.php                      🆕
│   │   │   │   ├── Calendar.php                     🆕
│   │   │   │   └── QuotaManagement.php              🆕
│   │   │   ├── Shifts/
│   │   │   │   ├── Index.php                        🆕
│   │   │   │   └── Schedule.php                     🆕
│   │   │   ├── Terminations/
│   │   │   │   ├── Pending.php                      🆕
│   │   │   │   ├── Handover.php                     🆕
│   │   │   │   └── Reassignment.php                 🆕
│   │   │   └── Reports/
│   │   │       ├── Attendance.php                   🆕
│   │   │       ├── Leave.php                        🆕
│   │   │       └── Employee.php                     🆕
│   │   ├── Finance/
│   │   │   ├── Payroll/
│   │   │   │   ├── Index.php                        🆕
│   │   │   │   ├── Generate.php                     🆕
│   │   │   │   ├── Detail.php                       🆕
│   │   │   │   ├── Publish.php                      🆕
│   │   │   │   └── BulkGenerate.php                 🆕
│   │   │   ├── Loans/
│   │   │   │   ├── Pending.php                      🆕
│   │   │   │   ├── Installments.php                 🆕
│   │   │   │   └── Report.php                       🆕
│   │   │   ├── Reimbursements/
│   │   │   │   ├── Pending.php                      🆕
│   │   │   │   └── Report.php                       🆕
│   │   │   └── Reports/
│   │   │       ├── Payroll.php                      🆕
│   │   │       └── Tax.php                          🆕
│   │   └── Admin/
│   │       ├── Settings/
│   │       │   ├── Company.php                      🆕
│   │       │   ├── Attendance.php                   🆕
│   │       │   ├── Leave.php                        🆕
│   │       │   ├── Branding.php                     🆕
│   │       │   ├── Security.php                     🆕
│   │       │   └── System.php                       🆕
│   │       ├── Users/
│   │       │   ├── Index.php                        🆕
│   │       │   ├── Create.php                       🆕
│   │       │   └── Edit.php                         🆕
│   │       ├── KnowledgeBase/
│   │       │   ├── Index.php                        🆕
│   │       │   ├── Create.php                       🆕
│   │       │   ├── Edit.php                         🆕
│   │       │   └── Chat.php                         🆕
│   │       └── ActivityLog/
│   │           └── Index.php                        🆕
│   ├── Models/
│   │   ├── ActivityLog.php                          ✅
│   │   ├── Approval.php                             ✅
│   │   ├── Asset.php                                ✅
│   │   ├── AssetHandover.php                        ✅
│   │   ├── Attendance.php                           ✅🔧
│   │   ├── Branch.php                               ✅
│   │   ├── Company.php                              ✅
│   │   ├── CompanySetting.php                       🆕
│   │   ├── Department.php                           ✅
│   │   ├── Device.php                               ✅🔧
│   │   ├── Employee.php                             ✅🔧
│   │   ├── EmployeeHandover.php                     🆕
│   │   ├── FamilyDetail.php                         ✅
│   │   ├── Holiday.php                              ✅
│   │   ├── KnowledgeBase.php                        ✅
│   │   ├── KnowledgeBaseEmbedding.php               🆕
│   │   ├── Leave.php                                ✅🔧
│   │   ├── LeaveQuota.php                           🆕
│   │   ├── LeaveType.php                            ✅
│   │   ├── Loan.php                                 ✅
│   │   ├── LoanInstallment.php                      ✅
│   │   ├── Overtime.php                             ✅
│   │   ├── Payroll.php                              ✅🔧
│   │   ├── PayrollItem.php                          ✅
│   │   ├── PerformanceReview.php                    ✅
│   │   ├── Position.php                             ✅
│   │   ├── Reimbursement.php                        ✅
│   │   ├── RolePermission.php                       🆕
│   │   ├── Shift.php                                ✅
│   │   ├── ShiftSchedule.php                        🆕
│   │   ├── User.php                                 ✅🔧
│   │   └── Company.php                              ✅
│   ├── Notifications/
│   │   ├── AttendanceReminderNotification.php       🆕
│   │   ├── LeaveRequestNotification.php             🆕
│   │   ├── LeaveApprovedNotification.php            🆕
│   │   ├── LeaveRejectedNotification.php            🆕
│   │   ├── PayrollGeneratedNotification.php         🆕
│   │   ├── ApprovalRequestNotification.php          🆕
│   │   ├── ApprovalApprovedNotification.php         🆕
│   │   ├── ApprovalRejectedNotification.php         🆕
│   │   ├── ForcePasswordChangeNotification.php      🆕
│   │   ├── ResignationSubmittedNotification.php     🆕
│   │   ├── HandoverCompletedNotification.php        🆕
│   │   └── KnowledgeBaseUpdatedNotification.php     🆕
│   ├── Observers/
│   │   ├── EmployeeObserver.php                     🆕
│   │   ├── AttendanceObserver.php                   🆕
│   │   ├── LeaveObserver.php                        🆕
│   │   ├── PayrollObserver.php                      🆕
│   │   ├── ApprovalObserver.php                     🆕
│   │   └── UserObserver.php                         🆕
│   ├── Policies/
│   │   ├── EmployeePolicy.php                       🆕
│   │   ├── AttendancePolicy.php                     🆕
│   │   ├── LeavePolicy.php                          🆕
│   │   ├── PayrollPolicy.php                        🆕
│   │   ├── ApprovalPolicy.php                       🆕
│   │   ├── LoanPolicy.php                           🆕
│   │   ├── ReimbursementPolicy.php                  🆕
│   │   └── KnowledgeBasePolicy.php                  🆕
│   ├── Providers/
│   │   ├── AppServiceProvider.php                   ✅🔧
│   │   └── FortifyServiceProvider.php               ✅
│   └── Services/
│       ├── AttendanceService.php                    🆕
│       ├── LeaveService.php                         🆕
│       ├── PayrollCalculatorService.php             🆕
│       ├── ApprovalService.php                      🆕
│       ├── EmployeeTerminationService.php           🆕
│       ├── GeofenceService.php                      🆕
│       ├── FaceRecognitionService.php               🆕
│       └── DeviceDetectionService.php               🆕
│
├── bootstrap/
│   ├── cache/
│   └── app.php
│
├── config/
│   ├── activitylog.php                              ✅
│   ├── app.php                                      ✅
│   ├── auth.php                                     ✅
│   ├── cache.php                                    ✅
│   ├── ciphersweet.php                              🆕
│   ├── database.php                                 ✅
│   ├── filesystems.php                              ✅
│   ├── fortify.php                                  ✅
│   ├── hashing.php                                  ✅
│   ├── hrconnect.php                                🆕
│   ├── logging.php                                  ✅
│   ├── mail.php                                     ✅
│   ├── permission.php                               ✅
│   ├── queue.php                                    ✅
│   ├── services.php                                 ✅
│   ├── session.php                                  ✅
│   └── laravolt/
│       └── indonesia.php                            ✅
│
├── database/
│   ├── factories/
│   │   ├── EmployeeFactory.php                      ✅
│   │   └── UserFactory.php                          ✅
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php ✅
│   │   ├── 0001_01_01_000001_create_cache_table.php ✅
│   │   ├── 0001_01_01_000002_create_jobs_table.php  ✅
│   │   ├── 2016_08_03_072729_create_provinces_table.php ✅
│   │   ├── 2016_08_03_072750_create_cities_table.php    ✅
│   │   ├── 2016_08_03_072804_create_districts_table.php ✅
│   │   ├── 2016_08_03_072819_create_villages_table.php  ✅
│   │   ├── 2025_08_14_170933_add_two_factor_columns_to_users_table.php ✅
│   │   ├── 2026_04_12_203653_create_companies_table.php   ✅
│   │   ├── 2026_04_12_211908_create_shifts_table.php      ✅
│   │   ├── 2026_04_12_212245_create_holidays_table.php    ✅
│   │   ├── 2026_04_12_212804_create_leave_types_table.php ✅
│   │   ├── 2026_04_12_224615_create_assets_table.php      ✅
│   │   ├── 2026_04_13_152045_create_branches_table.php    ✅
│   │   ├── 2026_04_13_153747_create_departments_table.php ✅
│   │   ├── 2026_04_13_154751_create_positions_table.php   ✅
│   │   ├── 2026_04_16_192201_create_employees_table.php   ✅
│   │   ├── 2026_04_17_175332_create_attendances_table.php ✅
│   │   ├── 2026_04_17_175549_create_leaves_table.php      ✅
│   │   ├── 2026_04_19_043720_create_family_details_table.php ✅
│   │   ├── 2026_04_19_043732_create_devices_table.php     ✅
│   │   ├── 2026_04_19_044010_create_asset_handovers_table.php ✅
│   │   ├── 2026_04_19_044028_create_overtimes_table.php   ✅
│   │   ├── 2026_04_19_044050_create_loans_table.php       ✅
│   │   ├── 2026_04_19_044109_create_performance_reviews_table.php ✅
│   │   ├── 2026_04_20_192640_create_approvals_table.php   ✅
│   │   ├── 2026_04_20_192703_create_payrolls_table.php    ✅
│   │   ├── 2026_04_20_192725_create_loan_installments_table.php ✅
│   │   ├── 2026_04_20_203528_create_payroll_items_table.php ✅
│   │   ├── 2026_04_20_212002_create_permission_tables.php ✅
│   │   ├── 2026_04_20_212019_create_activity_log_table.php  ✅
│   │   ├── 2026_04_21_014315_create_blind_indexes_table.php ✅
│   │   ├── 2026_04_21_044041_create_reimbursements_table.php ✅
│   │   ├── 2026_04_28_133152_create_knowledge_bases_table.php ✅
│   │   ├── 2026_05_08_000001_add_employment_type_and_resignation_to_employees_table.php 🆕
│   │   ├── 2026_05_08_000002_add_face_photo_to_employees_table.php 🆕
│   │   ├── 2026_05_08_000003_create_company_settings_table.php 🆕
│   │   ├── 2026_05_08_000004_create_shift_schedules_table.php 🆕
│   │   ├── 2026_05_08_000005_create_leave_quotas_table.php 🆕
│   │   ├── 2026_05_08_000006_add_device_detection_to_devices_table.php 🆕
│   │   ├── 2026_05_08_000007_add_clock_exception_to_attendances_table.php 🆕
│   │   ├── 2026_05_08_000008_add_gps_validation_to_attendances_table.php 🆕
│   │   ├── 2026_05_08_000009_create_employee_handovers_table.php 🆕
│   │   ├── 2026_05_08_000010_add_payroll_locked_to_payrolls_table.php 🚫 DIHAPUS
│   │   ├── 2026_05_08_000011_create_notifications_table.php 🆕
│   │   ├── 2026_05_08_000012_add_force_password_change_to_users_table.php 🆕
│   │   ├── 2026_05_08_000013_add_google_oauth_to_users_table.php 🆕
│   │   ├── 2026_05_08_000014_add_password_changed_at_to_users_table.php 🆕
│   │   └── 2026_05_08_000015_create_knowledge_base_embeddings_table.php 🆕
│   └── seeders/
│       ├── DatabaseSeeder.php                       ✅🔧
│       ├── RoleAndPermissionSeeder.php              🆕
│       ├── CompanySeeder.php                        🆕
│       ├── DepartmentSeeder.php                     🆕
│       ├── PositionSeeder.php                       🆕
│       ├── ShiftSeeder.php                          🆕
│       ├── LeaveTypeSeeder.php                      🆕
│       ├── HolidaySeeder.php                        🆕
│       ├── CompanySettingSeeder.php                 🆕
│       ├── EmployeeSeeder.php                       🆕
│       ├── KnowledgeBaseSeeder.php                  🆕
│       └── DemoDataSeeder.php                       🆕
│
├── docs/
│   ├── PRD.md                                       ✅
│   ├── erd.md                                       ✅
│   ├── use-case-diagram.md                          ✅
│   ├── class-diagram.md                             ✅
│   ├── sequence-diagrams.md                         ✅
│   ├── activity-diagrams.md                         ✅
│   ├── data-flow-diagram.md                         ✅
│   ├── deployment-diagram.md                        ✅
│   ├── complete-file-blueprint.md                   🆕
│   ├── wireframes.md                                🆕
│   ├── testing-strategy.md                          🆕
│   ├── security-config.md                           🆕
│   ├── caching-strategy.md                          🆕
│   ├── deployment-guide.md                          🆕
│   └── execution-schedule.md                        🆕
│
├── lang/
│   └── id/
│       ├── auth.php                                 🆕
│       ├── pagination.php                           🆕
│       ├── passwords.php                            🆕
│       ├── validation.php                           🆕
│       ├── attendance.php                           🆕
│       ├── leave.php                                🆕
│       ├── payroll.php                              🆕
│       ├── employee.php                             🆕
│       ├── approval.php                             🆕
│       ├── finance.php                              🆕
│       ├── settings.php                             🆕
│       ├── common.php                               🆕
│       └── errors.php                               🆕
│
├── public/
│   ├── icons/
│   │   ├── icon-192.png                             🆕
│   │   └── icon-512.png                             🆕
│   ├── manifest.json                                🆕
│   ├── offline.html                                 🆕
│   └── sw.js                                        🆕
│
├── resources/
│   ├── css/
│   │   ├── app.css                                  ✅
│   │   ├── face-capture.css                         🆕
│   │   └── gps-map.css                              🆕
│   ├── js/
│   │   ├── app.js                                   ✅
│   │   ├── face-detection.js                        🆕
│   │   ├── gps-locator.js                           🆕
│   │   ├── pwa-install.js                           🆕
│   │   ├── service-worker.js                        🆕
│   │   ├── app-debounce.js                          🆕
│   │   └── app-formatters.js                        🆕
│   └── views/
│       ├── components/
│       │   ├── app-logo.blade.php                   ✅
│       │   ├── app-logo-icon.blade.php              ✅
│       │   ├── auth-header.blade.php                ✅
│       │   ├── auth-session-status.blade.php        ✅
│       │   ├── desktop-user-menu.blade.php          ✅
│       │   ├── placeholder-pattern.blade.php        ✅
│       │   ├── status-badge.blade.php               🆕
│       │   ├── data-card.blade.php                  🆕
│       │   ├── empty-state.blade.php                🆕
│       │   ├── loading-spinner.blade.php            🆕
│       │   ├── confirmation-modal.blade.php         🆕
│       │   ├── approval-timeline.blade.php          🆕
│       │   ├── face-capture.blade.php               🆕
│       │   ├── gps-map.blade.php                    🆕
│       │   └── pdf-preview.blade.php                🆕
│       ├── flux/
│       │   ├── icon/
│       │   │   ├── layout-grid.blade.php            ✅
│       │   │   ├── chevrons-up-down.blade.php       ✅
│       │   │   ├── folder-git-2.blade.php           ✅
│       │   │   ├── book-open-text.blade.php         ✅
│       │   │   ├── clock.blade.php                  🆕
│       │   │   ├── calendar.blade.php               🆕
│       │   │   ├── users.blade.php                  🆕
│       │   │   ├── money.blade.php                  🆕
│       │   │   ├── settings.blade.php               🆕
│       │   │   ├── chart.blade.php                  🆕
│       │   │   ├── file-text.blade.php              🆕
│       │   │   ├── bell.blade.php                   🆕
│       │   │   ├── search.blade.php                 🆕
│       │   │   ├── camera.blade.php                 🆕
│       │   │   ├── map-pin.blade.php                🆕
│       │   │   ├── check-circle.blade.php           🆕
│       │   │   ├── x-circle.blade.php               🆕
│       │   │   └── alert-circle.blade.php           🆕
│       │   └── navlist/
│       │       └── group.blade.php                  ✅
│       ├── layouts/
│       │   ├── app.blade.php                        ✅
│       │   ├── auth.blade.php                       ✅
│       │   ├── app/
│       │   │   ├── header.blade.php                 ✅
│       │   │   └── sidebar.blade.php                ✅
│       │   ├── auth/
│       │   │   ├── simple.blade.php                 ✅
│       │   │   ├── split.blade.php                  ✅
│       │   │   └── card.blade.php                   ✅
│       │   ├── ess.blade.php                        🆕
│       │   ├── hrd.blade.php                        🆕
│       │   ├── finance.blade.php                    🆕
│       │   └── admin.blade.php                      🆕
│       ├── pages/
│       │   ├── auth/
│       │   │   ├── login.blade.php                  ✅
│       │   │   ├── register.blade.php               ✅
│       │   │   ├── forgot-password.blade.php        ✅
│       │   │   ├── reset-password.blade.php         ✅
│       │   │   ├── confirm-password.blade.php       ✅
│       │   │   ├── verify-email.blade.php           ✅
│       │   │   └── two-factor-challenge.blade.php   ✅
│       │   └── settings/
│       │       ├── layout.blade.php                 ✅
│       │       ├── appearance.blade.php              ✅
│       │       ├── profile.blade.php                 ✅
│       │       ├── security.blade.php                ✅
│       │       ├── delete-user-form.blade.php        ✅
│       │       ├── delete-user-modal.blade.php       ✅
│       │       ├── two-factor-setup-modal.blade.php  ✅
│       │       └── two-factor/
│       │           └── recovery-codes.blade.php      ✅
│       ├── partials/
│       │   ├── head.blade.php                       ✅
│       │   ├── settings-heading.blade.php           ✅
│       │   ├── sidebar-nav.blade.php                🆕
│       │   ├── ess-nav.blade.php                    🆕
│       │   ├── hrd-nav.blade.php                    🆕
│       │   ├── finance-nav.blade.php                🆕
│       │   ├── admin-nav.blade.php                  🆕
│       │   ├── flash-messages.blade.php             🆕
│       │   └── breadcrumbs.blade.php                🆕
│       ├── employee/
│       │   ├── dashboard.blade.php                  🆕
│       │   ├── attendance/
│       │   │   ├── clock-in.blade.php               🆕
│       │   │   ├── clock-out.blade.php              🆕
│       │   │   ├── history.blade.php                🆕
│       │   │   └── summary.blade.php                🆕
│       │   ├── leave/
│       │   │   ├── create.blade.php                 🆕
│       │   │   ├── history.blade.php                🆕
│       │   │   └── quota.blade.php                  🆕
│       │   ├── finance/
│       │   │   ├── loan-request.blade.php           🆕
│       │   │   ├── reimbursement-request.blade.php  🆕
│       │   │   └── payroll-slip.blade.php           🆕
│       │   └── profile/
│       │       ├── personal-info.blade.php          🆕
│       │       ├── family-details.blade.php         🆕
│       │       ├── face-registration.blade.php      🆕
│       │       └── devices.blade.php                🆕
│       ├── hrd/
│       │   ├── dashboard.blade.php                  🆕
│       │   ├── attendance/
│       │   │   └── today.blade.php                  🆕
│       │   ├── employees/
│       │   │   ├── index.blade.php                  🆕
│       │   │   ├── create.blade.php                 🆕
│       │   │   ├── edit.blade.php                   🆕
│       │   │   ├── show.blade.php                   🆕
│       │   │   └── bulk-upload.blade.php            🆕
│       │   ├── approvals/
│       │   │   ├── pending.blade.php                🆕
│       │   │   ├── all.blade.php                    🆕
│       │   │   └── escalated.blade.php              🆕
│       │   ├── leaves/
│       │   │   ├── pending.blade.php                🆕
│       │   │   ├── calendar.blade.php               🆕
│       │   │   └── quota-management.blade.php       🆕
│       │   ├── shifts/
│       │   │   ├── index.blade.php                  🆕
│       │   │   └── schedule.blade.php               🆕
│       │   ├── terminations/
│       │   │   ├── pending.blade.php                🆕
│       │   │   ├── handover.blade.php               🆕
│       │   │   └── reassignment.blade.php           🆕
│       │   └── reports/
│       │       ├── attendance.blade.php             🆕
│       │       ├── leave.blade.php                  🆕
│       │       └── employee.blade.php               🆕
│       ├── finance/
│       │   ├── dashboard.blade.php                  🆕
│       │   ├── payroll/
│       │   │   ├── index.blade.php                  🆕
│       │   │   ├── generate.blade.php               🆕
│       │   │   ├── detail.blade.php                 🆕
│       │   │   ├── publish.blade.php                🆕
│       │   │   └── bulk-generate.blade.php          🆕
│       │   ├── loans/
│       │   │   ├── pending.blade.php                🆕
│       │   │   ├── installments.blade.php           🆕
│       │   │   └── report.blade.php                 🆕
│       │   ├── reimbursements/
│       │   │   ├── pending.blade.php                🆕
│       │   │   └── report.blade.php                 🆕
│       │   └── reports/
│       │       ├── payroll.blade.php                🆕
│       │       └── tax.blade.php                    🆕
│       ├── admin/
│       │   ├── dashboard.blade.php                  🆕
│       │   ├── settings/
│       │   │   ├── company.blade.php                🆕
│       │   │   ├── attendance.blade.php             🆕
│       │   │   ├── leave.blade.php                  🆕
│       │   │   ├── branding.blade.php               🆕
│       │   │   ├── security.blade.php               🆕
│       │   │   └── system.blade.php                 🆕
│       │   ├── users/
│       │   │   ├── index.blade.php                  🆕
│       │   │   ├── create.blade.php                 🆕
│       │   │   └── edit.blade.php                   🆕
│       │   ├── knowledge-base/
│       │   │   ├── index.blade.php                  🆕
│       │   │   ├── create.blade.php                 🆕
│       │   │   ├── edit.blade.php                   🆕
│       │   │   └── chat.blade.php                   🆕
│       │   └── activity-log/
│       │       └── index.blade.php                  🆕
│       ├── dashboard.blade.php                      ✅
│       └── welcome.blade.php                        ✅
│
├── routes/
│   ├── api.php                                      🆕
│   ├── console.php                                  ✅🔧
│   ├── employee.php                                 🆕
│   ├── finance.php                                  🆕
│   ├── hrd.php                                      🆕
│   ├── settings.php                                 ✅
│   └── web.php                                      ✅🔧
│
├── storage/
│   ├── app/
│   │   ├── private/                                 (Face photos, payroll PDFs)
│   │   └── public/                                  (Avatars, company logo)
│   ├── framework/
│   └── logs/
│
├── tests/
│   ├── Feature/
│   │   ├── Attendance/
│   │   │   ├── ClockInTest.php                      🆕
│   │   │   ├── ClockOutTest.php                     🆕
│   │   │   ├── GeofenceValidationTest.php           🆕
│   │   │   ├── AntiFakeGPSTest.php                  🆕
│   │   │   └── AttendanceHistoryTest.php            🆕
│   │   ├── Leave/
│   │   │   ├── LeaveRequestTest.php                 🆕
│   │   │   ├── LeaveQuotaTest.php                   🆕
│   │   │   ├── LeaveApprovalTest.php                🆕
│   │   │   └── ProbationLeaveBlockTest.php          🆕
│   │   ├── Payroll/
│   │   │   ├── PayrollGenerationTest.php            🆕
│   │   │   ├── PayrollCalculationTest.php           🆕
│   │   │   ├── PayrollLockTest.php                  🆕
│   │   │   ├── BPJSAndTaxTest.php                   🆕
│   │   │   └── InternExemptTest.php                 🆕
│   │   ├── Loan/
│   │   │   ├── LoanRequestTest.php                  🆕
│   │   │   └── LoanInstallmentTest.php              🆕
│   │   ├── Reimbursement/
│   │   │   └── ReimbursementRequestTest.php         🆕
│   │   ├── Approval/
│   │   │   ├── MultiLevelApprovalTest.php           🆕
│   │   │   └── ApprovalReassignmentTest.php         🆕
│   │   ├── Employee/
│   │   │   ├── EmployeeCRUDTest.php                 🆕
│   │   │   ├── EmployeeNumberGenerationTest.php     🆕
│   │   │   ├── ResignationTest.php                  🆕
│   │   │   ├── HandoverTest.php                     🆕
│   │   │   └── ProbationTest.php                    🆕
│   │   ├── FaceRecognition/
│   │   │   ├── FaceEmbeddingTest.php                🆕
│   │   │   └── FaceSimilarityTest.php               🆕
│   │   ├── KnowledgeBase/
│   │   │   ├── KnowledgeBaseCRUDTest.php            🆕
│   │   │   └── RagQueryTest.php                     🆕
│   │   ├── RBAC/
│   │   │   ├── RolePermissionTest.php               🆕
│   │   │   └── MiddlewareTest.php                   🆕
│   │   ├── Device/
│   │   │   ├── DeviceDetectionTest.php              🆕
│   │   │   └── DeviceVerificationTest.php           🆕
│   │   ├── Notification/
│   │   │   └── NotificationTest.php                 🆕
│   │   ├── Security/
│   │   │   ├── ForcePasswordChangeTest.php          🆕
│   │   │   ├── CipherSweetEncryptionTest.php        🆕
│   │   │   └── GoogleOAuthTest.php                  🆕
│   │   ├── Settings/
│   │   │   ├── ProfileUpdateTest.php                ✅
│   │   │   └── SecurityTest.php                     ✅
│   │   ├── Auth/
│   │   │   ├── AuthenticationTest.php               ✅
│   │   │   ├── RegistrationTest.php                 ✅
│   │   │   ├── EmailVerificationTest.php            ✅
│   │   │   ├── PasswordConfirmationTest.php         ✅
│   │   │   ├── PasswordResetTest.php                ✅
│   │   │   └── TwoFactorChallengeTest.php           ✅
│   │   ├── DashboardTest.php                        ✅
│   │   └── ExampleTest.php                          ✅
│   ├── Unit/
│   │   ├── Enums/
│   │   │   ├── EmploymentTypeTest.php               🆕
│   │   │   └── ApprovalStatusTest.php               🆕
│   │   ├── Services/
│   │   │   ├── AttendanceServiceTest.php            🆕
│   │   │   ├── PayrollCalculatorServiceTest.php     🆕
│   │   │   ├── GeofenceServiceTest.php              🆕
│   │   │   ├── LeaveServiceTest.php                 🆕
│   │   │   └── ApprovalServiceTest.php              🆕
│   │   ├── Models/
│   │   │   ├── EmployeeTest.php                     🆕
│   │   │   ├── AttendanceTest.php                   🆕
│   │   │   ├── PayrollTest.php                      🆕
│   │   │   └── LeaveTest.php                        🆕
│   │   ├── Observers/
│   │   │   ├── EmployeeObserverTest.php             🆕
│   │   │   ├── AttendanceObserverTest.php           🆕
│   │   │   └── LeaveObserverTest.php                🆕
│   │   └── ExampleTest.php                          ✅
│   ├── Pest.php                                     ✅
│   └── TestCase.php                                 ✅
│
├── .env
├── .env.example
├── .gitignore
├── composer.json
├── package.json
├── phpunit.xml
├── vite.config.js
├── artisan
└── README.md
```

---

## KEY FOLDERS SUMMARY

| Folder | Purpose | Target Files | Realisasi (2026-06-06) |
|--------|---------|--------------|------------------------|
| `app/Enums/` | PHP 8.1 Enums | 33 files | ✅ 34 files (termasuk Permission) |
| `app/Models/` | Eloquent Models | 31 files | ✅ 31 files |
| `app/Services/` | Business Logic | 13 files | ✅ 13 files (Approval, Attendance, FaceRecognition, Geofence, Leave, PayrollCalculator, PayrollExport, PayslipPdf, Reimbursement, EmployeeTermination, KnowledgeBase, Embedding, GeminiClient) |
| `app/Observers/` | Model Event Handlers | 6 files | ✅ 6 files (Attendance, BpjsConfig, Employee, Holiday, TaxConfig, Leave) |
| `app/Jobs/` | Queue Jobs | 2 files | ✅ 2 files (GenerateEmployeePayrollJob, ProcessKnowledgeBaseEmbedding) |
| `app/Notifications/` | Mail/Database Notifications | 7 files | ✅ 7 files (folder ada) |
| `app/Livewire/` | Livewire Components | 69 files | ⚠️ 1 file (Logout.php) — Sprint 16-30 |
| `app/Http/Requests/Api/` | Form Validation | 14 files | ✅ 14 files |
| `app/Http/Middleware/` | HTTP Middleware | 3 files | ✅ 3 files (CheckPasswordExpired, DeviceDetection, GeofenceValidation) |
| `app/Policies/` | Authorization Policies | 8 files | ✅ 8 files |
| `app/Console/Commands/` | Artisan Commands | 8 files | ✅ 8 files (semua terdaftar di routes/console.php) |
| `database/migrations/` | Database Schema | 48 files | ⚠️ 43 files (migrasi phk_variant baru) — 5 pending |
| `database/seeders/` | Database Seeders | 12 files | ✅ 12 files (11 terdaftar di DatabaseSeeder) |
| `resources/views/` | Blade Templates | 110+ files | ⚠️ 38 files — Sprint 16-30 |
| `resources/js/` | JavaScript Files | 2 files (app.js, pwa-install.js) | ⚠️ 2 files — Sprint 16-30 |
| `lang/id/` | Bahasa Indonesia Translations | 6 files + id.json | ⚠️ partial |
| `tests/Feature/` | Feature Tests | 49 files | ⚠️ partial |
| `tests/Unit/` | Unit Tests | 14 files | ⚠️ partial |
| `docs/` | Project Documentation | 25+ files | ✅ 27 files |

---

*Dokumen ini adalah referensi lengkap struktur folder.*
*Terakhir diupdate: 2026-05-31*
