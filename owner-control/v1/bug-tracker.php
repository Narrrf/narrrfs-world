<?php
declare(strict_types=1);

// 🐛 Narrrfs World Owner Control V2 — Bug Tracker UI.
//
// DEVS FOR DECADES:
// This page is presentation only. It reads through the dedicated
// owner-authorized Bug Tracker read endpoint. It contains no database
// connection and provides no mutation controls.

define('NARRRFS_SESSION_READ_ONLY', true);

require_once __DIR__ . '/../../api/config/admin-authorization.php';

narrrfs_require_admin_request(
    'owner_control.bug_tracker.ui.v1'
);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Bug Tracker · Narrrfs Owner Control</title>

    <style>
        :root {
            color-scheme: dark;
            font-family:
                Inter,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;

            background: #0d0f12;
            color: #f5f7fa;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: #0d0f12;
        }

        main {
            width: min(1280px, calc(100% - 2rem));
            margin: 0 auto;
            padding: 2rem 0 4rem;
        }

        a {
            color: inherit;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .back-link {
            color: #aeb6c2;
            text-decoration: none;
        }

        .back-link:hover {
            color: #fff;
        }

        h1 {
            margin: .75rem 0 .4rem;
            font-size: clamp(2rem, 5vw, 3.5rem);
        }

        .lede {
            margin: 0;
            color: #aeb6c2;
        }

        .summary {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .summary-card,
        .panel {
            border: 1px solid #292f38;
            border-radius: 16px;
            background: #14181e;
        }

        .summary-card {
            padding: 1rem;
        }

        .summary-label {
            color: #929cab;
            font-size: .8rem;
        }

        .summary-value {
            margin-top: .35rem;
            font-size: 2rem;
            font-weight: 800;
        }

        .panel {
            margin-top: 1.5rem;
            overflow: hidden;
        }

        .toolbar {
            display: flex;
            gap: .75rem;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            padding: 1rem;
            border-bottom: 1px solid #292f38;
        }

        .toolbar-actions {
            display: flex;
            gap: .5rem;
            align-items: center;
        }

        button {
            border: 1px solid #37404c;
            border-radius: 9px;
            background: #222831;
            color: #fff;
            padding: .55rem .8rem;
            cursor: pointer;
        }

        button:hover:not(:disabled) {
            background: #2c3440;
        }

        button:disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        .status {
            color: #9ca6b3;
            font-size: .85rem;
        }

        .error {
            color: #ffaca5;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 980px;
        }

        th,
        td {
            padding: .75rem;
            border-bottom: 1px solid #252b33;
            text-align: left;
            vertical-align: top;
        }

        th {
            color: #8f9aaa;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        td {
            color: #d9dee5;
            font-size: .88rem;
        }

        .title {
            max-width: 420px;
            font-weight: 700;
        }

        .reporter {
            margin-top: .25rem;
            color: #84909f;
            font-size: .76rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .25rem .5rem;
            border-radius: 999px;
            background: #252b33;
            white-space: nowrap;
        }

        .empty {
            padding: 2rem;
            text-align: center;
            color: #8993a0;
        }

        .action-cell {
            white-space: nowrap;
        }

        .secondary-button {
            background: transparent;
        }

        dialog {
            width: min(980px, calc(100% - 2rem));
            max-height: calc(100vh - 2rem);
            padding: 0;
            border: 1px solid #37404c;
            border-radius: 16px;
            background: #14181e;
            color: #f5f7fa;
        }

        dialog::backdrop {
            background: rgba(0, 0, 0, .72);
        }

        .dialog-shell {
            display: grid;
            grid-template-rows: auto 1fr auto;
            max-height: calc(100vh - 2rem);
        }

        .dialog-header,
        .dialog-footer {
            padding: 1rem 1.25rem;
            border-color: #292f38;
        }

        .dialog-header {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            justify-content: space-between;
            border-bottom: 1px solid #292f38;
        }

        .dialog-header h2 {
            margin: 0;
        }

        .dialog-body {
            overflow-y: auto;
            padding: 1.25rem;
        }

        .dialog-footer {
            display: flex;
            gap: .75rem;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            border-top: 1px solid #292f38;
        }

        .form-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .field {
            display: grid;
            gap: .4rem;
        }

        .field-wide {
            grid-column: 1 / -1;
        }

        .field label {
            color: #aeb6c2;
            font-size: .8rem;
            font-weight: 700;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid #37404c;
            border-radius: 9px;
            background: #0f1318;
            color: #f5f7fa;
            padding: .7rem .75rem;
            font: inherit;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        .description-field {
            min-height: 180px;
        }

        .dialog-meta {
            margin: 0 0 1rem;
            color: #929cab;
            font-size: .82rem;
        }

        .discord-link {
            display: inline-flex;
            margin-top: .75rem;
            color: #8fc7ff;
        }

        .audit-grid {
            display: grid;
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
            gap: 1rem;
            margin-top: 1.25rem;
        }

        .audit-panel {
            min-width: 0;
            padding: 1rem;
            border: 1px solid #292f38;
            border-radius: 12px;
            background: #101419;
        }

        .audit-panel h3 {
            margin: 0 0 .75rem;
            font-size: 1rem;
        }

        .audit-list {
            display: grid;
            gap: .65rem;
        }

        .audit-item {
            padding-bottom: .65rem;
            border-bottom: 1px solid #252b33;
            color: #cbd2dc;
            font-size: .82rem;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .audit-item:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .mutation-gate {
            color: #f1c879;
            font-size: .8rem;
        }

        @media (max-width: 760px) {
            .form-grid,
            .audit-grid {
                grid-template-columns: 1fr;
            }

            .field-wide {
                grid-column: auto;
            }
        }
    </style>
</head>

<body>
<main>
    <div class="topbar">
        <a
            class="back-link"
            href="/owner-control/v1/index.php"
        >
            ← Owner Control
        </a>

        <div class="status" id="requestStatus">
            Loading…
        </div>
    </div>

    <h1>🐛 Bug Tracker</h1>

    <p class="lede">
        Owner-only Bug Tracker. Full report details can be reviewed in the
        edit workspace. Saving remains disabled until the separately reviewed
        mutation gate is enabled.
    </p>

    <section class="summary">
        <div class="summary-card">
            <div class="summary-label">Total reports</div>
            <div class="summary-value" id="totalCount">—</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">Open</div>
            <div class="summary-value" id="openCount">—</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">Resolved</div>
            <div class="summary-value" id="resolvedCount">—</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">Showing</div>
            <div class="summary-value" id="showingCount">—</div>
        </div>
    </section>

    <section class="panel">
        <div class="toolbar">
            <div class="status" id="pageStatus">
                Page data pending
            </div>

            <div class="toolbar-actions">
                <button
                    type="button"
                    id="previousButton"
                    disabled
                >
                    ← Previous
                </button>

                <button
                    type="button"
                    id="refreshButton"
                >
                    Refresh
                </button>

                <button
                    type="button"
                    id="nextButton"
                    disabled
                >
                    Next →
                </button>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title / Reporter</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody id="bugRows">
                    <tr>
                        <td colspan="8" class="empty">
                            Loading Bug Tracker…
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <dialog id="bugDialog">
        <div class="dialog-shell">
            <div class="dialog-header">
                <div>
                    <h2 id="bugDialogTitle">
                        Bug details
                    </h2>

                    <div
                        class="status"
                        id="bugDialogStatus"
                    >
                        Waiting
                    </div>
                </div>

                <button
                    type="button"
                    id="closeBugDialogButton"
                    class="secondary-button"
                >
                    Close
                </button>
            </div>

            <div class="dialog-body">
                <p
                    class="dialog-meta"
                    id="bugDialogMeta"
                >
                    Loading report…
                </p>

                <form
                    id="bugEditForm"
                    novalidate
                >
                    <div class="form-grid">
                        <div class="field field-wide">
                            <label for="bugTitleInput">
                                Title
                            </label>

                            <input
                                id="bugTitleInput"
                                name="title"
                                maxlength="500"
                                required
                            >
                        </div>

                        <div class="field field-wide">
                            <label for="bugDescriptionInput">
                                Description
                            </label>

                            <textarea
                                id="bugDescriptionInput"
                                name="description"
                                class="description-field"
                                maxlength="20000"
                                required
                            ></textarea>
                        </div>

                        <div class="field">
                            <label for="bugCategorySelect">
                                Category
                            </label>

                            <select
                                id="bugCategorySelect"
                                name="category_id"
                                required
                            ></select>
                        </div>

                        <div class="field">
                            <label for="bugPrioritySelect">
                                Priority
                            </label>

                            <select
                                id="bugPrioritySelect"
                                name="priority_id"
                                required
                            ></select>
                        </div>

                        <div class="field">
                            <label for="bugStatusSelect">
                                Status
                            </label>

                            <select
                                id="bugStatusSelect"
                                name="status_id"
                                required
                            ></select>
                        </div>

                        <div class="field">
                            <label for="bugAssignedInput">
                                Assigned To
                            </label>

                            <input
                                id="bugAssignedInput"
                                name="assigned_to"
                                maxlength="100"
                                placeholder="Unassigned"
                            >
                        </div>

                        <div class="field field-wide">
                            <label for="bugResolutionInput">
                                Resolution Notes
                            </label>

                            <textarea
                                id="bugResolutionInput"
                                name="resolution_notes"
                                maxlength="10000"
                            ></textarea>
                        </div>

                        <div class="field field-wide">
                            <label for="bugChangeReasonInput">
                                Change Reason
                            </label>

                            <input
                                id="bugChangeReasonInput"
                                name="change_reason"
                                maxlength="500"
                                value="Owner Control Bug Tracker update"
                            >
                        </div>
                    </div>
                </form>

                <a
                    id="bugDiscordLink"
                    class="discord-link"
                    href="#"
                    target="_blank"
                    rel="noopener noreferrer"
                    hidden
                >
                    Open original Discord report ↗
                </a>

                <div class="audit-grid">
                    <section class="audit-panel">
                        <h3>Status History</h3>

                        <div
                            id="bugStatusHistory"
                            class="audit-list"
                        ></div>
                    </section>

                    <section class="audit-panel">
                        <h3>Assignment History</h3>

                        <div
                            id="bugAssignmentHistory"
                            class="audit-list"
                        ></div>
                    </section>

                    <section class="audit-panel">
                        <h3>Comments</h3>

                        <div
                            id="bugComments"
                            class="audit-list"
                        ></div>
                    </section>
                </div>
            </div>

            <div class="dialog-footer">
                <div
                    class="mutation-gate"
                    id="mutationGateMessage"
                >
                    Save is disabled until the Bug Tracker mutation route
                    passes its separate local runtime gate.
                </div>

                <div class="toolbar-actions">
                    <button
                        type="button"
                        id="cancelBugButton"
                        class="secondary-button"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        id="saveBugButton"
                        disabled
                    >
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </dialog>
</main>

<script>
(() => {
    'use strict';

    const pageSize = 50;
    const discordGuildId = '1332015322546311218';

    // DEVS FOR DECADES:
    // This stays false until the separately reviewed mutation route is
    // explicitly enabled and runtime-verified. UI presence never grants write
    // authority.
    const bugTrackerMutationUiEnabled = true;

    let offset = 0;
    let loading = false;
    let detailsLoading = false;
    let lastPageData = null;
    let currentBugId = null;

    const requestStatus = document.getElementById(
        'requestStatus'
    );

    const pageStatus = document.getElementById(
        'pageStatus'
    );

    const rows = document.getElementById(
        'bugRows'
    );

    const previousButton = document.getElementById(
        'previousButton'
    );

    const nextButton = document.getElementById(
        'nextButton'
    );

    const refreshButton = document.getElementById(
        'refreshButton'
    );

    const totalCount = document.getElementById(
        'totalCount'
    );

    const openCount = document.getElementById(
        'openCount'
    );

    const resolvedCount = document.getElementById(
        'resolvedCount'
    );

    const showingCount = document.getElementById(
        'showingCount'
    );

    const bugDialog = document.getElementById(
        'bugDialog'
    );

    const bugDialogTitle = document.getElementById(
        'bugDialogTitle'
    );

    const bugDialogStatus = document.getElementById(
        'bugDialogStatus'
    );

    const bugDialogMeta = document.getElementById(
        'bugDialogMeta'
    );

    const bugEditForm = document.getElementById(
        'bugEditForm'
    );

    const closeBugDialogButton = document.getElementById(
        'closeBugDialogButton'
    );

    const cancelBugButton = document.getElementById(
        'cancelBugButton'
    );

    const saveBugButton = document.getElementById(
        'saveBugButton'
    );

    const mutationGateMessage = document.getElementById(
        'mutationGateMessage'
    );

    const bugTitleInput = document.getElementById(
        'bugTitleInput'
    );

    const bugDescriptionInput = document.getElementById(
        'bugDescriptionInput'
    );

    const bugCategorySelect = document.getElementById(
        'bugCategorySelect'
    );

    const bugPrioritySelect = document.getElementById(
        'bugPrioritySelect'
    );

    const bugStatusSelect = document.getElementById(
        'bugStatusSelect'
    );

    const bugAssignedInput = document.getElementById(
        'bugAssignedInput'
    );

    const bugResolutionInput = document.getElementById(
        'bugResolutionInput'
    );

    const bugChangeReasonInput = document.getElementById(
        'bugChangeReasonInput'
    );

    const bugDiscordLink = document.getElementById(
        'bugDiscordLink'
    );

    const bugStatusHistory = document.getElementById(
        'bugStatusHistory'
    );

    const bugAssignmentHistory = document.getElementById(
        'bugAssignmentHistory'
    );

    const bugComments = document.getElementById(
        'bugComments'
    );


    function safeColor(value) {
        return /^#[0-9a-fA-F]{6}$/.test(
            String(value || '')
        )
            ? String(value)
            : '#6B7280';
    }


    function lookupById(items, key, value) {
        return Array.isArray(items)
            ? items.find(
                item => Number(item[key]) === Number(value)
            ) || null
            : null;
    }


    function textCell(value, className = '') {
        const cell = document.createElement('td');

        if (className) {
            cell.className = className;
        }

        cell.textContent = value == null
            ? ''
            : String(value);

        return cell;
    }


    function badgeCell(record, nameKey) {
        const cell = document.createElement('td');
        const badge = document.createElement('span');

        badge.className = 'badge';

        if (!record) {
            badge.textContent = 'Unknown';
        } else {
            const icon = String(record.icon || '').trim();
            const name = String(record[nameKey] || 'Unknown');

            badge.textContent = icon
                ? `${icon} ${name}`
                : name;

            const color = safeColor(record.color_code);

            badge.style.color = color;
            badge.style.border = `1px solid ${color}`;
        }

        cell.appendChild(badge);

        return cell;
    }


    function titleCell(bug) {
        const cell = document.createElement('td');
        const title = document.createElement('div');
        const reporter = document.createElement('div');

        title.className = 'title';
        title.textContent = String(
            bug.title || '(untitled report)'
        );

        reporter.className = 'reporter';
        reporter.textContent = String(
            bug.discord_username || 'Unknown reporter'
        );

        cell.appendChild(title);
        cell.appendChild(reporter);

        return cell;
    }


    function formatDate(value) {
        if (!value) {
            return '—';
        }

        const date = new Date(value);

        return Number.isNaN(date.getTime())
            ? String(value)
            : date.toLocaleString();
    }


    function isDiscordSnowflake(value) {
        return /^\d{17,20}$/.test(
            String(value || '')
        );
    }


    function populateSelect(
        select,
        items,
        idKey,
        nameKey,
        selectedValue
    ) {
        select.replaceChildren();

        for (const item of Array.isArray(items) ? items : []) {
            const option = document.createElement('option');

            option.value = String(item[idKey]);
            option.textContent = String(
                item[nameKey] || `#${item[idKey]}`
            );

            option.selected =
                Number(item[idKey])
                === Number(selectedValue);

            select.appendChild(option);
        }
    }


    function renderAuditList(container, items, formatter) {
        container.replaceChildren();

        if (!Array.isArray(items) || items.length === 0) {
            const empty = document.createElement('div');

            empty.className = 'status';
            empty.textContent = 'No history recorded.';

            container.appendChild(empty);
            return;
        }

        for (const item of items) {
            const entry = document.createElement('div');

            entry.className = 'audit-item';
            entry.textContent = formatter(item);

            container.appendChild(entry);
        }
    }


    function configureDiscordLink(bug) {
        const channelId = String(
            bug.discord_channel_id || ''
        );

        const messageId = String(
            bug.discord_message_id || ''
        );

        if (
            !isDiscordSnowflake(discordGuildId)
            || !isDiscordSnowflake(channelId)
            || !isDiscordSnowflake(messageId)
        ) {
            bugDiscordLink.hidden = true;
            bugDiscordLink.removeAttribute('href');
            return;
        }

        bugDiscordLink.href =
            `https://discord.com/channels/${discordGuildId}`
            + `/${channelId}/${messageId}`;

        bugDiscordLink.hidden = false;
    }


    function setMutationUiState() {
        saveBugButton.disabled =
            bugTrackerMutationUiEnabled !== true
            || currentBugId === null
            || detailsLoading === true;

        mutationGateMessage.textContent =
            bugTrackerMutationUiEnabled === true
                ? 'Mutation route enabled for this UI.'
                : 'Save is disabled until the Bug Tracker mutation route '
                    + 'passes its separate local runtime gate.';
    }


    function resetDialog() {
        currentBugId = null;

        bugDialogTitle.textContent = 'Bug details';
        bugDialogStatus.className = 'status';
        bugDialogStatus.textContent = 'Waiting';
        bugDialogMeta.textContent = 'Loading report…';

        bugEditForm.reset();

        bugCategorySelect.replaceChildren();
        bugPrioritySelect.replaceChildren();
        bugStatusSelect.replaceChildren();

        bugDiscordLink.hidden = true;
        bugDiscordLink.removeAttribute('href');

        bugStatusHistory.replaceChildren();
        bugAssignmentHistory.replaceChildren();
        bugComments.replaceChildren();

        setMutationUiState();
    }


    async function loadBugDetails(bugId) {
        if (detailsLoading) {
            return;
        }

        detailsLoading = true;
        currentBugId = Number(bugId);

        setMutationUiState();

        bugDialogStatus.className = 'status';
        bugDialogStatus.textContent = 'Loading details…';

        try {
            const response = await fetch(
                '/api/owner-control/v1/bug-tracker-details.php?id='
                    + encodeURIComponent(String(bugId)),
                {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/json',
                    },
                }
            );

            const data = await response.json();

            if (!response.ok || data.success !== true) {
                throw new Error(
                    data.error || `HTTP ${response.status}`
                );
            }

            const bug = data.bug || {};

            currentBugId = Number(bug.bug_id);

            bugDialogTitle.textContent =
                `Bug #${currentBugId}`;

            bugDialogMeta.textContent =
                `Reporter: ${String(
                    bug.discord_username || 'Unknown'
                )}`
                + ` · Created: ${formatDate(bug.created_at)}`
                + ` · Updated: ${formatDate(bug.updated_at)}`
                + ` · Resolved: ${formatDate(bug.resolved_at)}`;

            bugTitleInput.value = String(
                bug.title || ''
            );

            bugDescriptionInput.value = String(
                bug.description || ''
            );

            bugAssignedInput.value = String(
                bug.assigned_to || ''
            );

            bugResolutionInput.value = String(
                bug.resolution_notes || ''
            );

            bugChangeReasonInput.value =
                'Owner Control Bug Tracker update';

            populateSelect(
                bugCategorySelect,
                lastPageData?.categories,
                'category_id',
                'category_name',
                bug.category_id
            );

            populateSelect(
                bugPrioritySelect,
                lastPageData?.priorities,
                'priority_id',
                'priority_name',
                bug.priority_id
            );

            populateSelect(
                bugStatusSelect,
                lastPageData?.statuses,
                'status_id',
                'status_name',
                bug.status_id
            );

            configureDiscordLink(bug);

            renderAuditList(
                bugStatusHistory,
                data.status_history,
                item => {
                    const from =
                        item.old_status_name
                        || 'Initial';

                    const to =
                        item.new_status_name
                        || `#${item.new_status_id}`;

                    const reason =
                        item.change_reason
                            ? ` · ${item.change_reason}`
                            : '';

                    return `${formatDate(item.changed_at)}`
                        + ` · ${from} → ${to}`
                        + ` · ${String(
                            item.changed_by || 'Unknown'
                        )}`
                        + reason;
                }
            );

            renderAuditList(
                bugAssignmentHistory,
                data.assignments,
                item => {
                    const closed =
                        item.unassigned_at
                            ? ` · ended ${formatDate(
                                item.unassigned_at
                            )}`
                            : '';

                    return `${formatDate(item.assigned_at)}`
                        + ` · ${String(
                            item.assigned_to || 'Unknown'
                        )}`
                        + ` · by ${String(
                            item.assigned_by || 'Unknown'
                        )}`
                        + closed;
                }
            );

            renderAuditList(
                bugComments,
                data.comments,
                item => {
                    return `${formatDate(item.created_at)}`
                        + ` · ${String(
                            item.discord_username || 'Unknown'
                        )}`
                        + ` · ${String(
                            item.comment_text || ''
                        )}`;
                }
            );

            bugDialogStatus.textContent =
                'Details loaded';

        } catch (error) {
            currentBugId = null;

            const message = error instanceof Error
                ? error.message
                : 'Unable to load bug details';

            bugDialogStatus.className =
                'status error';

            bugDialogStatus.textContent =
                message;

        } finally {
            detailsLoading = false;
            setMutationUiState();
        }
    }


    async function openBugDialog(bugId) {
        resetDialog();

        if (!bugDialog.open) {
            bugDialog.showModal();
        }

        await loadBugDetails(bugId);
    }


    async function saveCurrentBug() {
        if (
            bugTrackerMutationUiEnabled !== true
            || currentBugId === null
        ) {
            bugDialogStatus.className =
                'status error';

            bugDialogStatus.textContent =
                'Mutation route is intentionally disabled.';

            return;
        }

        if (!bugEditForm.reportValidity()) {
            return;
        }

        saveBugButton.disabled = true;

        bugDialogStatus.className = 'status';
        bugDialogStatus.textContent =
            'Preparing guarded update…';

        try {
            const csrfResponse = await fetch(
                '/api/owner-control/v1/csrf.php',
                {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/json',
                    },
                }
            );

            const csrfData =
                await csrfResponse.json();

            const csrfToken = String(
                csrfData.csrf_token || ''
            );

            if (
                !csrfResponse.ok
                || csrfData.success !== true
                || !/^[a-f0-9]{64}$/.test(csrfToken)
            ) {
                throw new Error(
                    csrfData.error
                    || `CSRF HTTP ${csrfResponse.status}`
                );
            }

            const payload = {
                bug_id: currentBugId,
                title: bugTitleInput.value,
                description:
                    bugDescriptionInput.value,
                category_id:
                    Number(bugCategorySelect.value),
                priority_id:
                    Number(bugPrioritySelect.value),
                status_id:
                    Number(bugStatusSelect.value),
                assigned_to:
                    bugAssignedInput.value.trim() || null,
                resolution_notes:
                    bugResolutionInput.value.trim() || null,
                change_reason:
                    bugChangeReasonInput.value.trim()
                    || 'Owner Control Bug Tracker update',
            };

            const response = await fetch(
                '/api/owner-control/v1/bug-tracker-update.php',
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken,
                    },
                    body: JSON.stringify(payload),
                }
            );

            const data = await response.json();

            if (!response.ok || data.success !== true) {
                throw new Error(
                    data.error || `HTTP ${response.status}`
                );
            }

            bugDialogStatus.textContent =
                'Changes saved.';

            await loadPage();
            await loadBugDetails(currentBugId);

        } catch (error) {
            const message = error instanceof Error
                ? error.message
                : 'Unable to update bug';

            bugDialogStatus.className =
                'status error';

            bugDialogStatus.textContent =
                message;

        } finally {
            setMutationUiState();
        }
    }


    function showMessage(message, isError = false) {
        rows.replaceChildren();

        const row = document.createElement('tr');
        const cell = document.createElement('td');

        cell.colSpan = 8;
        cell.className = isError
            ? 'empty error'
            : 'empty';

        cell.textContent = message;

        row.appendChild(cell);
        rows.appendChild(row);
    }


    function renderRows(data) {
        rows.replaceChildren();

        if (
            !Array.isArray(data.bugs)
            || data.bugs.length === 0
        ) {
            showMessage('No reports on this page.');
            return;
        }

        for (const bug of data.bugs) {
            const category = lookupById(
                data.categories,
                'category_id',
                bug.category_id
            );

            const priority = lookupById(
                data.priorities,
                'priority_id',
                bug.priority_id
            );

            const status = lookupById(
                data.statuses,
                'status_id',
                bug.status_id
            );

            const row = document.createElement('tr');

            row.appendChild(
                textCell(`#${bug.bug_id}`)
            );

            row.appendChild(
                titleCell(bug)
            );

            row.appendChild(
                badgeCell(
                    category,
                    'category_name'
                )
            );

            row.appendChild(
                badgeCell(
                    priority,
                    'priority_name'
                )
            );

            row.appendChild(
                badgeCell(
                    status,
                    'status_name'
                )
            );

            row.appendChild(
                textCell(
                    bug.assigned_to || 'Unassigned'
                )
            );

            row.appendChild(
                textCell(
                    formatDate(bug.updated_at)
                )
            );

            const actionCell =
                document.createElement('td');

            const actionButton =
                document.createElement('button');

            actionCell.className = 'action-cell';

            actionButton.type = 'button';
            actionButton.textContent = 'View / Edit';

            actionButton.addEventListener(
                'click',
                () => {
                    openBugDialog(
                        Number(bug.bug_id)
                    );
                }
            );

            actionCell.appendChild(actionButton);
            row.appendChild(actionCell);

            rows.appendChild(row);
        }
    }


    async function loadPage() {
        if (loading) {
            return;
        }

        loading = true;

        previousButton.disabled = true;
        nextButton.disabled = true;
        refreshButton.disabled = true;

        requestStatus.className = 'status';
        requestStatus.textContent = 'Loading…';

        try {
            const query = new URLSearchParams({
                limit: String(pageSize),
                offset: String(offset),
            });

            const response = await fetch(
                '/api/owner-control/v1/bug-tracker.php?'
                    + query.toString(),
                {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/json',
                    },
                }
            );

            const data = await response.json();

            if (!response.ok || data.success !== true) {
                if (
                    response.status === 401
                    && data.error === 'authentication_required'
                ) {
                    throw new Error(
                        'Owner session expired. Re-authenticate through local Discord OAuth.'
                    );
                }

                throw new Error(
                    data.error || `HTTP ${response.status}`
                );
            }

            totalCount.textContent = String(
                data.summary?.total ?? 0
            );

            openCount.textContent = String(
                data.summary?.open ?? 0
            );

            resolvedCount.textContent = String(
                data.summary?.resolved ?? 0
            );

            showingCount.textContent = String(
                data.pagination?.returned ?? 0
            );

            lastPageData = data;

            renderRows(data);

            const returned = Number(
                data.pagination?.returned || 0
            );

            const total = Number(
                data.summary?.total || 0
            );

            pageStatus.textContent =
                `${offset + 1}–${offset + returned} of ${total}`;

            previousButton.disabled = offset === 0;

            nextButton.disabled =
                returned === 0
                || offset + returned >= total;

            requestStatus.textContent = 'Read-only data loaded';
        } catch (error) {
            const message = error instanceof Error
                ? error.message
                : 'Unable to load Bug Tracker';

            requestStatus.className = 'status error';
            requestStatus.textContent = message;

            pageStatus.textContent = 'Unavailable';

            showMessage(message, true);
        } finally {
            loading = false;
            refreshButton.disabled = false;
        }
    }


    previousButton.addEventListener(
        'click',
        () => {
            offset = Math.max(
                0,
                offset - pageSize
            );

            loadPage();
        }
    );


    nextButton.addEventListener(
        'click',
        () => {
            offset += pageSize;
            loadPage();
        }
    );


    refreshButton.addEventListener(
        'click',
        () => {
            loadPage();
        }
    );


    closeBugDialogButton.addEventListener(
        'click',
        () => {
            bugDialog.close();
        }
    );


    cancelBugButton.addEventListener(
        'click',
        () => {
            bugDialog.close();
        }
    );


    saveBugButton.addEventListener(
        'click',
        () => {
            saveCurrentBug();
        }
    );


    bugDialog.addEventListener(
        'close',
        () => {
            resetDialog();
        }
    );


    setMutationUiState();
    loadPage();
})();
</script>
</body>
</html>
