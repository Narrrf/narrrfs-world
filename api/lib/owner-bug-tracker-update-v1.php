<?php
declare(strict_types=1);

/**
 * Domain failure for one guarded Bug Tracker mutation.
 *
 * Plain language:
 * The service reports a stable HTTP/error pair without exposing SQLite errors
 * or internal file paths to the Owner Control browser.
 */
final class NarrrfsOwnerBugTrackerUpdateException
    extends RuntimeException
{
    public function __construct(
        private readonly int $httpStatus,
        private readonly string $publicCode
    ) {
        parent::__construct($publicCode);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    public function publicCode(): string
    {
        return $this->publicCode;
    }
}


/**
 * Read one required bounded text field.
 *
 * Plain language:
 * Editable bug text must exist, remain a string, and stay within a deliberate
 * server-side size bound before a database transaction may use it.
 */
function narrrfs_owner_bug_update_required_text(
    array $input,
    string $key,
    int $maximumBytes
): string {
    $value = $input[$key] ?? null;

    if (
        !is_string($value)
        || trim($value) === ''
        || strlen($value) > $maximumBytes
    ) {
        throw new NarrrfsOwnerBugTrackerUpdateException(
            400,
            'invalid_bug_update'
        );
    }

    return $value;
}


/**
 * Read one optional bounded text field and normalize empty text to NULL.
 */
function narrrfs_owner_bug_update_optional_text(
    array $input,
    string $key,
    int $maximumBytes
): ?string {
    if (
        !array_key_exists($key, $input)
        || $input[$key] === null
    ) {
        return null;
    }

    if (!is_string($input[$key])) {
        throw new NarrrfsOwnerBugTrackerUpdateException(
            400,
            'invalid_bug_update'
        );
    }

    $value = trim($input[$key]);

    if ($value === '') {
        return null;
    }

    if (strlen($value) > $maximumBytes) {
        throw new NarrrfsOwnerBugTrackerUpdateException(
            400,
            'invalid_bug_update'
        );
    }

    return $value;
}


/**
 * Read one required positive integer field.
 */
function narrrfs_owner_bug_update_positive_integer(
    array $input,
    string $key
): int {
    $value = $input[$key] ?? null;

    if (!is_int($value) || $value < 1) {
        throw new NarrrfsOwnerBugTrackerUpdateException(
            400,
            'invalid_bug_update'
        );
    }

    return $value;
}


/**
 * Execute one prepared SELECT and return its first row or NULL.
 */
function narrrfs_owner_bug_update_fetch_one(
    SQLite3Stmt $statement
): ?array {
    $result = $statement->execute();

    if (!$result instanceof SQLite3Result) {
        throw new RuntimeException(
            'Bug Tracker database read failed.'
        );
    }

    $row = $result->fetchArray(SQLITE3_ASSOC);

    $result->finalize();

    return is_array($row)
        ? $row
        : null;
}


/**
 * Update exactly one Bug Tracker report inside one SQLite transaction.
 *
 * Plain language:
 * Category, priority and status are verified before the report changes.
 * Status and assignment audit records are committed together with the report.
 * Any failure rolls the complete operation back.
 *
 * @return array<string, mixed>
 */
function narrrfs_owner_bug_tracker_update(
    SQLite3 $db,
    int $bugId,
    array $input,
    string $ownerSubject
): array {
    if (
        $bugId < 1
        || !preg_match('/^\d{17,20}$/', $ownerSubject)
    ) {
        throw new NarrrfsOwnerBugTrackerUpdateException(
            400,
            'invalid_bug_update'
        );
    }

    $title = narrrfs_owner_bug_update_required_text(
        $input,
        'title',
        500
    );

    $description =
        narrrfs_owner_bug_update_required_text(
            $input,
            'description',
            20000
        );

    $categoryId =
        narrrfs_owner_bug_update_positive_integer(
            $input,
            'category_id'
        );

    $priorityId =
        narrrfs_owner_bug_update_positive_integer(
            $input,
            'priority_id'
        );

    $statusId =
        narrrfs_owner_bug_update_positive_integer(
            $input,
            'status_id'
        );

    $assignedTo =
        narrrfs_owner_bug_update_optional_text(
            $input,
            'assigned_to',
            100
        );

    $resolutionNotes =
        narrrfs_owner_bug_update_optional_text(
            $input,
            'resolution_notes',
            10000
        );

    $changeReason =
        narrrfs_owner_bug_update_optional_text(
            $input,
            'change_reason',
            500
        ) ?? 'Owner Control Bug Tracker update';

    if (!$db->exec('PRAGMA foreign_keys = ON')) {
        throw new RuntimeException(
            'Unable to enable SQLite foreign keys.'
        );
    }

    if (!$db->exec('BEGIN IMMEDIATE')) {
        throw new RuntimeException(
            'Unable to begin Bug Tracker transaction.'
        );
    }

    $transactionOpen = true;

    try {
        $bugStatement = $db->prepare(
            '
            SELECT
                bug.bug_id,
                bug.status_id,
                bug.assigned_to,
                bug.resolved_at,
                status.is_resolved AS old_is_resolved
            FROM tbl_bug_reports AS bug
            LEFT JOIN tbl_bug_statuses AS status
                ON status.status_id = bug.status_id
            WHERE bug.bug_id = :bug_id
            LIMIT 1
            '
        );

        if (!$bugStatement instanceof SQLite3Stmt) {
            throw new RuntimeException(
                'Unable to prepare Bug Tracker lookup.'
            );
        }

        $bugStatement->bindValue(
            ':bug_id',
            $bugId,
            SQLITE3_INTEGER
        );

        $existingBug =
            narrrfs_owner_bug_update_fetch_one(
                $bugStatement
            );

        if ($existingBug === null) {
            throw new NarrrfsOwnerBugTrackerUpdateException(
                404,
                'bug_not_found'
            );
        }


        $categoryStatement = $db->prepare(
            '
            SELECT category_id
            FROM tbl_bug_categories
            WHERE category_id = :category_id
            LIMIT 1
            '
        );

        if (!$categoryStatement instanceof SQLite3Stmt) {
            throw new RuntimeException(
                'Unable to prepare category lookup.'
            );
        }

        $categoryStatement->bindValue(
            ':category_id',
            $categoryId,
            SQLITE3_INTEGER
        );

        if (
            narrrfs_owner_bug_update_fetch_one(
                $categoryStatement
            ) === null
        ) {
            throw new NarrrfsOwnerBugTrackerUpdateException(
                400,
                'invalid_category'
            );
        }


        $priorityStatement = $db->prepare(
            '
            SELECT priority_id
            FROM tbl_bug_priorities
            WHERE priority_id = :priority_id
            LIMIT 1
            '
        );

        if (!$priorityStatement instanceof SQLite3Stmt) {
            throw new RuntimeException(
                'Unable to prepare priority lookup.'
            );
        }

        $priorityStatement->bindValue(
            ':priority_id',
            $priorityId,
            SQLITE3_INTEGER
        );

        if (
            narrrfs_owner_bug_update_fetch_one(
                $priorityStatement
            ) === null
        ) {
            throw new NarrrfsOwnerBugTrackerUpdateException(
                400,
                'invalid_priority'
            );
        }


        $statusStatement = $db->prepare(
            '
            SELECT
                status_id,
                is_active,
                is_resolved
            FROM tbl_bug_statuses
            WHERE status_id = :status_id
            LIMIT 1
            '
        );

        if (!$statusStatement instanceof SQLite3Stmt) {
            throw new RuntimeException(
                'Unable to prepare status lookup.'
            );
        }

        $statusStatement->bindValue(
            ':status_id',
            $statusId,
            SQLITE3_INTEGER
        );

        $newStatus =
            narrrfs_owner_bug_update_fetch_one(
                $statusStatement
            );

        if (
            $newStatus === null
            || (int)$newStatus['is_active'] !== 1
        ) {
            throw new NarrrfsOwnerBugTrackerUpdateException(
                400,
                'invalid_status'
            );
        }

        $newIsResolved =
            (int)$newStatus['is_resolved'] === 1
                ? 1
                : 0;


        $updateStatement = $db->prepare(
            '
            UPDATE tbl_bug_reports
            SET
                title = :title,
                description = :description,
                category_id = :category_id,
                priority_id = :priority_id,
                status_id = :status_id,
                assigned_to = :assigned_to,
                resolution_notes = :resolution_notes,
                resolved_at = CASE
                    WHEN :is_resolved = 1
                    THEN CASE
                        WHEN resolved_at IS NULL
                             OR TRIM(resolved_at) = \'\'
                        THEN CURRENT_TIMESTAMP
                        ELSE resolved_at
                    END
                    ELSE NULL
                END,
                updated_at = CURRENT_TIMESTAMP
            WHERE bug_id = :bug_id
            '
        );

        if (!$updateStatement instanceof SQLite3Stmt) {
            throw new RuntimeException(
                'Unable to prepare Bug Tracker update.'
            );
        }

        $updateStatement->bindValue(
            ':title',
            $title,
            SQLITE3_TEXT
        );

        $updateStatement->bindValue(
            ':description',
            $description,
            SQLITE3_TEXT
        );

        $updateStatement->bindValue(
            ':category_id',
            $categoryId,
            SQLITE3_INTEGER
        );

        $updateStatement->bindValue(
            ':priority_id',
            $priorityId,
            SQLITE3_INTEGER
        );

        $updateStatement->bindValue(
            ':status_id',
            $statusId,
            SQLITE3_INTEGER
        );

        $updateStatement->bindValue(
            ':assigned_to',
            $assignedTo,
            $assignedTo === null
                ? SQLITE3_NULL
                : SQLITE3_TEXT
        );

        $updateStatement->bindValue(
            ':resolution_notes',
            $resolutionNotes,
            $resolutionNotes === null
                ? SQLITE3_NULL
                : SQLITE3_TEXT
        );

        $updateStatement->bindValue(
            ':is_resolved',
            $newIsResolved,
            SQLITE3_INTEGER
        );

        $updateStatement->bindValue(
            ':bug_id',
            $bugId,
            SQLITE3_INTEGER
        );

        $updateResult =
            $updateStatement->execute();

        if (!$updateResult instanceof SQLite3Result) {
            throw new RuntimeException(
                'Bug Tracker update failed.'
            );
        }

        $updateResult->finalize();

        if ($db->changes() !== 1) {
            throw new RuntimeException(
                'Bug Tracker update changed an unexpected row count.'
            );
        }


        $oldStatusId =
            (int)$existingBug['status_id'];

        $statusHistoryWritten = false;

        if ($oldStatusId !== $statusId) {
            $historyStatement = $db->prepare(
                '
                INSERT INTO tbl_bug_status_history (
                    bug_id,
                    old_status_id,
                    new_status_id,
                    changed_by,
                    changed_at,
                    change_reason
                )
                VALUES (
                    :bug_id,
                    :old_status_id,
                    :new_status_id,
                    :changed_by,
                    CURRENT_TIMESTAMP,
                    :change_reason
                )
                '
            );

            if (!$historyStatement instanceof SQLite3Stmt) {
                throw new RuntimeException(
                    'Unable to prepare status audit.'
                );
            }

            $historyStatement->bindValue(
                ':bug_id',
                $bugId,
                SQLITE3_INTEGER
            );

            $historyStatement->bindValue(
                ':old_status_id',
                $oldStatusId,
                SQLITE3_INTEGER
            );

            $historyStatement->bindValue(
                ':new_status_id',
                $statusId,
                SQLITE3_INTEGER
            );

            $historyStatement->bindValue(
                ':changed_by',
                $ownerSubject,
                SQLITE3_TEXT
            );

            $historyStatement->bindValue(
                ':change_reason',
                $changeReason,
                SQLITE3_TEXT
            );

            $historyResult =
                $historyStatement->execute();

            if (!$historyResult instanceof SQLite3Result) {
                throw new RuntimeException(
                    'Status audit write failed.'
                );
            }

            $historyResult->finalize();

            if ($db->changes() !== 1) {
                throw new RuntimeException(
                    'Status audit changed an unexpected row count.'
                );
            }

            $statusHistoryWritten = true;
        }


        $oldAssignedTo =
            trim(
                (string)(
                    $existingBug['assigned_to']
                    ?? ''
                )
            );

        $oldAssignedTo =
            $oldAssignedTo === ''
                ? null
                : $oldAssignedTo;

        $assignmentChanged =
            $oldAssignedTo !== $assignedTo;

        if ($assignmentChanged) {
            if ($oldAssignedTo !== null) {
                $closeAssignmentStatement =
                    $db->prepare(
                        '
                        UPDATE tbl_bug_assignments
                        SET unassigned_at = CURRENT_TIMESTAMP
                        WHERE bug_id = :bug_id
                          AND assigned_to = :assigned_to
                          AND unassigned_at IS NULL
                        '
                    );

                if (
                    !$closeAssignmentStatement
                    instanceof SQLite3Stmt
                ) {
                    throw new RuntimeException(
                        'Unable to prepare assignment closure.'
                    );
                }

                $closeAssignmentStatement->bindValue(
                    ':bug_id',
                    $bugId,
                    SQLITE3_INTEGER
                );

                $closeAssignmentStatement->bindValue(
                    ':assigned_to',
                    $oldAssignedTo,
                    SQLITE3_TEXT
                );

                $closeResult =
                    $closeAssignmentStatement->execute();

                if (!$closeResult instanceof SQLite3Result) {
                    throw new RuntimeException(
                        'Assignment closure failed.'
                    );
                }

                $closeResult->finalize();
            }

            if ($assignedTo !== null) {
                $assignmentStatement =
                    $db->prepare(
                        '
                        INSERT INTO tbl_bug_assignments (
                            bug_id,
                            assigned_to,
                            assigned_by,
                            assigned_at,
                            assignment_notes
                        )
                        VALUES (
                            :bug_id,
                            :assigned_to,
                            :assigned_by,
                            CURRENT_TIMESTAMP,
                            :assignment_notes
                        )
                        '
                    );

                if (
                    !$assignmentStatement
                    instanceof SQLite3Stmt
                ) {
                    throw new RuntimeException(
                        'Unable to prepare assignment audit.'
                    );
                }

                $assignmentStatement->bindValue(
                    ':bug_id',
                    $bugId,
                    SQLITE3_INTEGER
                );

                $assignmentStatement->bindValue(
                    ':assigned_to',
                    $assignedTo,
                    SQLITE3_TEXT
                );

                $assignmentStatement->bindValue(
                    ':assigned_by',
                    $ownerSubject,
                    SQLITE3_TEXT
                );

                $assignmentStatement->bindValue(
                    ':assignment_notes',
                    'Owner Control Bug Tracker assignment update',
                    SQLITE3_TEXT
                );

                $assignmentResult =
                    $assignmentStatement->execute();

                if (!$assignmentResult instanceof SQLite3Result) {
                    throw new RuntimeException(
                        'Assignment audit write failed.'
                    );
                }

                $assignmentResult->finalize();

                if ($db->changes() !== 1) {
                    throw new RuntimeException(
                        'Assignment audit changed an unexpected row count.'
                    );
                }
            }
        }


        $finalStatement = $db->prepare(
            '
            SELECT
                bug_id,
                title,
                description,
                category_id,
                priority_id,
                status_id,
                assigned_to,
                resolution_notes,
                created_at,
                updated_at,
                resolved_at
            FROM tbl_bug_reports
            WHERE bug_id = :bug_id
            LIMIT 1
            '
        );

        if (!$finalStatement instanceof SQLite3Stmt) {
            throw new RuntimeException(
                'Unable to prepare final Bug Tracker read.'
            );
        }

        $finalStatement->bindValue(
            ':bug_id',
            $bugId,
            SQLITE3_INTEGER
        );

        $finalBug =
            narrrfs_owner_bug_update_fetch_one(
                $finalStatement
            );

        if ($finalBug === null) {
            throw new RuntimeException(
                'Updated Bug Tracker row disappeared.'
            );
        }


        if (!$db->exec('COMMIT')) {
            throw new RuntimeException(
                'Unable to commit Bug Tracker update.'
            );
        }

        $transactionOpen = false;

        return [
            'bug' => $finalBug,
            'status_history_written' =>
                $statusHistoryWritten,
            'assignment_changed' =>
                $assignmentChanged,
        ];

    } catch (Throwable $error) {
        if ($transactionOpen) {
            $db->exec('ROLLBACK');
        }

        throw $error;
    }
}
