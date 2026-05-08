# Sequence Diagrams - HRConnect HRIS

## Deskripsi
Dokumen ini menyajikan diagram Sequence UML untuk sistem HRConnect HRIS yang menggambarkan interaksi antar objek dalam skenario tertentu. Diagram ini menunjukkan message types (sync, async, return, self-call) dan alur komunikasi antara aktor, controller, service classes, models, jobs, dan external APIs sesuai dengan arsitektur yang didefinisikan dalam PRD.

---

## 1. Sequence Diagram - Clock-In Flow (WFO + WFA)

```mermaid
sequenceDiagram
    actor E as Employee (PWA)
    participant C as AttendanceController
    participant AS as AttendanceService
    participant GPS as Geolocation API
    participant FA as face-api.js
    participant DB as PostgreSQL
    participant N as NotificationService
    
    E->>C: POST /clock-in (mode, lat, lng, faceEmbedding, wfaNote)
    activate C
    
    C->>C: validateRequest() [self-call]
    
    alt WFO Mode
        C->>GPS: getCoordinates()
        GPS-->>C: return (latitude, longitude)
        C->>AS: validateGPS(lat, lng, branch)
        activate AS
        AS->>AS: haversineFormula() [self-call]
        AS-->>C: return true/false
        deactivate AS
        
        alt GPS Valid (distance <= radius)
            C->>FA: validateFace(liveEmbedding, storedEmbedding)
            activate FA
            FA->>FA: compare128DEmbedding() [self-call]
            FA-->>C: return similarityScore
            deactivate FA
            
            alt Face Match (score >= 0.85)
                C->>DB: createAttendance(employee_id, clock_in, status, is_wfa=false)
                activate DB
                DB-->>C: return Attendance
                deactivate DB
                
                C->>AS: linkOvertimeToAttendance(attendance)
                activate AS
                AS->>DB: findApprovedOvertime(employee_id, date)
                activate DB
                DB-->>AS: return Overtime or null
                deactivate DB
                
                alt Overtime Found
                    AS->>DB: updateOvertime(attendance_id)
                    activate DB
                    DB-->>AS: return updated
                    deactivate DB
                end
                deactivate AS
                
                C-->>E: return success (Attendance created)
            else Face Not Match
                C-->>E: return error (Face recognition failed)
            end
        else GPS Invalid
            C-->>E: return error (Outside geofence radius)
        end
    else WFA Mode
        C->>C: validateWfaNote(wfaNote) [self-call]
        
        alt Note Valid (>= 20 chars)
            C->>FA: validateFace(liveEmbedding, storedEmbedding)
            activate FA
            FA-->>C: return similarityScore
            deactivate FA
            
            alt Face Match
                C->>DB: createAttendance(employee_id, clock_in, is_wfa=true, status_wfa=pending)
                activate DB
                DB-->>C: return Attendance
                deactivate DB
                
                C->>N: notifyManager(manager_id, attendance) [async]
                activate N
                N->>N: sendInApp() [self-call]
                N->>N: sendEmail() [self-call]
                deactivate N
                
                C-->>E: return success (WFA pending approval)
            else Face Not Match
                C-->>E: return error (Face recognition failed)
            end
        else Note Invalid
            C-->>E: return error (Note too short, min 20 chars)
        end
    end
    
    deactivate C
```

---

## 2. Sequence Diagram - Leave Approval Flow

```mermaid
sequenceDiagram
    actor E as Employee
    participant C as LeaveController
    participant LS as LeaveService
    participant DB as PostgreSQL
    participant AS as ApprovalService
    participant M as Manager (L1)
    participant HR as HR Manager (L2)
    participant N as NotificationService
    
    E->>C: POST /leaves (leave_type, start_date, end_date, day_type)
    activate C
    
    C->>LS: calculateTotalDays(start, end, day_type)
    activate LS
    LS->>DB: getHolidays(start, end)
    activate DB
    DB-->>LS: return holidays[]
    deactivate DB
    LS->>LS: calculateWorkDays() [self-call]
    LS-->>C: return totalDays (float)
    deactivate LS
    
    C->>LS: validateLeaveQuota(employee, leaveType, totalDays)
    activate LS
    LS->>DB: getLeaveBalance(employee_id, leave_type_id, year)
    activate DB
    DB-->>LS: return LeaveBalance (quota, used)
    deactivate DB
    LS-->>C: return true/false
    deactivate LS
    
    alt Quota Available
        C->>DB: createLeave(employee_id, leave_type_id, totalDays, status=pending)
        activate DB
        DB-->>C: return Leave
        deactivate DB
        
        C->>AS: createApprovalWorkflow(leave)
        activate AS
        AS->>DB: getDirectApprover(employee)
        activate DB
        DB-->>AS: return approver (Manager or fallback HR)
        deactivate DB
        
        alt parent_id NOT NULL
            AS->>DB: createApproval(leave, approver_id, level=1, status=pending)
            activate DB
            DB-->>AS: return Approval L1
            deactivate DB
            
            AS->>N: notifyApprover(L1) [async]
            activate N
            N-->>M: In-App + Email Notification
            deactivate N
            
            M->>C: POST /approvals/{id}/approve (notes)
            activate C
            C->>AS: approve(approval, notes)
            activate AS
            AS->>DB: updateApproval(status=approved)
            activate DB
            DB-->>AS: return updated
            deactivate DB
            
            AS->>AS: checkAllApproved(leave) [self-call]
            alt L1 Approved, Continue to L2
                AS->>DB: createApproval(leave, hr_manager_id, level=2, status=pending)
                activate DB
                DB-->>AS: return Approval L2
                deactivate DB
                
                AS->>N: notifyApprover(L2) [async]
                activate N
                N-->>HR: In-App + Email Notification
                deactivate N
                
                HR->>C: POST /approvals/{id}/approve (notes)
                activate C
                C->>AS: approve(approval, notes)
                activate AS
                AS->>DB: updateApproval(status=approved)
                activate DB
                DB-->>AS: return updated
                deactivate DB
                
                AS->>AS: checkAllApproved(leave) [self-call]
                alt All Approved
                    AS->>DB: updateLeave(status=approved)
                    activate DB
                    DB-->>AS: return updated
                    deactivate DB
                    AS->>LS: applyLeave(leave) [deduct quota]
                    activate LS
                    LS->>DB: updateLeaveBalance(used += totalDays)
                    activate DB
                    DB-->>LS: return updated
                    deactivate DB
                    deactivate LS
                    
                    AS->>N: notifyEmployee(approved) [async]
                    activate N
                    N-->>E: In-App + Email: Leave Approved
                    deactivate N
                end
                deactivate AS
                deactivate C
            end
            deactivate AS
            deactivate C
        else parent_id NULL (Skip L1)
            AS->>DB: createApproval(leave, hr_manager_id, level=2, status=pending)
            activate DB
            DB-->>AS: return Approval L2 (direct)
            deactivate DB
            Note over AS,HR: Skip to L2 directly
        end
        deactivate AS
        
        C-->>E: return success (Leave submitted)
    else Quota Exceeded
        C-->>E: return error (Insufficient leave quota)
    end
    
    deactivate C
```

---

## 3. Sequence Diagram - Payroll Generate Flow

```mermaid
sequenceDiagram
    actor F as Finance
    participant C as PayrollController
    participant PCS as PayrollCalculatorService
    participant DB as PostgreSQL
    participant J as GenerateEmployeePayrollJob
    participant N as NotificationService
    
    F->>C: POST /payrolls/generate (period_month, period_year)
    activate C
    
    C->>DB: getCompanySetting(payroll_cutoff_date)
    activate DB
    DB-->>C: return cutoffDate (default: 25)
    deactivate DB
    
    C->>DB: getEmployees(active, join_date <= cutoff)
    activate DB
    DB-->>C: return employees[]
    deactivate DB
    
    loop For Each Employee
        C->>J: dispatch(employee_id, period) [async] [queue: payroll_high]
        activate J
        Note over J: Tries: 3, Timeout: 120s, Backoff: [10,30,60]
        
        J->>DB: getEmployee(employee_id)
        activate DB
        DB-->>J: return Employee
        deactivate DB
        
        J->>PCS: calculateProratedSalary(employee, period)
        activate PCS
        PCS->>PCS: countWorkingDays(start, end) [self-call]
        PCS->>DB: getHolidays(start, end)
        activate DB
        DB-->>PCS: return holidays[]
        deactivate DB
        PCS->>PCS: calculateProRated() [self-call]
        PCS-->>J: return grossSalary
        deactivate PCS
        
        J->>PCS: calculatePPh21(grossIncome, category)
        activate PCS
        PCS->>PCS: getTERCategory(employee) [self-call]
        PCS->>DB: getTaxConfig(category, min, max)
        activate DB
        DB-->>PCS: return taxConfig
        deactivate DB
        PCS->>PCS: calculatePPh21TER() [self-call]
        PCS-->>J: return pph21Amount
        deactivate PCS
        
        J->>PCS: calculateBPJS(employee, grossIncome)
        activate PCS
        PCS->>DB: getBPJSConfigs()
        activate DB
        DB-->>PCS: return bpjsConfigs[]
        deactivate DB
        PCS->>PCS: calculateBPJSHealth() [self-call]
        PCS->>PCS: calculateBPJSJHT() [self-call]
        PCS->>PCS: calculateBPJSJP() [self-call]
        PCS-->>J: return bpjsDeductions[]
        deactivate PCS
        
        J->>DB: createPayroll(employee_id, period, gross, pph21, bpjs_*)
        activate DB
        DB-->>J: return Payroll
        deactivate DB
        
        J->>J: generatePdf() [self-call]
        J->>DB: savePdfToStorage(payslips/{period}/{employee_id}.pdf)
        activate DB
        DB-->>J: return path
        deactivate DB
        
        deactivate J
    end
    
    C->>DB: updatePayrollStatus(status=published)
    activate DB
    DB-->>C: return updated
    deactivate DB
    
    Note over C,N: PAYROLL LOCKED PERMANENTLY
    
    C->>N: notifyAllEmployees(payroll) [async]
    activate N
    loop For Each Employee
        N-->>F: In-App + Email: Payroll Published
    end
    deactivate N
    
    C-->>F: return success (Payroll generated and published)
    deactivate C
```

---

## 4. Sequence Diagram - RAG Chat Flow (KnowledgeBase AI)

```mermaid
sequenceDiagram
    actor U as User (Employee/HR)
    participant C as KnowledgeBaseController
    participant KBS as KnowledgeBaseService
    participant DB as PostgreSQL (pgvector)
    participant OA as OpenAI API
    participant GM as Gemini 2.5 Pro API
    
    U->>C: POST /knowledgebase/chat (question)
    activate C
    
    C->>KBS: processQuery(question)
    activate KBS
    
    KBS->>OA: createEmbedding(text-embedding-3-small, question)
    activate OA
    Note over OA: Generate 1536-dimension embedding
    OA-->>KBS: return queryEmbedding[1536]
    deactivate OA
    
    KBS->>DB: vectorSimilaritySearch(queryEmbedding, topK=5)
    activate DB
    Note over DB: cosine similarity with pgvector<br/>knowledge_bases.embedding <-> queryEmbedding
    DB-->>KBS: return topChunks[text, source, page]
    deactivate DB
    
    KBS->>GM: generateResponse(model=gemini-2.5-pro, context=topChunks, question)
    activate GM
    Note over GM: Send context + question<br/>Receive AI-generated answer
    GM-->>KBS: return answer + sources[]
    deactivate GM
    
    KBS-->>C: return response(answer, sources)
    deactivate KBS
    
    C-->>U: return JSON {answer, sources: [{document, page}]}
    deactivate C
    
    Note over U: User receives AI answer with source references
```

---

## 5. Sequence Diagram - KnowledgeBase PDF Upload & Embedding

```mermaid
sequenceDiagram
    actor HR as HR Manager
    participant C as KnowledgeBaseController
    participant DB as PostgreSQL
    participant J as ProcessKnowledgeBaseEmbedding
    participant FS as File Storage
    participant PDF as PDF Parser
    participant OA as OpenAI API
    
    HR->>C: POST /knowledgebase/upload (pdf_file, title)
    activate C
    
    C->>C: validateFile(uploadedFile) [self-call]
    Note over C: Max 10MB, PDF only
    
    C->>FS: store(storage/app/knowledgebase/{filename})
    activate FS
    FS-->>C: return filePath
    deactivate FS
    
    C->>DB: createKnowledgeBase(title, filePath, status=processing)
    activate DB
    DB-->>C: return KnowledgeBase
    deactivate DB
    
    C->>J: dispatch(knowledge_base_id) [async] [queue: default]
    activate J
    Note over J: Tries: 2, Timeout: 300s
    
    J->>PDF: extractText(filePath)
    activate PDF
    PDF-->>J: return fullText
    deactivate PDF
    
    J->>J: chunking(fullText, ~60 tokens, overlap 10) [self-call]
    Note over J: Split into chunks
    
    loop For Each Chunk
        J->>OA: createEmbedding(text-embedding-3-small, chunk)
        activate OA
        Note over OA: Generate 1536-dimension embedding
        OA-->>J: return chunkEmbedding[1536]
        deactivate OA
        
        J->>DB: insertKnowledgeBaseChunk(knowledge_base_id, chunk, embedding, page)
        activate DB
        DB-->>J: return saved
        deactivate DB
    end
    
    J->>DB: updateKnowledgeBase(status=ready)
    activate DB
    DB-->>J: return updated
    deactivate DB
    
    deactivate J
    
    C-->>HR: return success (PDF uploaded, processing started)
    deactivate C
```

---

## Business Rules Reference (PRD)

1. **Face Recognition**: face-api.js 128D FaceNet embedding, threshold 0.85 (PRD 2.1, 6.1)
2. **Haversine Formula**: Validasi jarak GPS titik ke titik dengan radius tolerance per branch (PRD 2.2)
3. **WFA Mode**: GPS dilewati, catatan ≥20 karakter, approval SETELAH clock-in (PRD 6.1)
4. **Leave Calculation**: Exclude Sabtu/Minggu/holidays; morning/afternoon = 0.5 hari (PRD 7.2)
5. **Leave Quota**: Pro-rated tahun pertama, carry forward maksimal 3 hari (PRD 7.3)
6. **Approval Workflow**: 2 level, skip L1 jika parent_id NULL (PRD 12.1)
7. **Payroll Queue**: queue: payroll_high, tries: 3, timeout: 120s, backoff: [10,30,60] (PRD 11.9)
8. **Prorated Salary**: (Hari Kerja Aktual / Hari Kerja Efektif) × Gaji Pokok (PRD 11.3)
9. **PPh21 TER**: Dihitung per bulan, kategori A/B/C dari PTKP (PRD 11.4)
10. **BPJS**: Kesehatan 4%/1%, JHT 3.7%/2%, JP 2%/1%, ceiling di bpjs_configs (PRD 11.5)
11. **Payroll Lock**: Status published → LOCKED PERMANEN (PRD 11.7)
12. **KnowledgeBase AI**: PDF max 10MB, chunking 60 token, OpenAI embedding 1536D, Gemini 2.5 Pro (PRD 13.1)
13. **Overtime Link**: Saat Clock-Out, Observer link overtime ke attendance (PRD 8.2)
