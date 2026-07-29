export function buildPageScreenshotCatalog({
  adminEmail,
  appUrl,
  loginToken,
  userEmail,
}) {
  const urlFor = (routePath) => `${appUrl}${routePath.startsWith('/') ? routePath : `/${routePath}`}`;
  const loginUrl = (email, to) => `${appUrl}/__e2e-login?token=${encodeURIComponent(loginToken)}&email=${encodeURIComponent(email)}&to=${encodeURIComponent(to)}`;

  return [
    { slug: 'login', label: 'Login', url: urlFor('/login'), expectedPath: '/login', clearCookies: true },

    { slug: 'user-home', label: 'User Home', url: loginUrl(userEmail, '/home'), expectedPath: '/home', clearCookies: true },
    { slug: 'user-notifications', label: 'User Notifications', url: urlFor('/notifications') },
    { slug: 'user-apply-leave', label: 'Apply Leave', url: urlFor('/apply-leave') },
    { slug: 'user-attendance-history', label: 'Attendance History', url: urlFor('/attendance-history') },
    {
      slug: 'user-scan',
      label: 'Scan Attendance',
      url: urlFor('/scan?apk_screenshot=1'),
      expectedPath: '/scan',
      readyExpression: "document.body.innerText.includes('Scan Absensi') || document.body.innerText.includes('Scan Attendance') || document.querySelector('.scan-attendance-page')",
    },
    { slug: 'user-attendance-corrections', label: 'Attendance Corrections', url: urlFor('/attendance-corrections') },
    { slug: 'user-reimbursement', label: 'Reimbursement', url: urlFor('/reimbursement') },
    { slug: 'user-schedule', label: 'My Schedule', url: urlFor('/my-schedule') },
    { slug: 'user-shift-swaps', label: 'Shift Swap Requests', url: urlFor('/shift-swap-requests') },
    { slug: 'user-hr-tasks', label: 'HR Tasks', url: urlFor('/hr-tasks') },
    {
      slug: 'user-document-requests',
      label: 'User Document Requests',
      url: urlFor('/document-requests'),
      expectedPath: '/document-requests',
      readyExpression: "document.body.innerText.includes('Pengajuan Dokumen') || document.body.innerText.includes('Document Requests')",
    },
    { slug: 'user-approvals', label: 'Team Approvals', url: urlFor('/approvals') },
    { slug: 'user-approvals-history', label: 'Team Approvals History', url: urlFor('/approvals/history') },
    { slug: 'user-overtime', label: 'Overtime', url: urlFor('/overtime') },
    { slug: 'user-payslips', label: 'My Payslips', url: urlFor('/payroll'), expectedPath: '/payroll' },
    { slug: 'user-my-kasbon', label: 'My Cash Advances', url: urlFor('/my-kasbon') },
    { slug: 'user-team-kasbon', label: 'Team Cash Advances', url: urlFor('/team-kasbon') },
    {
      slug: 'user-home-before-face-id',
      label: 'User Home Before Face ID',
      url: urlFor('/home'),
      expectedPath: '/home',
      prepareState: 'face-unregistered',
      readyExpression: "document.getElementById('face-enrollment-heading') || document.body.innerText.includes('Register Face ID Now') || document.body.innerText.includes('Face ID Registration Required')",
      afterNavigate: "document.getElementById('face-enrollment-heading')?.closest('section')?.scrollIntoView({ block: 'center' })",
    },
    {
      slug: 'user-face-enrollment',
      label: 'Face Enrollment',
      url: urlFor('/face-enrollment?apk_screenshot=1'),
      expectedPath: '/face-enrollment',
      readyExpression: "document.body.innerText.includes('Face ID') || document.body.innerText.includes('Pengaturan Face ID')",
      afterNavigate: `(async () => {
        const updateButton = document.querySelector('[data-apk-face-update]');
        updateButton?.click();

        const deadline = Date.now() + 15000;
        while (Date.now() < deadline) {
          if (document.querySelector('[data-face-enrollment-root] video')) {
            return true;
          }
          await new Promise((resolve) => setTimeout(resolve, 250));
        }

        return false;
      })()`,
    },
    { slug: 'user-my-assets', label: 'My Assets', url: urlFor('/my-assets') },
    { slug: 'user-my-performance', label: 'My Performance', url: urlFor('/my-performance') },
    { slug: 'user-profile', label: 'User Profile', url: urlFor('/user/profile') },

    { slug: 'admin-dashboard', label: 'Admin Dashboard', url: loginUrl(adminEmail, '/admin/dashboard'), expectedPath: '/admin/dashboard', clearCookies: true },
    { slug: 'admin-inbox', label: 'Manager Inbox', url: urlFor('/admin/inbox') },
    { slug: 'admin-notifications', label: 'Admin Notifications', url: urlFor('/admin/notifications') },
    { slug: 'admin-attendances', label: 'Daily Attendance', url: urlFor('/admin/attendances') },
    { slug: 'admin-attendance-corrections', label: 'Attendance Corrections', url: urlFor('/admin/attendance-corrections') },
    { slug: 'admin-leaves', label: 'Leave Approvals', url: urlFor('/admin/leaves') },
    { slug: 'admin-shift-swaps', label: 'Shift Swap Approvals', url: urlFor('/admin/shift-swaps') },
    { slug: 'admin-overtime', label: 'Overtime', url: urlFor('/admin/overtime') },
    { slug: 'admin-schedules', label: 'Schedules Roster', url: urlFor('/admin/schedules') },
    { slug: 'admin-analytics', label: 'Analytics', url: urlFor('/admin/analytics') },
    { slug: 'admin-holidays', label: 'Holidays', url: urlFor('/admin/holidays') },
    { slug: 'admin-announcements', label: 'Announcements', url: urlFor('/admin/announcements') },
    { slug: 'admin-payrolls', label: 'Payroll', url: urlFor('/admin/payrolls'), expectedPath: '/admin/payrolls' },
    { slug: 'admin-reimbursements', label: 'Reimbursements', url: urlFor('/admin/reimbursements') },
    { slug: 'admin-manage-kasbon', label: 'Manage Kasbon', url: urlFor('/admin/manage-kasbon'), expectedPath: '/admin/manage-kasbon' },
    { slug: 'admin-payroll-settings', label: 'Payroll Settings', url: urlFor('/admin/payrolls/settings'), expectedPath: '/admin/payrolls/settings' },
    { slug: 'admin-employees', label: 'Employees', url: urlFor('/admin/employees') },
    { slug: 'admin-hr-checklists', label: 'HR Checklists', url: urlFor('/admin/hr-checklists') },
    { slug: 'admin-document-requests', label: 'Admin Document Requests', url: urlFor('/admin/document-requests') },
    {
      slug: 'admin-document-templates',
      label: 'Document Templates',
      url: urlFor('/admin/document-templates'),
      expectedPath: '/admin/document-templates',
      readyExpression: "document.body.innerText.includes('Template Dokumen') || document.body.innerText.includes('Document Templates')",
    },
    { slug: 'admin-appraisals', label: 'Performance Appraisals', url: urlFor('/admin/appraisals'), expectedPath: '/admin/appraisals' },
    { slug: 'admin-assets', label: 'Company Assets', url: urlFor('/admin/assets'), expectedPath: '/admin/assets' },
    { slug: 'admin-barcodes', label: 'Barcode Locations', url: urlFor('/admin/barcodes') },
    { slug: 'admin-divisions', label: 'Divisions', url: urlFor('/admin/masterdata/division') },
    { slug: 'admin-job-titles', label: 'Job Titles', url: urlFor('/admin/masterdata/job-title') },
    { slug: 'admin-education', label: 'Education Levels', url: urlFor('/admin/masterdata/education') },
    { slug: 'admin-shifts', label: 'Shifts', url: urlFor('/admin/masterdata/shift') },
    { slug: 'admin-leave-types', label: 'Leave Types', url: urlFor('/admin/masterdata/leave-types') },
    { slug: 'admin-administrators', label: 'Administrators', url: urlFor('/admin/masterdata/admin') },
    { slug: 'admin-settings', label: 'App Settings', url: urlFor('/admin/settings') },
    { slug: 'admin-kpi-settings', label: 'KPI Settings', url: urlFor('/admin/settings/kpi'), expectedPath: '/admin/settings/kpi' },
    { slug: 'admin-maintenance', label: 'System Maintenance', url: urlFor('/admin/system-maintenance') },
    { slug: 'admin-operational-health', label: 'Operational Health', url: urlFor('/admin/operational-health') },
    { slug: 'admin-reports', label: 'Reports', url: urlFor('/admin/reports') },
    { slug: 'admin-activity-logs', label: 'Activity Logs', url: urlFor('/admin/activity-logs') },
    { slug: 'admin-import-export-users', label: 'Import Export Users', url: urlFor('/admin/import-export/users'), expectedPath: '/admin/import-export/users' },
    { slug: 'admin-import-export-attendance', label: 'Import Export Attendance', url: urlFor('/admin/import-export/attendances'), expectedPath: '/admin/import-export/attendances' },
    { slug: 'admin-roles', label: 'Roles and Permissions', url: urlFor('/admin/roles-permissions') },
    { slug: 'admin-profile', label: 'Admin Profile', url: urlFor('/admin/profile') },
  ];
}
