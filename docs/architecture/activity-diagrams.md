# Activity Diagrams - HRConnect HRIS

## Deskripsi
Dokumen ini menyajikan diagram Activity UML untuk sistem HRConnect HRIS yang menggambarkan alur kerja (workflow) dari proses-proses bisnis utama. Diagram ini menggunakan swimlanes untuk memisahkan aktivitas berdasarkan aktor dan sistem, serta menunjukkan decision points, fork/join nodes, dan flow directions sesuai dengan business rules dalam PRD.

---

## 1. Activity Diagram - Clock-In/Out (WFO + WFA)

```mermaid
flowchart TD
    Start([Start Clock-In]) --> CheckShift{Memiliki Shift?}
    CheckShift -->|Tidak| CreateDefault[Buat Shift Default: Office Hour]
    CheckShift -->|Ya| CheckMode{Pilih Mode}
    CreateDefault --> CheckMode
    
    CheckMode -->|WFO| WFO[Mode Work From Office]
    CheckMode -->|WFA| WFA[Mode Work From Anywhere]
    
    %% WFO Flow
    WFO --> GetGPS[Ambil Koordinat GPS]
    GetGPS --> ValidateGPS{Validasi GPS<br/>Haversine Formula}
    ValidateGPS -->|Jarak > Radius| RejectGPS[Clock-In Ditolak<br/>Jarak Melebihi Radius]
    ValidateGPS -->|Jarak <= Radius| FaceRecogWFO[Face Recognition<br/>face-api.js 128D]
    
    %% WFA Flow
    WFA --> WFANote[Isi Catatan Pekerjaan<br/>Min 20 Karakter]
    WFANote --> ValidateNote{Panjang >= 20?}
    ValidateNote -->|Tidak| RejectNote[Reject: Catatan Kurang]
    ValidateNote -->|Ya| FaceRecogWFA[Face Recognition<br/>face-api.js 128D]
    
    %% Common Face Recognition
    FaceRecogWFO --> CompareFace{Bandingkan Embedding<br/>Similarity >= 0.85?}
    FaceRecogWFA --> CompareFace
    
    CompareFace -->|Tidak Match| RejectFace[Clock-In Ditolak<br/>Face Tidak Cocok]
    CompareFace -->|Match| CheckTime{Cek Waktu Shift}
    
    CheckTime -->|Before/On Time| OnTime[Status: on_time]
    CheckTime -->|Late + Tolerance| Late[Status: late]
    CheckTime -->|Early Clock-Out| Early[Status: early]
    
    OnTime --> SaveAttendance[Simpan Attendance<br/>is_wfa = false]
    Late --> SaveAttendance
    Early --> SaveAttendance
    
    WFA --> SetWFA[Set is_wfa = true<br/>status_wfa = pending]
    SetWFA --> SaveAttendance
    
    SaveAttendance --> CheckOvertime{Ada Request<br/>Lembur Approved?}
    CheckOvertime -->|Ya| LinkOvertime[Link Overtime ke Attendance<br/>Hitung Jam Lembur]
    CheckOvertime -->|Tidak| End([End Clock-In])
    LinkOvertime --> End
    
    RejectGPS --> End
    RejectNote --> End
    RejectFace --> End
```

---

## 2. Activity Diagram - Leave Request

```mermaid
flowchart TD
    Start([Start Leave Request]) --> FillForm[Isi Form Cuti<br/>leave_type, start_date, end_date]
    FillForm --> ValidateType{Validasi Leave Type}
    
    ValidateType -->|Sakit| UploadProof[Upload Bukti Sakit]
    ValidateType -->|Lainnya| CheckQuota
    
    UploadProof --> CheckQuota{Cek Sisa Kuota<br/>leave_balances}
    CheckQuota -->|Kuota Cukup| CalcDays[Hitung Total Days<br/>Exclude Sabtu/Minggu/Holiday]
    CheckQuota -->|Kuota Kurang| RejectQuota[Reject: Kuota Tidak Cukup]
    
    CalcDays --> ValidateOverlap{Cek Overlapping<br/>Cuti Approved/Pending?}
    ValidateOverlap -->|Ada Overlap| RejectOverlap[Reject: Tanggal Overlap]
    ValidateOverlap -->|Tidak Overlap| CheckRetroaktif{Cuti Mundur?<br/>Maksimal H+3}
    
    CheckRetroaktif -->|Valid| SaveLeave[Simpan Leave Request<br/>status = pending]
    CheckRetroaktif -->|Invalid| RejectRetro[Reject: Melebihi H+3]
    
    SaveLeave --> CreateWF[Buat Approval Workflow<br/>CreateApprovalWorkflow]
    CreateWF --> CheckParent{parent_id NULL?}
    
    CheckParent -->|Ya| SkipL1[Skip Level 1]
    CheckParent -->|Tidak| L1Approval[Level 1: Manager Approval]
    
    SkipL1 --> L2Approval[Level 2: HR Manager Approval]
    L1Approval --> L1Decision{Decision L1}
    
    L1Decision -->|Approve| L2Approval
    L1Decision -->|Reject| RejectLeave[Status: rejected<br/>Notes: rejection reason]
    
    L2Approval --> L2Decision{Decision L2}
    L2Decision -->|Approve| UpdateQuota[Kurangi leave_balances.used]
    L2Decision -->|Reject| RejectLeave
    
    UpdateQuota --> Approved[Status: approved]
    Approved --> End([End Leave Request])
    
    RejectLeave --> End
    RejectQuota --> End
    RejectOverlap --> End
    RejectRetro --> End
```

---

## 3. Activity Diagram - Approval Workflow

```mermaid
flowchart TD
    Start([Approval Workflow]) --> NewRequest[New Request Submitted<br/>Leave/Overtime]
    NewRequest --> CreateApproval[Create Approval Record<br/>approvable_type, approver_id]
    CreateApproval --> NotifyL1[Notify Level 1 Approver<br/>In-App + Email]
    
    NotifyL1 --> CheckL1Timeout{Timeout > 24 jam?}
    CheckL1Timeout -->|Ya| ReminderL1[Send Reminder<br/>In-App + Email]
    ReminderL1 --> L1Decision
    CheckL1Timeout -->|Tidak| L1Decision{Level 1 Decision}
    
    L1Decision -->|Approve| CheckL1Complete{Semua L1<br/>Approved?}
    L1Decision -->|Reject| Rejected[Status: rejected<br/>Rejection Reason]
    
    CheckL1Complete -->|Ya| NotifyL2[Notify Level 2 Approver<br/>HR Manager]
    CheckL1Complete -->|Tidak| WaitL1[Menunggu L1 Lainnya]
    
    WaitL1 --> CheckL1Timeout
    
    NotifyL2 --> CheckL2Timeout{Timeout > 24 jam?}
    CheckL2Timeout -->|Ya| ReminderL2[Send Reminder<br/>In-App + Email]
    ReminderL2 --> L2Decision
    CheckL2Timeout -->|Tidak| L2Decision{Level 2 Decision}
    
    L2Decision -->|Approve| Approved[Status: approved<br/>Execute Callback]
    L2Decision -->|Reject| Rejected
    
    Approved --> CheckAllApproved{Semua Approval<br/>Selesai?}
    CheckAllApproved -->|Ya| ProcessRequest[Process Request<br/>Update Quota/Payroll]
    CheckAllApproved -->|Tidak| WaitOther[Menunggu Approval Lain]
    
    ProcessRequest --> End([End Workflow])
    Rejected --> End
    WaitOther --> End
```

---

## 4. Activity Diagram - Payroll Generation

```mermaid
flowchart TD
    Start([Start Payroll Generation]) --> SelectPeriod[Pilih Periode<br/>Bulan & Tahun]
    SelectPeriod --> CheckCutoff{Tanggal >=<br/>payroll_cutoff_date?}
    
    CheckCutoff -->|Belum| WaitCutoff[Tunggu Cut-off Date]
    WaitCutoff --> End1([End: Belum Cut-off])
    
    CheckCutoff -->|Sudah| GetEmployees[Ambil Semua Karyawan<br/>Active + Pro-rated]
    GetEmployees --> ForkTasks{Fork: Parallel Processing}
    
    ForkTasks --> Task1[Job 1: GenerateEmployeePayroll]
    ForkTasks --> Task2[Job 2: GenerateEmployeePayroll]
    ForkTasks --> Task3[Job N: GenerateEmployeePayroll]
    
    Task1 --> CalcGross[Hitung GROSS Salary]
    Task2 --> CalcGross
    Task3 --> CalcGross
    
    CalcGross --> ProRated[CalculateProratedSalary<br/>Hari Kerja Aktual/Effective]
    ProRated --> AddAllowance[Tambah Allowance<br/>Jabatan + Makan × Hari Hadir]
    AddAllowance --> CalcOvertime[CalculateOvertimePay<br/>Upah per Jam × Rate]
    CalcOvertime --> AddTHR[Tambah THR/Bonus]
    
    AddTHR --> CalcDeductions[Hitung DEDUCTIONS]
    CalcDeductions --> CalcPPh21[CalculatePPh21 TER<br/>Kategori A/B/C]
    CalcPPh21 --> CalcBPJS[CalculateBPJS<br/>Kesehatan + JHT + JP + JKK + JKM]
    CalcBPJS --> CalcLoan[Potong Loan Deduction]
    CalcLoan --> CalcPenalty[Potong Attendance Penalty]
    
    CalcPenalty --> CalcNet[NET = GROSS - DEDUCTIONS]
    CalcNet --> SavePayroll[Simpan Payroll Record<br/>gross_salary, pph21, bpjs_*]
    SavePayroll --> GeneratePDF[Generate E-Payslip PDF<br/>2 Kolom: Pendapatan/Potongan]
    
    GeneratePDF --> JoinTasks{Join: All Jobs Complete?}
    Task1 --> JoinTasks
    Task2 --> JoinTasks
    Task3 --> JoinTasks
    
    JoinTasks --> CheckLock{Semua Berhasil?}
    CheckLock -->|Ya| Publish[Status: published<br/>LOCKED PERMANEN]
    CheckLock -->|Tidak| Error[Error: Rollback Transaction]
    
    Publish --> NotifyEmployees[Notify All Employees<br/>In-App + Email]
    NotifyEmployees --> End2([End: Payroll Published])
    Error --> End3([End: Payroll Failed])
```

---

## 5. Activity Diagram - KnowledgeBase AI RAG

```mermaid
flowchart TD
    subgraph HRD Activities
        Start([Start Upload PDF]) --> UploadPDF[Upload PDF Document<br/>Max 10MB]
        UploadPDF --> SavePDF[Simpan ke storage/app/knowledgebase/]
        SavePDF --> DispatchJob[Dispatch Job:<br/>ProcessKnowledgeBaseEmbedding]
    end
    
    subgraph Background Job
        DispatchJob --> ExtractText[Extract Text from PDF]
        ExtractText --> Chunking[Chunking ~60 Token<br/>Overlap 10 Token]
        Chunking --> GenEmbedding[Generate Embedding<br/>OpenAI text-embedding-3-small<br/>1536 dimensi]
        GenEmbedding --> SaveVector[Simpan ke DB<br/>knowledge_bases.embedding<br/>pgvector]
    end
    
    subgraph User Query
        UserAsk([User Tanya]) --> InputQuery[Input Pertanyaan]
        InputQuery --> VectorQuery[Query Vector Similarity<br/>Cosine Similarity]
        VectorQuery --> GetTopK[Ambil Top-K Chunks<br/>Relevant Documents]
        GetTopK --> SendToGemini[Send to Gemini 2.5 Pro API<br/>Context + Question]
        SendToGemini --> GetResponse[Receive AI Response<br/>+ Source References]
        GetResponse --> Display[Display to User<br/>Answer + Source Docs]
    end
    
    SaveVector --> Ready[PDF Ready for Query]
    Ready --> VectorQuery
    Display --> End([End])
```

---

## 6. Activity Diagram - WFA Approval After Clock-In

```mermaid
flowchart TD
    Start([Clock-In WFA]) --> FillNote[Isi Catatan Pekerjaan<br/>Min 20 Karakter]
    FillNote --> FaceRecog[Face Recognition<br/>128D Embedding]
    FaceRecog --> CheckFace{Face Match?}
    
    CheckFace -->|Tidak| Reject[Clock-In Ditolak]
    CheckFace -->|Ya| SaveWFA[Simpan Attendance<br/>is_wfa = true<br/>status_wfa = pending]
    
    SaveWFA --> NotifyManager[Notify Manager<br/>Review WFA Attendance]
    NotifyManager --> ManagerReview{Manager Review}
    
    ManagerReview -->|Approve| UpdateStatus[Update Status: Approved<br/>Normal Attendance]
    ManagerReview -->|Reject| SetAbsent[Set Status: absent<br/>WFA Rejected]
    
    UpdateStatus --> End1([End: WFA Approved])
    SetAbsent --> End2([End: WFA Rejected])
    Reject --> End3([End: Clock-In Failed])
```

---

## Business Rules Reference (PRD)

1. **Haversine Formula**: Validasi jarak GPS titik ke titik dengan radius tolerance per branch (PRD 2.2, 6.1)
2. **Face Recognition**: face-api.js client-side dengan 128D FaceNet embedding, threshold 0.85 (PRD 2.1, 6.1)
3. **WFA Note**: Wajib isi catatan ≥20 karakter untuk Work From Anywhere (PRD 6.1)
4. **Leave Calculation**: Exclude Sabtu, Minggu, holidays; morning/afternoon = 0.5 hari (PRD 7.2)
5. **Leave Retroaktif**: Maksimal H+3 dari tanggal cuti (PRD 7.2)
6. **Approval Workflow**: 2 Level (Manager L1 → HR Manager L2), skip L1 jika parent_id NULL (PRD 12.1)
7. **Payroll Cut-off**: Default tanggal 25, join setelah cut-off masuk bulan depan (PRD 11.1)
8. **Prorated Salary**: (Hari Kerja Aktual / Hari Kerja Efektif) × Gaji Pokok (PRD 11.3)
9. **PPh21 TER**: Kategori A/B/C berdasarkan PTKP dari marital_status + jumlah anak (PRD 11.4)
10. **BPJS**: Kesehatan 4%/1%, JHT 3.7%/2%, JP 2%/1%, ceiling di bpjs_configs (PRD 11.5)
11. **Payroll Lock**: Status published → LOCKED PERMANEN, koreksi via adjustment (PRD 11.7)
12. **KnowledgeBase AI**: PDF max 10MB, chunking 60 token, OpenAI embedding 1536D, Gemini 2.5 Pro LLM (PRD 13.1)
13. **WFA Approval**: Approval SETELAH clock-in, jika reject status bisa jadi absent (PRD 6.1)
