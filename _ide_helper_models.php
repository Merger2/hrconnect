<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $description
 * @property int $count
 * @property string|null $ip_address
 * @property string|null $integrity_hash
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityLogDetail> $details
 * @property-read int|null $details_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereIntegrityHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperActivityLog {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $activity_log_id
 * @property string $entity_type
 * @property int $entity_id
 * @property string $field
 * @property array<array-key, mixed>|null $old_value
 * @property array<array-key, mixed>|null $new_value
 * @property array<array-key, mixed>|null $metadata
 * @property string $integrity_hash
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\ActivityLog $activityLog
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLogDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLogDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLogDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLogDetail whereActivityLogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLogDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLogDetail whereEntityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLogDetail whereEntityType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLogDetail whereField($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLogDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLogDetail whereIntegrityHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLogDetail whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLogDetail whereNewValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLogDetail whereOldValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLogDetail whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperActivityLogDetail {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property string $content
 * @property string $priority
 * @property int $created_by
 * @property string $modal_behavior
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $published_at
 * @property \Carbon\CarbonImmutable|null $expired_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string|null $deleted_at
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $dismissedByUsers
 * @property-read int|null $dismissed_by_users_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $viewedByUsers
 * @property-read int|null $viewed_by_users_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $views
 * @property-read int|null $views_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement published()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement visible()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement visibleForUser($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereExpiredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereModalBehavior($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Announcement whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAnnouncement {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $announcement_id
 * @property int $user_id
 * @property string|null $viewed_at
 * @property string|null $dismissed_at
 * @property string|null $seen_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnouncementUserView newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnouncementUserView newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnouncementUserView query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnouncementUserView whereAnnouncementId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnouncementUserView whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnouncementUserView whereDismissedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnouncementUserView whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnouncementUserView whereSeenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnouncementUserView whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnouncementUserView whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnouncementUserView whereViewedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAnnouncementUserView {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int $reviewer_id
 * @property string $period
 * @property string $review_date
 * @property numeric|null $final_score
 * @property string $status
 * @property string|null $notes
 * @property string|null $recommendations
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property int|null $user_id
 * @property \Carbon\CarbonImmutable|null $meeting_date
 * @property bool $employee_acknowledgement
 * @property int|null $evaluator_id
 * @property int|null $calibrator_id
 * @property string|null $calibration_status
 * @property-read \App\Models\User|null $calibrator
 * @property-read \App\Models\Employee|null $employee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AppraisalEvaluation> $evaluations
 * @property-read int|null $evaluations_count
 * @property-read \App\Models\User|null $evaluator
 * @property-read \App\Models\Employee|null $reviewer
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereCalibrationStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereCalibratorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereEmployeeAcknowledgement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereEvaluatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereFinalScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereMeetingDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal wherePeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereRecommendations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereReviewDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereReviewerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appraisal whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAppraisal {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $appraisal_id
 * @property int|null $kpi_template_id
 * @property numeric|null $self_score
 * @property numeric|null $manager_score
 * @property string|null $comments
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Appraisal $appraisal
 * @property-read \App\Models\KpiTemplate|null $kpiTemplate
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppraisalEvaluation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppraisalEvaluation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppraisalEvaluation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppraisalEvaluation whereAppraisalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppraisalEvaluation whereComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppraisalEvaluation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppraisalEvaluation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppraisalEvaluation whereKpiTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppraisalEvaluation whereManagerScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppraisalEvaluation whereSelfScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppraisalEvaluation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAppraisalEvaluation {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $approver_id
 * @property \Carbon\CarbonImmutable|null $approved_at
 * @property string $approvable_type
 * @property int $approvable_id
 * @property \App\Enums\ApprovalLevel $level
 * @property \App\Enums\ApprovalStatus $status
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $approvable
 * @property-read \App\Models\Employee|null $approver
 * @method static \Database\Factories\ApprovalFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereApprovableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereApprovableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereApproverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Approval whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperApproval {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $module_name
 * @property string $condition_type
 * @property string $condition_value
 * @property int $approval_level
 * @property int|null $approver_id
 * @property int|null $approver_role_id
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalMatrixRule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalMatrixRule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalMatrixRule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalMatrixRule whereApprovalLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalMatrixRule whereApproverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalMatrixRule whereApproverRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalMatrixRule whereConditionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalMatrixRule whereConditionValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalMatrixRule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalMatrixRule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalMatrixRule whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalMatrixRule whereModuleName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalMatrixRule whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperApprovalMatrixRule {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $company_id
 * @property string $name
 * @property string $serial_number
 * @property string|null $code
 * @property string|null $category
 * @property bool $is_available
 * @property \App\Enums\AssetStatus $status
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Company|null $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AssetHandover> $handovers
 * @property-read int|null $handovers_count
 * @method static \Database\Factories\AssetFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereIsAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAsset {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $asset_id
 * @property int $employee_id
 * @property \Carbon\CarbonImmutable $handover_date
 * @property \Carbon\CarbonImmutable|null $return_date
 * @property string $condition
 * @property \App\Enums\HandoverCategory $category
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Asset|null $asset
 * @property-read \App\Models\Employee|null $employee
 * @method static \Database\Factories\AssetHandoverFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover whereAssetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover whereCondition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover whereHandoverDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover whereReturnDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetHandover whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAssetHandover {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int|null $shift_id
 * @property CarbonImmutable $date
 * @property CarbonImmutable|null $clock_in Null jika status=absent dari DetectAlphaAttendanceCommand
 * @property CarbonImmutable|null $clock_out
 * @property numeric|null $lat_in
 * @property numeric|null $long_in
 * @property bool $clock_in_is_mocked
 * @property numeric|null $clock_in_accuracy
 * @property numeric|null $lat_out
 * @property numeric|null $long_out
 * @property bool|null $clock_out_is_mocked
 * @property numeric|null $clock_out_accuracy
 * @property string|null $device_fingerprint
 * @property numeric|null $face_similarity_score Akurasi kemiripan wajah dalam persentase (%) - clock-in
 * @property VerificationMethod|null $clock_out_verification_method face_verified|pin_verified|manual
 * @property numeric|null $clock_out_face_similarity_score Akurasi kemiripan wajah clock-out
 * @property string|null $photo_selfie_in
 * @property string|null $photo_selfie_out
 * @property AttendanceStatus $status
 * @property bool $is_wfa
 * @property ApprovalStatus|null $status_wfa
 * @property string|null $exception_type
 * @property string|null $exception_notes
 * @property int|null $approved_late_by
 * @property VerificationMethod|null $verification_method face_verified|pin_verified|manual
 * @property string|null $wfa_note
 * @property int $late_minutes
 * @property int|null $risk_score
 * @property string|null $risk_level
 * @property string|null $risk_factors
 * @property int|null $device_id
 * @property string $verification_type
 * @property string|null $photo_clock_in
 * @property string|null $photo_clock_out
 * @property string|null $ip_address
 * @property bool $is_offline_sync
 * @property string|null $synced_at
 * @property string|null $latitude
 * @property string|null $longitude
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property ApprovalStatus|null $approval_status
 * @property int|null $leave_type_id
 * @property string|null $note
 * @property-read Collection<int, Approval> $approvals
 * @property-read int|null $approvals_count
 * @property-read Employee|null $approvedLateBy
 * @property-read mixed $attachment
 * @property-read mixed $attachment_url
 * @property-read Employee|null $employee
 * @property-read mixed $lat_lng
 * @property-read mixed $latitude_in
 * @property-read mixed $latitude_out
 * @property-read mixed $longitude_in
 * @property-read mixed $longitude_out
 * @property-read Overtime|null $overtime
 * @property-read Shift|null $shift
 * @property-read mixed $time_in
 * @property-read mixed $time_out
 * @property-read User|null $user
 * @method static \Database\Factories\AttendanceFactory factory($count = null, $state = [])
 * @method static Builder<static>|Attendance managedBy(\App\Models\User $admin)
 * @method static Builder<static>|Attendance newModelQuery()
 * @method static Builder<static>|Attendance newQuery()
 * @method static Builder<static>|Attendance onlyTrashed()
 * @method static Builder<static>|Attendance query()
 * @method static Builder<static>|Attendance whereApprovalStatus($value)
 * @method static Builder<static>|Attendance whereApprovedLateBy($value)
 * @method static Builder<static>|Attendance whereClockIn($value)
 * @method static Builder<static>|Attendance whereClockInAccuracy($value)
 * @method static Builder<static>|Attendance whereClockInIsMocked($value)
 * @method static Builder<static>|Attendance whereClockOut($value)
 * @method static Builder<static>|Attendance whereClockOutAccuracy($value)
 * @method static Builder<static>|Attendance whereClockOutFaceSimilarityScore($value)
 * @method static Builder<static>|Attendance whereClockOutIsMocked($value)
 * @method static Builder<static>|Attendance whereClockOutVerificationMethod($value)
 * @method static Builder<static>|Attendance whereCreatedAt($value)
 * @method static Builder<static>|Attendance whereDate($value)
 * @method static Builder<static>|Attendance whereDeletedAt($value)
 * @method static Builder<static>|Attendance whereDeviceFingerprint($value)
 * @method static Builder<static>|Attendance whereDeviceId($value)
 * @method static Builder<static>|Attendance whereEmployeeId($value)
 * @method static Builder<static>|Attendance whereExceptionNotes($value)
 * @method static Builder<static>|Attendance whereExceptionType($value)
 * @method static Builder<static>|Attendance whereFaceSimilarityScore($value)
 * @method static Builder<static>|Attendance whereId($value)
 * @method static Builder<static>|Attendance whereIpAddress($value)
 * @method static Builder<static>|Attendance whereIsOfflineSync($value)
 * @method static Builder<static>|Attendance whereIsWfa($value)
 * @method static Builder<static>|Attendance whereLatIn($value)
 * @method static Builder<static>|Attendance whereLatOut($value)
 * @method static Builder<static>|Attendance whereLateMinutes($value)
 * @method static Builder<static>|Attendance whereLatitude($value)
 * @method static Builder<static>|Attendance whereLeaveTypeId($value)
 * @method static Builder<static>|Attendance whereLongIn($value)
 * @method static Builder<static>|Attendance whereLongOut($value)
 * @method static Builder<static>|Attendance whereLongitude($value)
 * @method static Builder<static>|Attendance whereNote($value)
 * @method static Builder<static>|Attendance wherePhotoClockIn($value)
 * @method static Builder<static>|Attendance wherePhotoClockOut($value)
 * @method static Builder<static>|Attendance wherePhotoSelfieIn($value)
 * @method static Builder<static>|Attendance wherePhotoSelfieOut($value)
 * @method static Builder<static>|Attendance whereRiskFactors($value)
 * @method static Builder<static>|Attendance whereRiskLevel($value)
 * @method static Builder<static>|Attendance whereRiskScore($value)
 * @method static Builder<static>|Attendance whereShiftId($value)
 * @method static Builder<static>|Attendance whereStatus($value)
 * @method static Builder<static>|Attendance whereStatusWfa($value)
 * @method static Builder<static>|Attendance whereSyncedAt($value)
 * @method static Builder<static>|Attendance whereUpdatedAt($value)
 * @method static Builder<static>|Attendance whereVerificationMethod($value)
 * @method static Builder<static>|Attendance whereVerificationType($value)
 * @method static Builder<static>|Attendance whereWfaNote($value)
 * @method static Builder<static>|Attendance withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Attendance withoutTrashed()
 * @property int|null $approved_by
 * @property string|null $approved_at
 * @property string|null $rejection_note
 * @property-read \App\Models\LeaveType|null $leaveType
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attendance whereRejectionNote($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAttendance {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property \Carbon\CarbonImmutable $attendance_date
 * @property \Carbon\CarbonImmutable|null $actual_clock_in
 * @property \Carbon\CarbonImmutable|null $actual_clock_out
 * @property string $reason
 * @property string|null $attachment
 * @property string $status
 * @property int|null $approved_by
 * @property \Carbon\CarbonImmutable|null $approved_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property int|null $user_id
 * @property int|null $attendance_id
 * @property string|null $request_type
 * @property \Carbon\CarbonImmutable|null $requested_time_in
 * @property \Carbon\CarbonImmutable|null $requested_time_out
 * @property int|null $requested_shift_id
 * @property array<array-key, mixed>|null $current_snapshot
 * @property int|null $head_approved_by
 * @property \Carbon\CarbonImmutable|null $head_approved_at
 * @property int|null $reviewed_by
 * @property \Carbon\CarbonImmutable|null $reviewed_at
 * @property string|null $rejection_note
 * @property-read \App\Models\User|null $approver
 * @property-read \App\Models\Attendance|null $attendance
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\User|null $headApprover
 * @property-read \App\Models\Shift|null $requestedShift
 * @property-read \App\Models\User|null $reviewer
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereActualClockIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereActualClockOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereAttachment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereAttendanceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereAttendanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereCurrentSnapshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereHeadApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereHeadApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereRejectionNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereRequestType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereRequestedShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereRequestedTimeIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereRequestedTimeOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceCorrection whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAttendanceCorrection {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property \Carbon\CarbonImmutable $attendance_date
 * @property \Carbon\CarbonImmutable|null $clock_in
 * @property \Carbon\CarbonImmutable|null $clock_out
 * @property string $latitude
 * @property string $longitude
 * @property string|null $photo_path
 * @property string $status
 * @property string|null $sync_error
 * @property \Carbon\CarbonImmutable|null $synced_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceOfflineSubmission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceOfflineSubmission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceOfflineSubmission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceOfflineSubmission whereAttendanceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceOfflineSubmission whereClockIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceOfflineSubmission whereClockOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceOfflineSubmission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceOfflineSubmission whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceOfflineSubmission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceOfflineSubmission whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceOfflineSubmission whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceOfflineSubmission wherePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceOfflineSubmission whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceOfflineSubmission whereSyncError($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceOfflineSubmission whereSyncedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AttendanceOfflineSubmission whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAttendanceOfflineSubmission {}
}

namespace App\Models{
/**
 * @property int $id
 * @property \App\Enums\BpjsType $name
 * @property numeric $employer_rate
 * @property numeric $employee_rate
 * @property numeric|null $ceiling
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Database\Factories\BpjsConfigFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BpjsConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BpjsConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BpjsConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BpjsConfig whereCeiling($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BpjsConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BpjsConfig whereEmployeeRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BpjsConfig whereEmployerRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BpjsConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BpjsConfig whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BpjsConfig whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperBpjsConfig {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $code
 * @property string $type
 * @property string|null $address
 * @property string|null $alamat_lengkap
 * @property string|null $telepon
 * @property string|null $email
 * @property bool $is_main
 * @property bool $is_active
 * @property numeric|null $latitude Titik Y Pusat Kantor
 * @property numeric|null $longitude Titik X Pusat Kantor
 * @property int $radius Batas toleransi absen dalam meter
 * @property array<array-key, mixed>|null $metadata
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Division> $divisions
 * @property-read int|null $divisions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Employee> $employees
 * @property-read int|null $employees_count
 * @method static \Database\Factories\BranchFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch orWhereBlind(string $column, string $indexName, array|string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereAlamatLengkap($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereBlind(string $column, string $indexName, array|string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereIsMain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereRadius($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereTelepon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperBranch {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property numeric $amount
 * @property string|null $purpose
 * @property string $status
 * @property int|null $approval_matrix_rule_id
 * @property array<array-key, mixed>|null $approval_steps
 * @property string|null $approval_current_step
 * @property array<array-key, mixed>|null $approval_completed_steps
 * @property int|null $payment_month
 * @property int|null $payment_year
 * @property int|null $approved_by
 * @property \Carbon\CarbonImmutable|null $approved_at
 * @property int|null $head_approved_by
 * @property \Carbon\CarbonImmutable|null $head_approved_at
 * @property int|null $finance_approved_by
 * @property \Carbon\CarbonImmutable|null $finance_approved_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User|null $approver
 * @property-read \App\Models\User|null $financeApprover
 * @property-read \App\Models\User|null $headApprover
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereApprovalCompletedSteps($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereApprovalCurrentStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereApprovalMatrixRuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereApprovalSteps($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereFinanceApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereFinanceApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereHeadApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereHeadApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance wherePaymentMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance wherePaymentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance wherePurpose($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCashAdvance {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $chat_thread_id
 * @property string $body
 * @property string|null $attachment_path
 * @property \Carbon\CarbonImmutable|null $read_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property int $user_id
 * @property string|null $attachment_disk
 * @property string|null $attachment_name
 * @property string|null $attachment_mime
 * @property int|null $attachment_size
 * @property array<array-key, mixed>|null $metadata
 * @property-read \App\Models\ChatThread|null $thread
 * @property-read \App\Models\User|null $user
 * @method static \Database\Factories\ChatMessageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereAttachmentDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereAttachmentMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereAttachmentName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereAttachmentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereAttachmentSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereChatThreadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessage whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperChatMessage {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $chat_session_id
 * @property string $role
 * @property string $message
 * @property array<array-key, mixed>|null $sources
 * @property int|null $tokens_used
 * @property int|null $response_time_ms
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\ChatSession $chatSession
 * @property-read mixed $sources_array
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessageRag assistant()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessageRag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessageRag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessageRag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessageRag user()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessageRag whereChatSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessageRag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessageRag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessageRag whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessageRag whereResponseTimeMs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessageRag whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessageRag whereSources($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessageRag whereTokensUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatMessageRag whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperChatMessageRag {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string|null $title
 * @property string|null $context
 * @property string $status
 * @property \Carbon\CarbonImmutable|null $last_message_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChatMessageRag> $messages
 * @property-read int|null $messages_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatSession active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatSession archived()
 * @method static \Database\Factories\ChatSessionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatSession newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatSession query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatSession whereContext($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatSession whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatSession whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatSession whereLastMessageAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatSession whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatSession whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatSession whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperChatSession {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $title
 * @property string $type
 * @property int $created_by
 * @property string|null $last_message_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property int|null $company_id
 * @property bool $is_archived
 * @property array<array-key, mixed>|null $metadata
 * @property-read \App\Models\Company|null $company
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $members
 * @property-read int|null $members_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChatMessage> $messages
 * @property-read int|null $messages_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatThread newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatThread newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatThread onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatThread query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatThread whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatThread whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatThread whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatThread whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatThread whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatThread whereIsArchived($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatThread whereLastMessageAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatThread whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatThread whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatThread whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatThread whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatThread withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChatThread withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperChatThread {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $code
 * @property string|null $contact_name
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project> $projects
 * @property-read int|null $projects_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereContactEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperClient {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $company_id
 * @property int|null $project_id
 * @property int|null $chat_thread_id
 * @property int|null $owner_id
 * @property string $disk
 * @property string $path
 * @property string|null $original_name
 * @property string|null $mime_type
 * @property int $size
 * @property string $visibility
 * @property string|null $checksum
 * @property array<array-key, mixed>|null $metadata
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Company|null $company
 * @property-read \App\Models\User|null $owner
 * @property-read \App\Models\ChatThread|null $thread
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CloudFile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CloudFile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CloudFile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CloudFile whereChatThreadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CloudFile whereChecksum($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CloudFile whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CloudFile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CloudFile whereDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CloudFile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CloudFile whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CloudFile whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CloudFile whereOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CloudFile whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CloudFile wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CloudFile whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CloudFile whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CloudFile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CloudFile whereVisibility($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCloudFile {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $type
 * @property string|null $icon
 * @property string|null $cover_photo
 * @property bool $is_active
 * @property int|null $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\User|null $creator
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Community newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Community newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Community onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Community query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Community whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Community whereCoverPhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Community whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Community whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Community whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Community whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Community whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Community whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Community whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Community whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Community whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Community whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Community whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Community withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Community withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCommunity {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property string $email
 * @property string|null $website
 * @property string|null $npwp
 * @property string $code
 * @property string|null $logo
 * @property bool $is_active
 * @property string|null $slug
 * @property string $status
 * @property string|null $kode_perusahaan
 * @property string|null $kebijakan_cuti
 * @property string|null $kebijakan_lembur
 * @property string|null $kebijakan_overtime
 * @property string|null $tax_no
 * @property string|null $alasan_resign
 * @property string|null $bank_name
 * @property string|null $nomor_rekening
 * @property string|null $npwp_resign
 * @property string|null $telepon
 * @property string $status_perusahaan
 * @property string|null $inisial
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Branch> $branches
 * @property-read int|null $branches_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Employee> $employees
 * @property-read int|null $employees_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CompanySetting> $settings
 * @property-read int|null $settings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\CompanyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company orWhereBlind(string $column, string $indexName, array|string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereAlasanResign($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereBankName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereBlind(string $column, string $indexName, array|string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereInisial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereKebijakanCuti($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereKebijakanLembur($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereKebijakanOvertime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereKodePerusahaan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereNomorRekening($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereNpwp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereNpwpResign($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereStatusPerusahaan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereTaxNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereTelepon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereWebsite($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCompany {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $type
 * @property string|null $serial_number
 * @property numeric|null $purchase_cost
 * @property \Carbon\CarbonImmutable|null $purchase_date
 * @property string $status
 * @property int|null $user_id
 * @property \Carbon\CarbonImmutable|null $date_assigned
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \Carbon\CarbonImmutable|null $expiration_date
 * @property \Carbon\CarbonImmutable|null $return_date
 * @property string|null $notes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CompanyAssetHistory> $histories
 * @property-read int|null $histories_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset whereDateAssigned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset whereExpirationDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset wherePurchaseCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset wherePurchaseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset whereReturnDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAsset withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCompanyAsset {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_asset_id
 * @property string $action
 * @property int|null $from_employee_id
 * @property int|null $to_employee_id
 * @property string|null $notes
 * @property int|null $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\CompanyAsset|null $asset
 * @property-read \App\Models\Employee|null $creator
 * @property-read \App\Models\Employee|null $fromEmployee
 * @property-read \App\Models\Employee|null $toEmployee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAssetHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAssetHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAssetHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAssetHistory whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAssetHistory whereCompanyAssetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAssetHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAssetHistory whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAssetHistory whereFromEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAssetHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAssetHistory whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAssetHistory whereToEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyAssetHistory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCompanyAssetHistory {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $code
 * @property string $type
 * @property string|null $address
 * @property string $status
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Company $company
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyBranch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyBranch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyBranch onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyBranch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyBranch whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyBranch whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyBranch whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyBranch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyBranch whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyBranch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyBranch whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyBranch whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyBranch whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyBranch whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyBranch withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyBranch withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCompanyBranch {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $company_id
 * @property string $key
 * @property array<array-key, mixed>|null $value
 * @property string|null $description
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Company|null $company
 * @method static \Database\Factories\CompanySettingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanySetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanySetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanySetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanySetting whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanySetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanySetting whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanySetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanySetting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanySetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanySetting whereValue($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCompanySetting {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $custom_form_template_id
 * @property int|null $company_id
 * @property int|null $submitted_by
 * @property string $status
 * @property array<array-key, mixed>|null $payload
 * @property array<array-key, mixed>|null $metadata
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User|null $submitter
 * @property-read \App\Models\CustomFormTemplate $template
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormSubmission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormSubmission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormSubmission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormSubmission whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormSubmission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormSubmission whereCustomFormTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormSubmission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormSubmission whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormSubmission wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormSubmission whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormSubmission whereSubmittedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormSubmission whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCustomFormSubmission {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $company_id
 * @property string $title
 * @property string|null $category
 * @property string|null $description
 * @property array<array-key, mixed>|null $fields
 * @property bool $is_active
 * @property array<array-key, mixed>|null $metadata
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Company|null $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CustomFormSubmission> $submissions
 * @property-read int|null $submissions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormTemplate whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormTemplate whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormTemplate whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormTemplate whereFields($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormTemplate whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormTemplate whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormTemplate whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomFormTemplate whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperCustomFormTemplate {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string $device_uuid
 * @property string|null $device_type
 * @property string|null $device_name
 * @property string|null $browser
 * @property string|null $os
 * @property bool $is_verified
 * @property \Carbon\CarbonImmutable|null $verified_at
 * @property \Carbon\CarbonImmutable|null $last_used_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @method static \Database\Factories\DeviceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereBrowser($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereDeviceName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereDeviceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereDeviceUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereIsVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereLastUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereOs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereVerifiedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperDevice {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $branch_id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Branch|null $branch
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Employee> $employees
 * @property-read int|null $employees_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Position> $positions
 * @property-read int|null $positions_count
 * @method static \Database\Factories\DivisionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Division withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperDivision {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Education newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Education newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Education query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Education whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Education whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Education whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Education whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperEducation {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int|null $parent_id
 * @property int $company_id
 * @property int $branch_id
 * @property int|null $division_id
 * @property int|null $position_id
 * @property int|null $shift_id
 * @property int|null $province_id
 * @property int|null $city_id
 * @property int|null $district_id
 * @property int|null $village_id
 * @property string|null $postal_code
 * @property string|null $address_detail
 * @property string $employee_number
 * @property string $full_name
 * @property string $phone
 * @property string|null $bank_account_number
 * @property string|null $bank_name
 * @property string|null $npwp
 * @property string $nik
 * @property \App\Enums\MaritalStatus $marital_status
 * @property \App\Enums\BloodType|null $blood_type
 * @property \App\Enums\Gender $gender
 * @property \App\Enums\EmployeeStatus $status
 * @property \Carbon\CarbonImmutable $birth_date
 * @property \Carbon\CarbonImmutable $join_date
 * @property \App\Enums\EmploymentType $employment_type
 * @property \Carbon\CarbonImmutable|null $contract_start_date
 * @property \Carbon\CarbonImmutable|null $contract_end_date
 * @property \Carbon\CarbonImmutable|null $resign_date
 * @property \Carbon\CarbonImmutable|null $deceased_date
 * @property \App\Enums\TerminationType|null $termination_type
 * @property string|null $termination_reason
 * @property string|null $phk_variant
 * @property string|null $pin
 * @property int|null $golongan_ptkp_id
 * @property int|null $tarif_ter_id
 * @property int|null $kategori_ter_id
 * @property string|null $kode_karyawan
 * @property string|null $tanggal_masuk
 * @property string $status_karyawan
 * @property string|null $nip Nomor Induk Pegawai untuk laporan pajak
 * @property string|null $bank_account_name Nama sesuai rekening bank
 * @property string|null $ptkp_status Status PTKP: TK/0, K/0, K/1, K/2, K/3
 * @property string|null $payslip_password
 * @property string|null $payslip_password_set_at
 * @property numeric|null $basic_salary Basic salary for payroll calc
 * @property string|null $bank_account_holder
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string|null $emergency_contact_relation
 * @property string|null $bpjs_kesehatan
 * @property string|null $bpjs_ketenagakerjaan
 * @property string|null $photo
 * @property \App\Enums\EducationLevel $education_level
 * @property string $institution_name
 * @property string|null $major
 * @property int $graduation_year
 * @property \App\Enums\SalaryType $salary_type
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \Carbon\CarbonImmutable|null $probation_ends_at
 * @property \Carbon\CarbonImmutable|null $contract_ends_at
 * @property \Carbon\CarbonImmutable|null $resignation_submitted_at
 * @property \Carbon\CarbonImmutable|null $resigned_at
 * @property string|null $resignation_reason
 * @property \Carbon\CarbonImmutable|null $exit_interview_completed_at
 * @property \Carbon\CarbonImmutable|null $account_auto_disable_at
 * @property \App\Enums\EmploymentStatus $employment_status
 * @property \Carbon\CarbonImmutable|null $account_deletion_requested_at
 * @property string|null $account_deletion_reason
 * @property \Carbon\CarbonImmutable|null $account_deletion_reviewed_at
 * @property int|null $account_deletion_reviewed_by
 * @property string|null $account_deletion_review_notes
 * @property int|null $manager_id
 * @property string|null $provinsi_kode
 * @property string|null $kabupaten_kode
 * @property string|null $kecamatan_kode
 * @property string|null $kelurahan_kode
 * @property string|null $birth_place
 * @property-read \App\Models\User|null $accountDeletionReviewer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Approval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attendance> $attendances
 * @property-read int|null $attendances_count
 * @property-read \App\Models\Branch $branch
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FamilyDetail> $children
 * @property-read int|null $children_count
 * @property-read \App\Models\Wilayah|null $city
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LeaveBalance> $currentYearLeaveBalances
 * @property-read int|null $current_year_leave_balances_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Device> $devices
 * @property-read int|null $devices_count
 * @property-read Employee|null $directManager
 * @property-read \App\Models\Wilayah|null $district
 * @property-read \App\Models\Division|null $division
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EmployeeDocumentRequest> $documentRequests
 * @property-read int|null $document_requests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FaceDescriptor> $faceDescriptors
 * @property-read int|null $face_descriptors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FamilyDetail> $families
 * @property-read int|null $families_count
 * @property-read string|null $email
 * @property-read string|null $name
 * @property-read \App\Models\GolonganPtkp|null $golonganPtkp
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AssetHandover> $handovers
 * @property-read int|null $handovers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HrChecklistCase> $hrChecklistCases
 * @property-read int|null $hr_checklist_cases_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LeaveBalance> $leaveBalances
 * @property-read int|null $leave_balances_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Leave> $leaves
 * @property-read int|null $leaves_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Loan> $loans
 * @property-read int|null $loans_count
 * @property-read Employee|null $manager
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Overtime> $overtimes
 * @property-read int|null $overtimes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payroll> $payrolls
 * @property-read int|null $payrolls_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PerformanceReview> $performanceReviews
 * @property-read int|null $performance_reviews_count
 * @property-read \App\Models\Position|null $position
 * @property-read \App\Models\Wilayah|null $province
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reimbursement> $reimbursements
 * @property-read int|null $reimbursements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PerformanceReview> $reviewedVersions
 * @property-read int|null $reviewed_versions_count
 * @property-read \App\Models\Shift|null $shift
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftSchedule> $shiftSchedules
 * @property-read int|null $shift_schedules_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Employee> $subordinates
 * @property-read int|null $subordinates_count
 * @property-read \App\Models\User|null $updater
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Wilayah|null $village
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee active()
 * @method static \Database\Factories\EmployeeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee nearestNeighbors(string $column, ?mixed $value, \Pgvector\Laravel\Distance $distance)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee orWhereBlind(string $column, string $indexName, array|string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAccountAutoDisableAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAccountDeletionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAccountDeletionRequestedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAccountDeletionReviewNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAccountDeletionReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAccountDeletionReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAddressDetail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBankAccountHolder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBankAccountName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBankAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBankName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBasicSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBirthDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBirthPlace($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBlind(string $column, string $indexName, array|string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBloodType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBpjsKesehatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBpjsKetenagakerjaan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereContractEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereContractEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereContractStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDeceasedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDistrictId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDivisionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEducationLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmergencyContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmergencyContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmergencyContactRelation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmployeeNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmploymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmploymentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereExitInterviewCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereGolonganPtkpId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereGraduationYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereInstitutionName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereJoinDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereKabupatenKode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereKategoriTerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereKecamatanKode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereKelurahanKode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereKodeKaryawan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereMajor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereMaritalStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereNik($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereNip($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereNpwp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePayslipPassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePayslipPasswordSetAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePhkVariant($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePositionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereProbationEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereProvinceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereProvinsiKode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePtkpStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereResignDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereResignationReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereResignationSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereResignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereSalaryType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereStatusKaryawan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereTanggalMasuk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereTarifTerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereTerminationReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereTerminationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereVillageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperEmployee {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int $document_type_id
 * @property int|null $requested_by
 * @property string $request_source
 * @property string $purpose
 * @property string|null $details
 * @property \Carbon\CarbonImmutable|null $due_date
 * @property string $status
 * @property string|null $uploaded_path
 * @property string|null $uploaded_original_name
 * @property \Carbon\CarbonImmutable|null $uploaded_at
 * @property string|null $generated_path
 * @property int|null $generated_template_id
 * @property \Carbon\CarbonImmutable|null $generated_at
 * @property array<array-key, mixed>|null $metadata
 * @property int|null $reviewed_by
 * @property \Carbon\CarbonImmutable|null $reviewed_at
 * @property string|null $fulfillment_note
 * @property string|null $rejection_note
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\EmployeeDocumentType $documentType
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\EmployeeDocumentTemplate|null $generatedTemplate
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\User|null $requester
 * @property-read \App\Models\User|null $reviewer
 * @method static \Database\Factories\EmployeeDocumentRequestFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereDocumentTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereFulfillmentNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereGeneratedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereGeneratedPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereGeneratedTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest wherePurpose($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereRejectionNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereRequestSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereRequestedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereUploadedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereUploadedOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest whereUploadedPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentRequest withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	final class IdeHelperEmployeeDocumentRequest {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $document_type_id
 * @property string $name
 * @property string|null $content
 * @property array<array-key, mixed>|null $variables
 * @property string|null $paper_size
 * @property string|null $orientation
 * @property string|null $header
 * @property string|null $footer
 * @property array<array-key, mixed>|null $layout_options
 * @property string|null $file_path
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\EmployeeDocumentType $documentType
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EmployeeDocumentRequest> $documents
 * @property-read int|null $documents_count
 * @method static \Database\Factories\EmployeeDocumentTemplateFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentTemplate whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentTemplate whereDocumentTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentTemplate whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentTemplate whereFooter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentTemplate whereHeader($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentTemplate whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentTemplate whereLayoutOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentTemplate whereOrientation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentTemplate wherePaperSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentTemplate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentTemplate whereVariables($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperEmployeeDocumentTemplate {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_required
 * @property string|null $icon
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string|null $code
 * @property int|null $retention_days
 * @property string|null $category
 * @property bool $requires_employee_upload
 * @property bool $auto_generate_enabled
 * @property bool $is_active
 * @property bool $admin_requestable
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EmployeeDocumentRequest> $documents
 * @property-read int|null $documents_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EmployeeDocumentTemplate> $templates
 * @property-read int|null $templates_count
 * @method static \Database\Factories\EmployeeDocumentTypeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentType whereAdminRequestable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentType whereAutoGenerateEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentType whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentType whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentType whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentType whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentType whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentType whereIsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentType whereRequiresEmployeeUpload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentType whereRetentionDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentType whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocumentType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperEmployeeDocumentType {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string $type
 * @property string|null $location
 * @property \Carbon\CarbonImmutable $start_at
 * @property \Carbon\CarbonImmutable|null $end_at
 * @property string $timezone
 * @property string|null $color
 * @property bool $is_all_day
 * @property bool $is_active
 * @property int|null $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\User|null $creator
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereIsAllDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperEvent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property \Pgvector\Laravel\Vector $embedding
 * @property bool $is_active
 * @property array<array-key, mixed>|null $metadata
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @method static \Database\Factories\FaceDescriptorFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaceDescriptor nearestNeighbors(string $column, ?mixed $value, \Pgvector\Laravel\Distance $distance)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaceDescriptor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaceDescriptor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaceDescriptor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaceDescriptor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaceDescriptor whereEmbedding($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaceDescriptor whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaceDescriptor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaceDescriptor whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaceDescriptor whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FaceDescriptor whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperFaceDescriptor {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string $name
 * @property \App\Enums\FamilyRelationship $relationship
 * @property \App\Enums\Gender|null $gender
 * @property string|null $nik
 * @property \Carbon\CarbonImmutable|null $birth_date
 * @property string|null $job
 * @property string|null $phone
 * @property string|null $address
 * @property bool $is_emergency
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @method static \Database\Factories\FamilyDetailFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail orWhereBlind(string $column, string $indexName, array|string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereBirthDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereBlind(string $column, string $indexName, array|string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereIsEmergency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereJob($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereNik($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereRelationship($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyDetail whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperFamilyDetail {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property numeric $latitude
 * @property numeric $longitude
 * @property numeric|null $accuracy
 * @property string|null $device_info
 * @property string $logged_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeolocationLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeolocationLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeolocationLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeolocationLog whereAccuracy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeolocationLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeolocationLog whereDeviceInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeolocationLog whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeolocationLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeolocationLog whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeolocationLog whereLoggedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeolocationLog whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GeolocationLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperGeolocationLog {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $kode Kode PTKP, e.g. TK/0, K/1, K/2, K/3
 * @property string $nama Nama golongan PTKP
 * @property numeric $ptkp_tahun Nilai PTKP per tahun (Rp)
 * @property numeric $ptkp_bulan Nilai PTKP per bulan (Rp)
 * @property int|null $kategori_ter_id
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Employee> $employees
 * @property-read int|null $employees_count
 * @property-read \App\Models\KategoriTer|null $kategoriTer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GolonganPtkp newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GolonganPtkp newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GolonganPtkp query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GolonganPtkp whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GolonganPtkp whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GolonganPtkp whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GolonganPtkp whereKategoriTerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GolonganPtkp whereKode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GolonganPtkp whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GolonganPtkp wherePtkpBulan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GolonganPtkp wherePtkpTahun($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GolonganPtkp whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperGolonganPtkp {}
}

namespace App\Models{
/**
 * @property int $id
 * @property \Carbon\CarbonImmutable $date
 * @property string $name
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property bool $is_recurring
 * @method static \Database\Factories\HolidayFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereIsRecurring($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Holiday whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperHoliday {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $template_id
 * @property int $user_id
 * @property string $type
 * @property string $status
 * @property \Carbon\CarbonImmutable|null $effective_date
 * @property int|null $started_by
 * @property \Carbon\CarbonImmutable|null $completed_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User|null $starter
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HrChecklistTask> $tasks
 * @property-read int|null $tasks_count
 * @property-read \App\Models\HrChecklistTemplate|null $template
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistCase newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistCase newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistCase query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistCase whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistCase whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistCase whereEffectiveDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistCase whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistCase whereStartedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistCase whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistCase whereTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistCase whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistCase whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistCase whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperHrChecklistCase {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $case_id
 * @property int|null $template_item_id
 * @property int|null $depends_on_task_id
 * @property int|null $assigned_to
 * @property string $title
 * @property string|null $description
 * @property string|null $category
 * @property \Carbon\CarbonImmutable|null $due_date
 * @property string $status
 * @property int|null $completed_by
 * @property \Carbon\CarbonImmutable|null $completed_at
 * @property string|null $notes
 * @property string|null $attachment_path
 * @property string|null $attachment_original_name
 * @property \Carbon\CarbonImmutable|null $attachment_uploaded_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User|null $assignee
 * @property-read \App\Models\HrChecklistCase $case
 * @property-read \App\Models\User|null $completer
 * @property-read HrChecklistTask|null $dependency
 * @property-read \App\Models\HrChecklistTemplateItem|null $templateItem
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask reminderReady()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask whereAttachmentOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask whereAttachmentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask whereAttachmentUploadedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask whereCaseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask whereCompletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask whereDependsOnTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask whereTemplateItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTask whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperHrChecklistTask {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $type
 * @property string $name
 * @property string|null $description
 * @property int|null $division_id
 * @property int|null $job_title_id
 * @property bool $is_active
 * @property int|null $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HrChecklistCase> $cases
 * @property-read int|null $cases_count
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\Division|null $division
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HrChecklistTemplateItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\JobTitle|null $jobTitle
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplate whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplate whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplate whereDivisionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplate whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplate whereJobTitleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplate whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplate whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperHrChecklistTemplate {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $template_id
 * @property string $title
 * @property string|null $description
 * @property string|null $category
 * @property string|null $default_assignee_type
 * @property int $due_offset_days
 * @property bool $is_required
 * @property int $sort_order
 * @property array<array-key, mixed>|null $metadata
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\HrChecklistTemplate $template
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplateItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplateItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplateItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplateItem whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplateItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplateItem whereDefaultAssigneeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplateItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplateItem whereDueOffsetDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplateItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplateItem whereIsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplateItem whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplateItem whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplateItem whereTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplateItem whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HrChecklistTemplateItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperHrChecklistTemplateItem {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $resource
 * @property string $operation
 * @property string $status
 * @property int|null $requested_by_user_id
 * @property string|null $queue
 * @property string|null $source_disk
 * @property string|null $source_path
 * @property string|null $source_name
 * @property string|null $file_disk
 * @property string|null $file_path
 * @property string|null $file_name
 * @property string|null $mime_type
 * @property int|null $size_bytes
 * @property float $progress_percentage
 * @property int $processed_rows
 * @property int $total_rows
 * @property array<array-key, mixed>|null $meta
 * @property string|null $error_message
 * @property \Carbon\CarbonImmutable|null $started_at
 * @property \Carbon\CarbonImmutable|null $completed_at
 * @property \Carbon\CarbonImmutable|null $failed_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User|null $requestedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereFailedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereFileDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereOperation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereProcessedRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereProgressPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereQueue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereRequestedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereResource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereSizeBytes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereSourceDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereSourceName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereSourcePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereTotalRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportExportRun whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperImportExportRun {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string|null $file_path
 * @property string $status
 * @property int $total_rows
 * @property int $processed_rows
 * @property int $failed_rows
 * @property string|null $errors
 * @property \Carbon\CarbonImmutable|null $completed_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportProgress newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportProgress newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportProgress query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportProgress whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportProgress whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportProgress whereErrors($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportProgress whereFailedRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportProgress whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportProgress whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportProgress whereProcessedRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportProgress whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportProgress whereTotalRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportProgress whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportProgress whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportProgress whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperImportProgress {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $integration_client_id
 * @property string $event_type
 * @property string $employee_code
 * @property \Carbon\CarbonImmutable $occurred_at
 * @property string $status
 * @property string|null $error_message
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string|null $source
 * @property string|null $idempotency_key
 * @property int|null $user_id
 * @property int|null $attendance_id
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property string|null $device_id
 * @property array<array-key, mixed>|null $normalized_payload
 * @property array<array-key, mixed>|null $raw_payload
 * @property \Carbon\CarbonImmutable|null $processed_at
 * @property-read \App\Models\Attendance|null $attendance
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent whereAttendanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent whereEmployeeCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent whereEventType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent whereIdempotencyKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent whereIntegrationClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent whereNormalizedPayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent whereOccurredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent whereRawPayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationAttendanceEvent whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperIntegrationAttendanceEvent {}
}

namespace App\Models{
/**
 * @property string $id
 * @property int $company_id
 * @property string $code
 * @property string $name
 * @property string|null $contact_name
 * @property string|null $contact_email
 * @property string $api_key_hash
 * @property string $secret_encrypted
 * @property array<array-key, mixed>|null $abilities
 * @property array<array-key, mixed>|null $allowed_sources
 * @property array<array-key, mixed>|null $allowed_ips
 * @property \Carbon\CarbonImmutable|null $last_used_at
 * @property string|null $last_used_ip
 * @property \Carbon\CarbonImmutable|null $expires_at
 * @property \Carbon\CarbonImmutable|null $revoked_at
 * @property int|null $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string|null $deleted_at
 * @property-read \App\Models\User|null $creator
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient whereAbilities($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient whereAllowedIps($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient whereAllowedSources($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient whereApiKeyHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient whereContactEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient whereContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient whereLastUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient whereLastUsedIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient whereRevokedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient whereSecretEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationClient whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperIntegrationClient {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $integration_endpoint_id
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string|null $event_key
 * @property array<array-key, mixed>|null $payload
 * @property string $status
 * @property int $attempts
 * @property int|null $response_status
 * @property string|null $response_body
 * @property string|null $signature
 * @property \Carbon\CarbonImmutable|null $dispatched_at
 * @property \Carbon\CarbonImmutable|null $failed_at
 * @property-read \App\Models\IntegrationEndpoint $endpoint
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationDelivery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationDelivery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationDelivery query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationDelivery whereAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationDelivery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationDelivery whereDispatchedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationDelivery whereEventKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationDelivery whereFailedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationDelivery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationDelivery whereIntegrationEndpointId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationDelivery wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationDelivery whereResponseBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationDelivery whereResponseStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationDelivery whereSignature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationDelivery whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationDelivery whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperIntegrationDelivery {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $client_id
 * @property string $name
 * @property array<array-key, mixed> $event_keys
 * @property string $url
 * @property string|null $secret
 * @property array<array-key, mixed>|null $headers
 * @property bool $active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\IntegrationDelivery> $deliveries
 * @property-read int|null $deliveries_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationEndpoint newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationEndpoint newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationEndpoint query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationEndpoint whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationEndpoint whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationEndpoint whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationEndpoint whereEventKeys($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationEndpoint whereHeaders($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationEndpoint whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationEndpoint whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationEndpoint whereSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationEndpoint whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IntegrationEndpoint whereUrl($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperIntegrationEndpoint {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int|null $project_id
 * @property string|null $number
 * @property string $status
 * @property numeric $grand_total
 * @property \Carbon\CarbonImmutable|null $issued_at
 * @property \Carbon\CarbonImmutable|null $due_at
 * @property \Carbon\CarbonImmutable|null $paid_at
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereDueAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereGrandTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereIssuedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invoice withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperInvoice {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $rank
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JobTitle> $jobTitles
 * @property-read int|null $job_titles_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobLevel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobLevel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobLevel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobLevel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobLevel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobLevel whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobLevel whereRank($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobLevel whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperJobLevel {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $level
 * @property string|null $description
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property int|null $job_level_id
 * @property int|null $division_id
 * @property-read \App\Models\Division|null $division
 * @property-read \App\Models\JobLevel|null $jobLevel
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTitle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTitle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTitle query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTitle whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTitle whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTitle whereDivisionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTitle whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTitle whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTitle whereJobLevelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTitle whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTitle whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTitle whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperJobTitle {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $kode
 * @property string $nama
 * @property string|null $deskripsi
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GolonganPtkp> $golonganPtkp
 * @property-read int|null $golongan_ptkp_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TarifTer> $tarifTer
 * @property-read int|null $tarif_ter_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KategoriTer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KategoriTer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KategoriTer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KategoriTer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KategoriTer whereDeskripsi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KategoriTer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KategoriTer whereKode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KategoriTer whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KategoriTer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperKategoriTer {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $knowledgeable_type
 * @property int $knowledgeable_id
 * @property string $title
 * @property string $content
 * @property \App\Models\KnowledgeBaseCategory|null $category
 * @property array<array-key, mixed>|null $embedding
 * @property \App\Enums\KnowledgeBaseStatus $status
 * @property string|null $source_document
 * @property int|null $page_number
 * @property int|null $category_id
 * @property string|null $summary
 * @property string|null $source_type
 * @property string|null $file_type
 * @property int|null $file_size
 * @property string|null $original_filename
 * @property int $chunk_count
 * @property bool $is_indexed
 * @property \Carbon\CarbonImmutable|null $indexed_at
 * @property array<array-key, mixed>|null $metadata
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\KnowledgeBaseChunk> $chunks
 * @property-read int|null $chunks_count
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $knowledgeable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase byCategory(string $category)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase completed()
 * @method static \Database\Factories\KnowledgeBaseFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase indexed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase processing()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereChunkCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereEmbedding($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereFileType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereIndexedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereIsIndexed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereKnowledgeableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereKnowledgeableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereOriginalFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase wherePageNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereSourceDocument($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereSourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBase whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperKnowledgeBase {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int|null $parent_id
 * @property int $sort_order
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, KnowledgeBaseCategory> $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\KnowledgeBase> $knowledgeBases
 * @property-read int|null $knowledge_bases_count
 * @property-read KnowledgeBaseCategory|null $parent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory root()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseCategory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperKnowledgeBaseCategory {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $knowledge_base_id
 * @property string $chunk_text
 * @property int $chunk_index
 * @property string|null $embedding_model
 * @property int|null $token_count
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\KnowledgeBase $knowledgeBase
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseChunk newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseChunk newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseChunk ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseChunk query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseChunk whereChunkIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseChunk whereChunkText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseChunk whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseChunk whereEmbeddingModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseChunk whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseChunk whereKnowledgeBaseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseChunk whereTokenCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeBaseChunk whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperKnowledgeBaseChunk {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property float $weight
 * @property int $sort_order
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property bool $is_active
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\KpiTemplate> $activeKpiTemplates
 * @property-read int|null $active_kpi_templates_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\KpiTemplate> $kpiTemplates
 * @property-read int|null $kpi_templates_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiGroup whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiGroup whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiGroup whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiGroup whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiGroup whereWeight($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperKpiGroup {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $department_type
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property int|null $kpi_group_id
 * @property string|null $indicator_description
 * @property float $weight
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AppraisalEvaluation> $evaluations
 * @property-read int|null $evaluations_count
 * @property-read \App\Models\KpiGroup|null $kpiGroup
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiTemplate whereDepartmentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiTemplate whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiTemplate whereIndicatorDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiTemplate whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiTemplate whereKpiGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiTemplate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KpiTemplate whereWeight($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperKpiTemplate {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int $leave_type_id
 * @property \Carbon\CarbonImmutable $start_date
 * @property \Carbon\CarbonImmutable $end_date
 * @property \App\Enums\DayType $day_type
 * @property numeric $total_days
 * @property string $reason
 * @property string|null $proof_file
 * @property string|null $rejection_reason
 * @property \App\Enums\RequestStatus $status
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Approval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\LeaveType $leaveType
 * @method static \Database\Factories\LeaveFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereDayType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereLeaveTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereProofFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereTotalDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leave withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperLeave {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int $leave_type_id
 * @property int $year
 * @property numeric $quota
 * @property numeric $used
 * @property numeric $carry_forward
 * @property \Carbon\CarbonImmutable|null $carry_forward_deadline
 * @property int|null $entitlement_id
 * @property numeric $carried_forward
 * @property string|null $expired_at
 * @property bool $is_frozen
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\LeaveType $leaveType
 * @method static \Database\Factories\LeaveBalanceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereCarriedForward($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereCarryForward($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereCarryForwardDeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereEntitlementId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereExpiredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereIsFrozen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereLeaveTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereQuota($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveBalance whereYear($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperLeaveBalance {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $leave_type_id
 * @property int $employee_id
 * @property numeric $total_days
 * @property numeric $used_days
 * @property numeric $remaining_days
 * @property int $year
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LeaveBalance> $leaveBalances
 * @property-read int|null $leave_balances_count
 * @property-read \App\Models\LeaveType $leaveType
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveEntitlement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveEntitlement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveEntitlement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveEntitlement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveEntitlement whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveEntitlement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveEntitlement whereLeaveTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveEntitlement whereRemainingDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveEntitlement whereTotalDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveEntitlement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveEntitlement whereUsedDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveEntitlement whereYear($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperLeaveEntitlement {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property int $quota
 * @property bool $is_paid
 * @property bool $deducts_from_quota
 * @property bool $eligible_for_carry_forward
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string $category
 * @property string|null $description
 * @property bool $counts_against_quota
 * @property bool $requires_attachment
 * @property bool $is_system
 * @property int $sort_order
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LeaveBalance> $leaveBalances
 * @property-read int|null $leave_balances_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Leave> $leaves
 * @property-read int|null $leaves_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType active()
 * @method static \Database\Factories\LeaveTypeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereCountsAgainstQuota($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereDeductsFromQuota($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereEligibleForCarryForward($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereIsPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereIsSystem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereQuota($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereRequiresAttachment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperLeaveType {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string|null $rejection_reason
 * @property int|null $created_by
 * @property numeric $amount
 * @property numeric $interest_rate
 * @property int $tenor_months
 * @property numeric $monthly_installment
 * @property \App\Enums\LoanStatus $status
 * @property bool $is_settled
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Approval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\Employee|null $employee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LoanInstallment> $installments
 * @property-read int|null $installments_count
 * @method static \Database\Factories\LoanFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereInterestRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereIsSettled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereMonthlyInstallment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereTenorMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Loan withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperLoan {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $loan_id
 * @property int|null $payroll_id
 * @property numeric $amount_paid
 * @property int $installment_number
 * @property \App\Enums\LoanInstallmentStatus $status
 * @property \Carbon\CarbonImmutable $due_date
 * @property \Carbon\CarbonImmutable|null $paid_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Loan|null $loan
 * @property-read \App\Models\Payroll|null $payroll
 * @method static \Database\Factories\LoanInstallmentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereAmountPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereInstallmentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereLoanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment wherePayrollId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanInstallment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperLoanInstallment {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property int $host_id
 * @property \Carbon\CarbonImmutable $start_time
 * @property \Carbon\CarbonImmutable $end_time
 * @property string $meeting_url
 * @property string $platform
 * @property string|null $meeting_id
 * @property string|null $password
 * @property string $status
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property int|null $company_id
 * @property int|null $chat_thread_id
 * @property array<array-key, mixed>|null $metadata
 * @property-read \App\Models\Company|null $company
 * @property-read \App\Models\User|null $host
 * @property-read \App\Models\ChatThread|null $thread
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting whereChatThreadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting whereHostId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting whereMeetingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting whereMeetingUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting wherePlatform($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OnlineMeeting withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperOnlineMeeting {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int|null $attendance_id
 * @property \Carbon\CarbonImmutable $date
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $description
 * @property numeric|null $total_hours
 * @property numeric|null $amount
 * @property numeric $insentif
 * @property int|null $approved_by
 * @property string|null $approved_at
 * @property string|null $notes
 * @property string|null $rejection_reason
 * @property \App\Enums\RequestStatus $status
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Approval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \App\Models\Attendance|null $attendance
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\User|null $user
 * @method static \Database\Factories\OvertimeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereAttendanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereInsentif($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereTotalHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Overtime withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperOvertime {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property string $period
 * @property numeric $basic_salary
 * @property numeric $total_allowance
 * @property numeric $gross_salary
 * @property numeric $overtime_pay
 * @property numeric $pph21
 * @property numeric $bpjs_health
 * @property numeric $bpjs_employment
 * @property numeric $loan_deduction
 * @property numeric $attendance_penalty
 * @property numeric $total_deduction
 * @property numeric $net_salary
 * @property \Carbon\CarbonImmutable|null $payment_date
 * @property string $payment_method
 * @property \App\Enums\PayrollStatus $status
 * @property string|null $pdf_path
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string|null $rejection_reason
 * @property string|null $pdf_emailed_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PayrollAdjustment> $adjustments
 * @property-read int|null $adjustments_count
 * @property-read \App\Models\Employee|null $employee
 * @property-read array $allowances
 * @property-read array $deductions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PayrollItem> $items
 * @property-read int|null $items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LoanInstallment> $loanInstallments
 * @property-read int|null $loan_installments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reimbursement> $reimbursements
 * @property-read int|null $reimbursements_count
 * @method static \Database\Factories\PayrollFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereAttendancePenalty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereBasicSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereBpjsEmployment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereBpjsHealth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereGrossSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereLoanDeduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereNetSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereOvertimePay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll wherePaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll wherePdfEmailedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll wherePdfPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll wherePeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll wherePph21($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereTotalAllowance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereTotalDeduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payroll withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPayroll {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $payroll_id
 * @property numeric $amount
 * @property string $reason
 * @property int|null $created_by
 * @property \Carbon\CarbonImmutable $applied_to_period
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\Payroll|null $payroll
 * @method static \Database\Factories\PayrollAdjustmentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment whereAppliedToPeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment wherePayrollId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAdjustment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPayrollAdjustment {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $payroll_id
 * @property int $employee_id
 * @property numeric $basic_salary
 * @property numeric $total_allowance
 * @property numeric $overtime_pay
 * @property numeric $gross_salary
 * @property numeric $ptkp
 * @property numeric $pph21_ter
 * @property numeric $pph21_netto
 * @property numeric $pph21_rate
 * @property numeric $bpjs_health
 * @property numeric $bpjs_employment
 * @property numeric $loan_deduction
 * @property numeric $attendance_penalty
 * @property numeric $total_deduction
 * @property numeric $net_salary
 * @property array<array-key, mixed>|null $meta Raw input dari sub-service (TunjanganService, LemburService, dll)
 * @property string|null $calculated_by User ID yang trigger kalkulasi
 * @property \Carbon\CarbonImmutable $calculated_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Payroll|null $payroll
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit whereAttendancePenalty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit whereBasicSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit whereBpjsEmployment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit whereBpjsHealth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit whereCalculatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit whereCalculatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit whereGrossSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit whereLoanDeduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit whereNetSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit whereOvertimePay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit wherePayrollId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit wherePph21Netto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit wherePph21Rate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit wherePph21Ter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit wherePtkp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit whereTotalAllowance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit whereTotalDeduction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollAudit whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPayrollAudit {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $type
 * @property string|null $description
 * @property bool $is_taxable
 * @property bool $is_bpjs_applicable
 * @property bool $is_active
 * @property numeric|null $percentage
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property numeric|null $amount
 * @property string $calculation_type
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollComponent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollComponent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollComponent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollComponent whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollComponent whereCalculationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollComponent whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollComponent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollComponent whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollComponent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollComponent whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollComponent whereIsBpjsApplicable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollComponent whereIsTaxable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollComponent whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollComponent wherePercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollComponent whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollComponent whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPayrollComponent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $payroll_id
 * @property string $name
 * @property numeric $amount
 * @property \App\Enums\PayrollItemType $type
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Payroll|null $payroll
 * @method static \Database\Factories\PayrollItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem wherePayrollId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPayrollItem {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int $reviewer_id
 * @property string $status
 * @property \Carbon\CarbonImmutable|null $review_date
 * @property string|null $period
 * @property numeric|null $final_score
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Employee|null $reviewer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereFinalScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview wherePeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereReviewDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereReviewerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PerformanceReview withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPerformanceReview {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $division_id
 * @property string $name
 * @property string $code
 * @property int|null $grade
 * @property numeric|null $basic_salary
 * @property numeric $allowance_jabatan
 * @property bool $is_active
 * @property int|null $job_title_id
 * @property numeric|null $min_salary
 * @property numeric|null $max_salary
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Division|null $division
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Employee> $employees
 * @property-read int|null $employees_count
 * @property-read \App\Models\JobTitle|null $jobTitle
 * @method static \Database\Factories\PositionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereAllowanceJabatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereBasicSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereDivisionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereGrade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereJobTitleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereMaxSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereMinSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPosition {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $sku
 * @property string $status
 * @property bool $stock_tracking
 * @property int $stock_quantity
 * @property int $reorder_point
 * @property numeric|null $price
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Company $company
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereReorderPoint($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereStockQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereStockTracking($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProduct {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int|null $client_id
 * @property int|null $branch_id
 * @property int|null $manager_id
 * @property string $name
 * @property string|null $code
 * @property string $status
 * @property \Carbon\CarbonImmutable|null $starts_at
 * @property \Carbon\CarbonImmutable|null $ends_at
 * @property string|null $description
 * @property array<array-key, mixed>|null $metadata
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\CompanyBranch|null $branch
 * @property-read \App\Models\Client|null $client
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\User|null $manager
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectTask> $tasks
 * @property-read int|null $tasks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectVisitEvidence> $visitEvidences
 * @property-read int|null $visit_evidences_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProject {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $project_id
 * @property int|null $company_id
 * @property int|null $assigned_to
 * @property string $title
 * @property string $status
 * @property string $priority
 * @property \Carbon\CarbonImmutable|null $due_date
 * @property string|null $description
 * @property array<array-key, mixed>|null $metadata
 * @property \Carbon\CarbonImmutable|null $completed_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User|null $assignee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectTaskChecklistItem> $checklistItems
 * @property-read int|null $checklist_items_count
 * @property-read \App\Models\Company|null $company
 * @property-read \App\Models\Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectVisitEvidence> $visitEvidences
 * @property-read int|null $visit_evidences_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTask whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProjectTask {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $project_task_id
 * @property string $title
 * @property bool $is_done
 * @property int $sort_order
 * @property \Carbon\CarbonImmutable|null $completed_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\ProjectTask $task
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskChecklistItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskChecklistItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskChecklistItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskChecklistItem whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskChecklistItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskChecklistItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskChecklistItem whereIsDone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskChecklistItem whereProjectTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskChecklistItem whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskChecklistItem whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectTaskChecklistItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProjectTaskChecklistItem {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $log_name
 * @property string|null $event
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property array<array-key, mixed>|null $attributes
 * @property array<array-key, mixed>|null $old_attributes
 * @property string|null $tags
 * @property string|null $performed_by_type
 * @property int|null $performed_by_id
 * @property string|null $batch_uuid
 * @property int|null $user_id
 * @property string|null $evidence_url
 * @property string|null $evidence_type
 * @property string|null $evidence_filename
 * @property int|null $evidence_size
 * @property array<array-key, mixed>|null $evidence_metadata
 * @property string|null $evidence_hash
 * @property int|null $project_id
 * @property string|null $evidence_description
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property int|null $project_task_id
 * @property int|null $company_id
 * @property \Carbon\CarbonImmutable|null $visited_at
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property int|null $accuracy_meters
 * @property string|null $address
 * @property string|null $notes
 * @property string|null $photo_disk
 * @property string|null $photo_path
 * @property string|null $photo_original_name
 * @property array<array-key, mixed>|null $metadata
 * @property-read \App\Models\Project|null $project
 * @property-read \App\Models\ProjectTask|null $task
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereAccuracyMeters($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereAttributes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereBatchUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereEvidenceDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereEvidenceFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereEvidenceHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereEvidenceMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereEvidenceSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereEvidenceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereEvidenceUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereLogName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereOldAttributes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence wherePerformedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence wherePerformedByType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence wherePhotoDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence wherePhotoOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence wherePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereProjectTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereSubjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereSubjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectVisitEvidence whereVisitedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperProjectVisitEvidence {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int|null $payroll_id
 * @property int|null $category_id
 * @property string $title
 * @property \Carbon\CarbonImmutable $expense_date
 * @property numeric $amount
 * @property string|null $description
 * @property string|null $receipt_file
 * @property string|null $attachment_path
 * @property string|null $rejection_reason
 * @property \App\Enums\ReimbursementStatus $status
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \Carbon\CarbonImmutable|null $approved_at
 * @property int|null $approved_by
 * @property int|null $head_approved_by
 * @property \Carbon\CarbonImmutable|null $head_approved_at
 * @property int|null $finance_approved_by
 * @property \Carbon\CarbonImmutable|null $finance_approved_at
 * @property int|null $approval_matrix_rule_id
 * @property array<array-key, mixed>|null $approval_steps
 * @property string|null $approval_current_step
 * @property array<array-key, mixed>|null $approval_completed_steps
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Approval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \App\Models\User|null $approvedBy
 * @property-read \App\Models\ReimbursementCategory|null $category
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\User|null $financeApprover
 * @property-read \App\Models\User|null $headApprover
 * @property-read \App\Models\Payroll|null $payroll
 * @property-read \App\Models\User|null $user
 * @method static \Database\Factories\ReimbursementFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereApprovalCompletedSteps($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereApprovalCurrentStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereApprovalMatrixRuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereApprovalSteps($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereAttachmentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereExpenseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereFinanceApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereFinanceApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereHeadApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereHeadApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement wherePayrollId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereReceiptFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Reimbursement withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperReimbursement {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $company_id
 * @property string $name
 * @property string $code
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Company|null $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reimbursement> $reimbursements
 * @property-read int|null $reimbursements_count
 * @method static \Database\Factories\ReimbursementCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementCategory whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementCategory whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementCategory whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReimbursementCategory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperReimbursementCategory {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string|null $slug
 * @property array<array-key, mixed>|null $permission_keys
 * @property bool $is_super_admin
 * @property string|null $description
 * @property bool $is_system
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role permission($permissions, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereGuardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereIsSuperAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereIsSystem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role wherePermissionKeys($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role withoutPermission($permissions)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperRole {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int|null $project_id
 * @property string $name
 * @property string $stage
 * @property numeric $expected_value
 * @property numeric $probability
 * @property \Carbon\CarbonImmutable|null $expected_close_at
 * @property \Carbon\CarbonImmutable|null $follow_up_at
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOpportunity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOpportunity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOpportunity onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOpportunity query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOpportunity whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOpportunity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOpportunity whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOpportunity whereExpectedCloseAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOpportunity whereExpectedValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOpportunity whereFollowUpAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOpportunity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOpportunity whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOpportunity whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOpportunity whereProbability($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOpportunity whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOpportunity whereStage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOpportunity whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOpportunity withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesOpportunity withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperSalesOpportunity {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int|null $shift_id
 * @property \Carbon\CarbonImmutable $date
 * @property bool $is_off
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Shift|null $shift
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Schedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Schedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Schedule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Schedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Schedule whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Schedule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Schedule whereIsOff($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Schedule whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Schedule whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Schedule whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperSchedule {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $company
 * @property string|null $phone
 * @property string $status
 * @property string|null $notes
 * @property int|null $reviewed_by
 * @property \Carbon\CarbonImmutable|null $reviewed_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\User|null $reviewer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelfRegistration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelfRegistration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelfRegistration onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelfRegistration query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelfRegistration whereCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelfRegistration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelfRegistration whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelfRegistration whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelfRegistration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelfRegistration whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelfRegistration whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelfRegistration wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelfRegistration whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelfRegistration whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelfRegistration whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelfRegistration whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelfRegistration withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SelfRegistration withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperSelfRegistration {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property string $group
 * @property string $type
 * @property string|null $description
 * @property bool $is_public
 * @property string|null $validation_rules
 * @property string|null $options
 * @property int $group_index
 * @property bool $face_enrollment_required
 * @property bool $face_verification_required
 * @property string|null $enterprise_license_key
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereEnterpriseLicenseKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereFaceEnrollmentRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereFaceVerificationRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereGroupIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereIsPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereValidationRules($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Setting whereValue($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperSetting {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $start_time
 * @property string $end_time
 * @property int $late_tolerance_minutes
 * @property bool $is_active
 * @property string $schedule_type
 * @property string|null $shift_pattern
 * @property int $break_minutes
 * @property bool $is_flexible
 * @property string|null $flexible_start
 * @property string|null $flexible_end
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Attendance> $attendances
 * @property-read int|null $attendances_count
 * @property-read mixed $duration
 * @property-read mixed $duration_label
 * @property-read mixed $formatted_end_time
 * @property-read mixed $formatted_start_time
 * @property-read bool $is_overnight
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftSchedule> $shiftSchedules
 * @property-read int|null $shift_schedules_count
 * @method static \Database\Factories\ShiftFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereBreakMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereFlexibleEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereFlexibleStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereIsFlexible($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereLateToleranceMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereScheduleType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereShiftPattern($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperShift {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $employee_id
 * @property int $shift_id
 * @property \Carbon\CarbonImmutable $date
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Shift|null $shift
 * @method static \Database\Factories\ShiftScheduleFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSchedule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSchedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSchedule whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSchedule whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSchedule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSchedule whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSchedule whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperShiftSchedule {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $requester_id
 * @property int $target_id
 * @property int $requested_shift_id
 * @property \Carbon\CarbonImmutable $schedule_date
 * @property string $status
 * @property int|null $reviewed_by
 * @property \Carbon\CarbonImmutable|null $reviewed_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property int|null $user_id
 * @property int|null $schedule_id
 * @property int|null $current_shift_id
 * @property int|null $replacement_user_id
 * @property string|null $reason
 * @property string|null $rejection_note
 * @property-read \App\Models\Shift|null $currentShift
 * @property-read \App\Models\User|null $replacementUser
 * @property-read \App\Models\Shift|null $requestedShift
 * @property-read \App\Models\Employee|null $requester
 * @property-read \App\Models\User|null $reviewer
 * @property-read \App\Models\Schedule|null $schedule
 * @property-read \App\Models\Employee|null $target
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSwapRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSwapRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSwapRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSwapRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSwapRequest whereCurrentShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSwapRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSwapRequest whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSwapRequest whereRejectionNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSwapRequest whereReplacementUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSwapRequest whereRequestedShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSwapRequest whereRequesterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSwapRequest whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSwapRequest whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSwapRequest whereScheduleDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSwapRequest whereScheduleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSwapRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSwapRequest whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSwapRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftSwapRequest whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperShiftSwapRequest {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $address
 * @property string|null $city
 * @property string|null $province
 * @property string|null $postal_code
 * @property string|null $phone
 * @property string|null $email
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property int $radius_meters
 * @property bool $is_active
 * @property array<array-key, mixed>|null $settings
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site orWhereBlind(string $column, string $indexName, array|string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereBlind(string $column, string $indexName, array|string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site wherePostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereProvince($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereRadiusMeters($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Site whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperSite {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $type
 * @property string $status
 * @property int|null $requested_by_user_id
 * @property string|null $queue
 * @property string|null $file_disk
 * @property string|null $file_path
 * @property string|null $file_name
 * @property int|null $size_bytes
 * @property string|null $error_message
 * @property array<array-key, mixed>|null $meta
 * @property \Carbon\CarbonImmutable|null $started_at
 * @property \Carbon\CarbonImmutable|null $completed_at
 * @property \Carbon\CarbonImmutable|null $failed_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User|null $requestedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemBackupRun newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemBackupRun newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemBackupRun query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemBackupRun whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemBackupRun whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemBackupRun whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemBackupRun whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemBackupRun whereFailedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemBackupRun whereFileDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemBackupRun whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemBackupRun whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemBackupRun whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemBackupRun whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemBackupRun whereQueue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemBackupRun whereRequestedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemBackupRun whereSizeBytes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemBackupRun whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemBackupRun whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemBackupRun whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemBackupRun whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperSystemBackupRun {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $kategori_ter_id
 * @property numeric $batas_bawah
 * @property numeric|null $batas_atas
 * @property numeric $tarif
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\KategoriTer $kategoriTer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TarifTer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TarifTer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TarifTer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TarifTer whereBatasAtas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TarifTer whereBatasBawah($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TarifTer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TarifTer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TarifTer whereKategoriTerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TarifTer whereTarif($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TarifTer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperTarifTer {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property string $type
 * @property string|null $provider
 * @property string|null $location
 * @property \Carbon\CarbonImmutable|null $start_date
 * @property \Carbon\CarbonImmutable|null $end_date
 * @property int|null $duration_minutes
 * @property string $status
 * @property bool $is_mandatory
 * @property int|null $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\User|null $creator
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereDurationMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereIsMandatory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Training withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperTraining {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Carbon\CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $google_id
 * @property \Carbon\CarbonImmutable|null $password_changed_at
 * @property string|null $last_activity_at
 * @property string|null $profile_photo_path
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property int|null $manager_id
 * @property int|null $company_id
 * @property string|null $remember_token
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property string $group
 * @property string|null $email_verification_code_hash
 * @property \Carbon\CarbonImmutable|null $email_verification_code_expires_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityLog> $activityLogs
 * @property-read int|null $activity_logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CashAdvance> $cashAdvances
 * @property-read int|null $cash_advances_count
 * @property-read \App\Models\Company|null $company
 * @property-read \App\Models\Division|null $division
 * @property-read \App\Models\Employee|null $employee
 * @property-read string|null $account_deletion_reason
 * @property-read mixed|null $account_deletion_requested_at
 * @property-read string|null $account_deletion_review_notes
 * @property-read string|null $address
 * @property-read mixed|null $birth_date
 * @property-read string|null $birth_place
 * @property-read mixed $direct_manager
 * @property-read int|null $division_id
 * @property-read mixed $education
 * @property-read string|null $employment_status
 * @property-read string|null $gender
 * @property-read mixed|null $hourly_rate
 * @property-read bool $is_admin
 * @property-read bool $is_demo
 * @property-read bool $is_not_admin
 * @property-read bool $is_superadmin
 * @property-read bool $is_user
 * @property-read mixed $job_title
 * @property-read mixed $kabupaten
 * @property-read string|null $kabupaten_kode
 * @property-read mixed $kecamatan
 * @property-read string|null $kecamatan_kode
 * @property-read mixed $kelurahan
 * @property-read string|null $kelurahan_kode
 * @property-read string|null $nip
 * @property-read string|null $phone
 * @property-read mixed $provinsi
 * @property-read string|null $provinsi_kode
 * @property-read mixed $reviewed_account_deletion_by
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserNotificationPreference> $notificationPreferences
 * @property-read int|null $notification_preferences_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read string $profile_photo_url
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read User|null $supervisor
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User managedBy(\App\Models\User $admin)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role(string $slug)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerificationCodeExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerificationCodeHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereGoogleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastActivityAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePasswordChangedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereProfilePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUser {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $event_key
 * @property array<array-key, mixed> $channels
 * @property bool $digest_enabled
 * @property string $digest_frequency
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property array<array-key, mixed>|null $external_routes
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereChannels($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereDigestEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereDigestFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereEventKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereExternalRoutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserNotificationPreference whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUserNotificationPreference {}
}

namespace App\Models{
/**
 * @property string $kode
 * @property string $nama
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wilayah newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wilayah newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wilayah query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wilayah whereKode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wilayah whereNama($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperWilayah {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property \Carbon\CarbonImmutable $start_date
 * @property \Carbon\CarbonImmutable $end_date
 * @property string $reason
 * @property string $status
 * @property int|null $approved_by
 * @property \Carbon\CarbonImmutable|null $approved_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property int|null $company_id
 * @property \Carbon\CarbonImmutable|null $date
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $location_address
 * @property int|null $reviewed_by
 * @property \Carbon\CarbonImmutable|null $reviewed_at
 * @property string|null $review_note
 * @property string|null $rejection_reason
 * @property array<array-key, mixed>|null $metadata
 * @property-read \App\Models\User|null $approver
 * @property-read \App\Models\User|null $employee
 * @property-read \App\Models\User|null $reviewer
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest orWhereBlind(string $column, string $indexName, array|string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereBlind(string $column, string $indexName, array|string $value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereLocationAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereReviewNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkFromHomeRequest whereUserId($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperWorkFromHomeRequest {}
}

