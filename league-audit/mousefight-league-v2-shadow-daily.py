"""
MouseFight Genesis Leagues V2 — Phase 1C daily shadow publisher.

Plain language for DEVS FOR DECADES:

Snapshot #001 established the initial V2 League population.

This script creates the next immutable daily snapshot by comparing the current
permanent Genesis League Power population with the latest valid published
snapshot.

The flow is:

current permanent Power
    -> raw V2 classifier
    -> H2 published thresholds
    -> candidate mouse assignments
    -> I6 individual hysteresis
    -> published assignments
    -> promotion / relegation / pending movement report

This script is research-to-production shadow infrastructure only.

It does NOT:
- write SQLite;
- alter permanent Genesis Traits or Abilities;
- change ownership;
- change Genetic Items;
- touch DSPOINC / SPOINC;
- touch PVP escrow / settlement;
- touch event burns / refunds;
- touch Fight Recovery;
- call Discord;
- modify production V1;
- modify website files.
"""

from __future__ import annotations

from bisect import bisect_right
from copy import deepcopy
from datetime import datetime, timezone
from pathlib import Path

import hashlib
import importlib.util
import json
import os
import shutil


ROOT = Path(__file__).resolve().parents[1]

BASELINE_ENGINE = (
    ROOT
    / "league-audit"
    / "mousefight-league-v2-shadow-baseline.py"
)

# mousefight_league_daily_production_v1
#
# DEVS FOR DECADES:
# Local research keeps snapshots in league-audit.
# Production may point this at persistent /data storage through a trusted
# environment variable. The DB remains read-only to this publisher.
DEFAULT_SNAPSHOT_DIRECTORY = (
    ROOT
    / "league-audit"
)

CONFIGURED_SNAPSHOT_DIRECTORY = (
    os.environ.get(
        "MOUSEFIGHT_LEAGUE_SNAPSHOT_DIRECTORY",
        "",
    ).strip()
)

SNAPSHOT_DIRECTORY = (
    Path(
        CONFIGURED_SNAPSHOT_DIRECTORY
    ).resolve()
    if CONFIGURED_SNAPSHOT_DIRECTORY
    else DEFAULT_SNAPSHOT_DIRECTORY
)

SNAPSHOT_PREFIX = (
    "mousefight-leagues-v2-shadow-snapshot-"
)

SNAPSHOT_SUFFIX = ".json"

# mousefight_league_retention_v1
#
# DEVS FOR DECADES:
# The frozen Snapshot #001 audit root always comes from the packaged,
# source-controlled league-audit directory. Production may prune old full
# snapshots from persistent /data without deleting the immutable audit root.
BASELINE_SNAPSHOT = (
    DEFAULT_SNAPSHOT_DIRECTORY
    / "mousefight-leagues-v2-shadow-snapshot-001.json"
)

RETENTION_ANCHOR = (
    SNAPSHOT_DIRECTORY
    / ".retention-anchor.json"
)


EXPECTED_BASELINE_ENGINE_SHA256 = (
    "e9057ea6a39cff8dcdc6c396f75b4da6b32a6d781c428e6763f365d8786f6b7d"
)

EXPECTED_SNAPSHOT_001_SHA256 = (
    "4db64a8b28e50cb1a28cd8ed75d153b61cad8d331415b4985411af5f4f2c5a6f"
)


H2_POPULATION_CHANGE_TRIGGER_PERCENT = 5.0
H2_LARGE_PROGRESS_MAX_GAIN_TRIGGER = 10
NOISE_MAX_ABSOLUTE_POWER_DELTA = 2

H2_MOVE_TRIGGER = 2
H2_GAP_RATIO_TRIGGER = 1.5

I6_POWER_MARGIN = 3
INDIVIDUAL_CONFIRMATION_SNAPSHOTS = 2


def sha256_file(path: Path) -> str:
    """Return the SHA256 digest for one local evidence file."""

    digest = hashlib.sha256()

    with path.open("rb") as handle:
        for chunk in iter(
            lambda: handle.read(1024 * 1024),
            b"",
        ):
            digest.update(chunk)

    return digest.hexdigest()


def normalize_snapshot_reference(
    value,
) -> str:
    """
    Normalize one logical snapshot reference across Windows and Linux.

    Historical snapshots #002-#004 were created on Windows and therefore store
    backslashes. Production Linux uses forward slashes. This changes only path
    presentation; filename, sequence and SHA256 authority remain unchanged.
    """

    return str(
        value
        or ""
    ).replace(
        "\\",
        "/",
    )


def build_snapshot_reference(
    path: Path,
) -> str:
    """
    Build the stable logical snapshot reference stored inside published JSON.

    Physical production storage may live under /data, but the audit contract
    continues to identify snapshot artifacts by league-audit/<filename>.
    """

    if (
        path.parent.resolve()
        != SNAPSHOT_DIRECTORY.resolve()
    ):
        raise RuntimeError(
            "REFUSED: snapshot reference is outside the configured "
            f"snapshot directory: {path}"
        )

    return (
        "league-audit/"
        + path.name
    )


def verify_input_evidence() -> None:
    """
    Refuse to continue if the frozen Phase 1B inputs changed.

    Every recurring snapshot chain must remain rooted in the exact frozen
    Snapshot #001 and baseline engine that already passed validation.
    """

    checks = [
        (
            BASELINE_ENGINE,
            EXPECTED_BASELINE_ENGINE_SHA256,
        ),
        (
            BASELINE_SNAPSHOT,
            EXPECTED_SNAPSHOT_001_SHA256,
        ),
    ]

    for path, expected_hash in checks:
        if not path.exists():
            raise RuntimeError(
                f"REFUSED: required evidence file missing: {path}"
            )

        actual_hash = sha256_file(path)

        if actual_hash != expected_hash:
            raise RuntimeError(
                "REFUSED: evidence hash mismatch.\n"
                f"path={path}\n"
                f"expected={expected_hash}\n"
                f"actual={actual_hash}"
            )


def load_baseline_engine():
    """
    Load the already-validated Phase 1B classifier helper without executing
    its guarded main() publisher.
    """

    spec = importlib.util.spec_from_file_location(
        "mousefight_league_v2_shadow_baseline",
        BASELINE_ENGINE,
    )

    if spec is None or spec.loader is None:
        raise RuntimeError(
            "REFUSED: unable to load Phase 1B baseline engine."
        )

    module = importlib.util.module_from_spec(
        spec
    )

    spec.loader.exec_module(
        module
    )

    return module


def configure_baseline_php_executable(
    baseline,
) -> Path:
    """
    Select the verified PHP CLI without modifying the frozen baseline engine.

    Windows keeps its accepted XAMPP PHP path. Production may provide the
    Render PHP CLI through MOUSEFIGHT_LEAGUE_PHP_EXE, with PATH discovery as a
    final portability fallback.
    """

    configured = (
        os.environ.get(
            "MOUSEFIGHT_LEAGUE_PHP_EXE",
            "",
        ).strip()
    )

    existing_baseline_php = Path(
        baseline.PHP_EXE
    )

    if configured:
        candidate = Path(
            configured
        )
    elif existing_baseline_php.exists():
        candidate = existing_baseline_php
    else:
        discovered = shutil.which(
            "php"
        )

        if not discovered:
            raise RuntimeError(
                "REFUSED: no PHP CLI executable available for "
                "the read-only V1 League source."
            )

        candidate = Path(
            discovered
        )

    if not candidate.exists():
        raise RuntimeError(
            f"REFUSED: configured PHP CLI does not exist: {candidate}"
        )

    baseline.PHP_EXE = candidate

    return candidate


def parse_snapshot_sequence(
    path: Path,
) -> int:
    """Return the numeric sequence encoded in one immutable snapshot filename."""

    name = path.name

    if (
        not name.startswith(
            SNAPSHOT_PREFIX
        )
        or not name.endswith(
            SNAPSHOT_SUFFIX
        )
    ):
        raise RuntimeError(
            f"REFUSED: invalid snapshot filename: {path}"
        )

    sequence_text = name[
        len(
            SNAPSHOT_PREFIX
        ):
        -len(
            SNAPSHOT_SUFFIX
        )
    ]

    if (
        len(
            sequence_text
        ) < 3
        or not sequence_text.isdigit()
    ):
        raise RuntimeError(
            f"REFUSED: invalid snapshot sequence filename: {path}"
        )

    sequence = int(
        sequence_text
    )

    expected_name = (
        f"{SNAPSHOT_PREFIX}"
        f"{sequence:03d}"
        f"{SNAPSHOT_SUFFIX}"
    )

    if name != expected_name:
        raise RuntimeError(
            "REFUSED: snapshot filename is not canonical.\n"
            f"path={path}\n"
            f"expected_name={expected_name}"
        )

    return sequence


def load_snapshot_payload(
    path: Path,
) -> dict:
    """Load one immutable JSON snapshot and reject malformed JSON or payloads."""

    if not path.exists():
        raise RuntimeError(
            f"REFUSED: snapshot missing: {path}"
        )

    try:
        payload = json.loads(
            path.read_text(
                encoding="utf-8"
            )
        )
    except Exception as error:
        raise RuntimeError(
            "REFUSED: unable to parse snapshot JSON.\n"
            f"path={path}\n"
            f"error={error}"
        ) from error

    if not isinstance(
        payload,
        dict,
    ):
        raise RuntimeError(
            f"REFUSED: snapshot payload is not an object: {path}"
        )

    return payload


def load_retention_anchor():
    """
    Load and validate the tiny persistent retention-chain anchor.

    DEVS FOR DECADES:
    Full historical snapshots may be pruned from persistent /data after the
    newest five are retained. The anchor preserves the exact sequence,
    logical reference and raw SHA256 of the one predecessor immediately
    before the oldest retained full snapshot.

    The anchor never replaces normal snapshot validation. It is accepted only
    when the physical predecessor is absent and every continuity field matches
    the immutable source fields already stored inside the retained snapshot.
    """

    if not RETENTION_ANCHOR.exists():
        return None

    try:
        payload = json.loads(
            RETENTION_ANCHOR.read_text(
                encoding="utf-8"
            )
        )
    except Exception as error:
        raise RuntimeError(
            "REFUSED: unable to parse Genesis League retention anchor.\n"
            f"path={RETENTION_ANCHOR}\n"
            f"error={error}"
        ) from error

    if not isinstance(
        payload,
        dict,
    ):
        raise RuntimeError(
            "REFUSED: Genesis League retention anchor is not an object."
        )

    if (
        payload.get(
            "version"
        )
        != "mousefight_genesis_leagues_v2_retention_anchor_v1"
    ):
        raise RuntimeError(
            "REFUSED: unknown Genesis League retention anchor version."
        )

    pruned_sequence = payload.get(
        "pruned_sequence"
    )

    first_retained_sequence = payload.get(
        "first_retained_sequence"
    )

    if (
        type(
            pruned_sequence
        )
        is not int
        or pruned_sequence < 1
        or type(
            first_retained_sequence
        )
        is not int
        or first_retained_sequence
        != pruned_sequence + 1
    ):
        raise RuntimeError(
            "REFUSED: invalid Genesis League retention anchor sequence."
        )

    pruned_hash = str(
        payload.get(
            "pruned_snapshot_sha256"
        )
        or ""
    )

    if (
        len(
            pruned_hash
        )
        != 64
        or any(
            character
            not in "0123456789abcdef"
            for character in pruned_hash
        )
    ):
        raise RuntimeError(
            "REFUSED: invalid Genesis League retention anchor SHA256."
        )

    if not str(
        payload.get(
            "pruned_snapshot"
        )
        or ""
    ):
        raise RuntimeError(
            "REFUSED: retention anchor has no pruned snapshot reference."
        )

    if not str(
        payload.get(
            "first_retained_snapshot"
        )
        or ""
    ):
        raise RuntimeError(
            "REFUSED: retention anchor has no first retained snapshot reference."
        )

    return payload


def validate_snapshot_file(
    path: Path,
) -> tuple[int, dict]:
    """
    Validate one immutable snapshot as a safe previous-state authority.

    DEVS FOR DECADES:
    Snapshot #001 is the frozen baseline chain root.
    Snapshot #002+ must use the recurring V2 daily shadow contract and must
    preserve an exact SHA256 link to the immediately previous snapshot.

    This validates publication continuity only. It does not calculate League
    placement, H2 thresholds, I6 decisions, fightability or permanent state.
    """

    sequence = parse_snapshot_sequence(
        path
    )

    payload = load_snapshot_payload(
        path
    )

    if payload.get(
        "success"
    ) is not True:
        raise RuntimeError(
            f"REFUSED: snapshot success != true: {path}"
        )

    payload_sequence = payload.get(
        "snapshot",
        {},
    ).get(
        "sequence"
    )

    if payload_sequence != sequence:
        raise RuntimeError(
            "REFUSED: snapshot filename/payload sequence mismatch.\n"
            f"path={path}\n"
            f"filename_sequence={sequence}\n"
            f"payload_sequence={payload_sequence}"
        )

    snapshot = payload.get(
        "snapshot",
        {},
    )

    if sequence == 1:
        if snapshot.get(
            "baseline"
        ) is not True:
            raise RuntimeError(
                "REFUSED: Snapshot #001 is not marked baseline."
            )

        actual_hash = sha256_file(
            path
        )

        if (
            actual_hash
            != EXPECTED_SNAPSHOT_001_SHA256
        ):
            raise RuntimeError(
                "REFUSED: frozen Snapshot #001 hash mismatch.\n"
                f"expected={EXPECTED_SNAPSHOT_001_SHA256}\n"
                f"actual={actual_hash}"
            )

        return (
            sequence,
            payload,
        )

    if snapshot.get(
        "baseline"
    ) is not False:
        raise RuntimeError(
            f"REFUSED: recurring Snapshot #{sequence:03d} is incorrectly marked baseline."
        )

    contract = payload.get(
        "contract",
        {},
    )

    if (
        contract.get(
            "version"
        )
        != "mousefight_genesis_leagues_v2_shadow_daily_v1"
        or contract.get(
            "read_only"
        ) is not True
        or contract.get(
            "shadow_only"
        ) is not True
        or int(
            contract.get(
                "league_count",
                0,
            )
            or 0
        ) != 9
    ):
        raise RuntimeError(
            f"REFUSED: Snapshot #{sequence:03d} has an invalid V2 daily contract."
        )

    tiers = payload.get(
        "tiers"
    )

    if (
        not isinstance(
            tiers,
            list,
        )
        or len(
            tiers
        ) != 9
    ):
        raise RuntimeError(
            f"REFUSED: Snapshot #{sequence:03d} does not contain nine tiers."
        )

    previous_sequence = (
        sequence - 1
    )

    previous_path = (
        SNAPSHOT_DIRECTORY
        / (
            f"{SNAPSHOT_PREFIX}"
            f"{previous_sequence:03d}"
            f"{SNAPSHOT_SUFFIX}"
        )
    )

    source = payload.get(
        "source",
        {},
    )

    expected_previous_reference = (
        build_snapshot_reference(
            previous_path
        )
    )

    actual_previous_reference_raw = str(
        source.get(
            "previous_snapshot"
        )
        or ""
    )

    actual_previous_reference = (
        normalize_snapshot_reference(
            actual_previous_reference_raw
        )
    )

    if (
        actual_previous_reference
        != expected_previous_reference
    ):
        raise RuntimeError(
            "REFUSED: recurring snapshot does not reference the immediate previous snapshot.\n"
            f"snapshot={path}\n"
            f"expected_previous={expected_previous_reference}\n"
            f"actual_previous={actual_previous_reference_raw}"
        )

    expected_previous_hash = str(
        source.get(
            "previous_snapshot_sha256"
        )
        or ""
    )

    if not expected_previous_hash:
        raise RuntimeError(
            "REFUSED: recurring snapshot has no previous snapshot SHA256.\n"
            f"snapshot={path}"
        )

    if previous_path.exists():
        actual_previous_hash = sha256_file(
            previous_path
        )

        if (
            expected_previous_hash
            != actual_previous_hash
        ):
            raise RuntimeError(
                "REFUSED: previous snapshot SHA256 chain verification failed.\n"
                f"snapshot={path}\n"
                f"previous={previous_path}\n"
                f"expected={expected_previous_hash}\n"
                f"actual={actual_previous_hash}"
            )

    else:
        anchor = load_retention_anchor()

        if anchor is None:
            raise RuntimeError(
                "REFUSED: previous snapshot in publication chain is missing "
                "and no retention anchor exists.\n"
                f"snapshot={path}\n"
                f"previous={previous_path}"
            )

        anchor_pruned_reference = (
            normalize_snapshot_reference(
                anchor.get(
                    "pruned_snapshot"
                )
            )
        )

        anchor_first_retained_reference = (
            normalize_snapshot_reference(
                anchor.get(
                    "first_retained_snapshot"
                )
            )
        )

        expected_first_retained_reference = (
            build_snapshot_reference(
                path
            )
        )

        if (
            int(
                anchor.get(
                    "pruned_sequence",
                    0,
                )
            )
            != previous_sequence
            or int(
                anchor.get(
                    "first_retained_sequence",
                    0,
                )
            )
            != sequence
            or anchor_pruned_reference
            != expected_previous_reference
            or str(
                anchor.get(
                    "pruned_snapshot_sha256"
                )
                or ""
            )
            != expected_previous_hash
            or anchor_first_retained_reference
            != expected_first_retained_reference
        ):
            raise RuntimeError(
                "REFUSED: Genesis League retention anchor does not prove "
                "the missing predecessor.\n"
                f"snapshot={path}\n"
                f"previous={previous_path}\n"
                f"anchor={RETENTION_ANCHOR}"
            )

    return (
        sequence,
        payload,
    )


def discover_latest_snapshot() -> tuple[Path, int]:
    """
    Return the highest validated immutable V2 snapshot.

    Every canonical snapshot found is validated before the highest sequence is
    accepted. A malformed publication artifact causes refusal rather than being
    silently skipped.
    """

    candidates = []

    for path in (
        SNAPSHOT_DIRECTORY.glob(
            f"{SNAPSHOT_PREFIX}*{SNAPSHOT_SUFFIX}"
        )
    ):
        name = path.name

        sequence_text = name[
            len(
                SNAPSHOT_PREFIX
            ):
            -len(
                SNAPSHOT_SUFFIX
            )
        ]

        if (
            name.startswith(
                SNAPSHOT_PREFIX
            )
            and name.endswith(
                SNAPSHOT_SUFFIX
            )
            and len(
                sequence_text
            ) >= 3
            and sequence_text.isdigit()
        ):
            candidates.append(
                path
            )

    if not candidates:
        raise RuntimeError(
            "REFUSED: no immutable V2 snapshot files found."
        )

    validated = []

    for path in sorted(
        candidates
    ):
        sequence, _ = validate_snapshot_file(
            path
        )

        validated.append(
            (
                sequence,
                path,
            )
        )

    latest_sequence, latest_path = max(
        validated,
        key=lambda item: item[
            0
        ],
    )

    return (
        latest_path,
        latest_sequence,
    )


def load_previous_snapshot(
    previous_snapshot: Path,
    expected_sequence: int,
) -> dict:
    """Load the already-validated previous immutable snapshot."""

    sequence, payload = validate_snapshot_file(
        previous_snapshot
    )

    if sequence != expected_sequence:
        raise RuntimeError(
            "REFUSED: previous snapshot sequence changed during publication.\n"
            f"expected={expected_sequence}\n"
            f"actual={sequence}"
        )

    return payload


def rows_by_identity(
    rows: list[dict],
) -> dict[str, dict]:
    """Return one identity-indexed row map."""

    return {
        row["identity"]: row
        for row in rows
    }


def calculate_dynamics(
    previous_rows: list[dict],
    current_rows: list[dict],
) -> dict:
    """
    Compare the previous and current permanent League Power populations.

    This is the frozen H2 population-dynamics calculation.
    """

    previous = rows_by_identity(
        previous_rows
    )

    current = rows_by_identity(
        current_rows
    )

    continuing = (
        set(previous)
        & set(current)
    )

    new_ids = (
        set(current)
        - set(previous)
    )

    removed_ids = (
        set(previous)
        - set(current)
    )

    deltas = []
    positive = []
    negative = []

    for identity in continuing:
        before = int(
            previous[
                identity
            ][
                "league_power"
            ]
        )

        after = int(
            current[
                identity
            ][
                "league_power"
            ]
        )

        delta = after - before

        deltas.append(
            delta
        )

        if delta > 0:
            positive.append(
                delta
            )

        elif delta < 0:
            negative.append(
                delta
            )

    previous_population = len(
        previous_rows
    )

    current_population = len(
        current_rows
    )

    population_change_percent = (
        (
            current_population
            - previous_population
        )
        * 100.0
        / previous_population
        if previous_population
        else 0.0
    )

    return {
        "previous_population":
            previous_population,

        "current_population":
            current_population,

        "population_change_percent":
            round(
                population_change_percent,
                6,
            ),

        "continuing_identities":
            len(
                continuing
            ),

        "new_identities":
            len(
                new_ids
            ),

        "removed_identities":
            len(
                removed_ids
            ),

        "positive_power_changes":
            len(
                positive
            ),

        "negative_power_changes":
            len(
                negative
            ),

        "max_positive_gain":
            max(
                positive,
                default=0,
            ),

        "max_negative_drop":
            abs(
                min(
                    negative,
                    default=0,
                )
            ),

        "max_absolute_delta":
            max(
                (
                    abs(delta)
                    for delta in deltas
                ),
                default=0,
            ),
    }


def is_structural_population_change(
    dynamics: dict,
) -> bool:
    """Return whether H2 identifies a structural population change."""

    return (
        abs(
            dynamics[
                "population_change_percent"
            ]
        )
        >= H2_POPULATION_CHANGE_TRIGGER_PERCENT
    )


def is_large_progression(
    dynamics: dict,
) -> bool:
    """Return whether H2 identifies a large one-direction progression event."""

    return (
        dynamics[
            "max_positive_gain"
        ]
        >= H2_LARGE_PROGRESS_MAX_GAIN_TRIGGER
        and dynamics[
            "negative_power_changes"
        ] == 0
    )


def is_small_bidirectional_noise(
    dynamics: dict,
) -> bool:
    """Return whether H2 identifies bounded bidirectional Power noise."""

    return (
        abs(
            dynamics[
                "population_change_percent"
            ]
        ) < 0.000001
        and dynamics[
            "positive_power_changes"
        ] > 0
        and dynamics[
            "negative_power_changes"
        ] > 0
        and dynamics[
            "max_absolute_delta"
        ]
        <= NOISE_MAX_ABSOLUTE_POWER_DELTA
    )


def publish_h2(
    previous_published: list[int] | None,
    raw_classification: dict,
    dynamics: dict,
) -> tuple[list[int], list[str]]:
    """
    Publish global V2 thresholds using frozen H2 policy semantics.
    """

    raw_thresholds = raw_classification[
        "thresholds"
    ]

    if previous_published is None:
        return (
            list(
                raw_thresholds
            ),
            [
                "day0_raw"
                for _ in raw_thresholds
            ],
        )

    if is_structural_population_change(
        dynamics
    ):
        return (
            list(
                raw_thresholds
            ),
            [
                "structural_population"
                for _ in raw_thresholds
            ],
        )

    if is_large_progression(
        dynamics
    ):
        return (
            list(
                raw_thresholds
            ),
            [
                "large_progression"
                for _ in raw_thresholds
            ],
        )

    if is_small_bidirectional_noise(
        dynamics
    ):
        return (
            list(
                previous_published
            ),
            [
                "small_noise_retained"
                for _ in raw_thresholds
            ],
        )

    published = []
    reasons = []

    for previous, candidate, boundary in zip(
        previous_published,
        raw_thresholds,
        raw_classification[
            "boundaries"
        ],
    ):
        movement = abs(
            candidate
            - previous
        )

        gap_ratio = float(
            boundary[
                "gap_ratio"
            ]
        )

        if (
            movement
            >= H2_MOVE_TRIGGER
            and gap_ratio
            >= H2_GAP_RATIO_TRIGGER
        ):
            published.append(
                candidate
            )

            reasons.append(
                "meaningful_gap_move"
            )

        else:
            published.append(
                previous
            )

            reasons.append(
                "retained"
            )

    return (
        published,
        reasons,
    )


def assign_with_thresholds(
    rows: list[dict],
    thresholds: list[int],
    leagues: list[dict],
) -> dict[str, dict]:
    """Assign every current mouse to one candidate League."""

    assignments = {}

    for row in rows:
        index = bisect_right(
            thresholds,
            int(
                row[
                    "league_power"
                ]
            ),
        )

        league = leagues[
            index
        ]

        assignments[
            row[
                "identity"
            ]
        ] = {
            "rank":
                index + 1,
            "key":
                league[
                    "key"
                ],
            "label":
                league[
                    "label"
                ],
            "league_power":
                int(
                    row[
                        "league_power"
                    ]
                ),
        }

    return assignments


def clear_pending(
    record: dict,
) -> None:
    """Clear one mouse's individual I6 pending transition."""

    record[
        "pending_rank"
    ] = None

    record[
        "pending_count"
    ] = 0


def record_pending(
    record: dict,
    candidate_rank: int,
) -> None:
    """Record one consecutive I6 candidate transition."""

    if (
        record[
            "pending_rank"
        ]
        == candidate_rank
    ):
        record[
            "pending_count"
        ] += 1

    else:
        record[
            "pending_rank"
        ] = candidate_rank

        record[
            "pending_count"
        ] = 1


def confirm_candidate(
    record: dict,
    candidate_assignment: dict,
) -> None:
    """Publish one candidate and preserve transition provenance."""

    previous_rank = int(
        record[
            "published_assignment"
        ][
            "rank"
        ]
    )

    candidate_rank = int(
        candidate_assignment[
            "rank"
        ]
    )

    if candidate_rank != previous_rank:
        record[
            "last_transition_from_rank"
        ] = previous_rank

        record[
            "last_transition_to_rank"
        ] = candidate_rank

    record[
        "published_assignment"
    ] = deepcopy(
        candidate_assignment
    )

    clear_pending(
        record
    )


def is_last_transition_reversal(
    record: dict,
    candidate_rank: int,
) -> bool:
    """Return whether a candidate directly reverses the latest published move."""

    last_from_rank = record.get(
        "last_transition_from_rank"
    )

    last_to_rank = record.get(
        "last_transition_to_rank"
    )

    if (
        last_from_rank is None
        or last_to_rank is None
    ):
        return False

    current_published_rank = int(
        record[
            "published_assignment"
        ][
            "rank"
        ]
    )

    return (
        current_published_rank
        == int(
            last_to_rank
        )
        and int(
            candidate_rank
        )
        == int(
            last_from_rank
        )
    )


def crossed_boundary_margin(
    previous_rank: int,
    candidate_rank: int,
    power: int,
    thresholds: list[int],
) -> int:
    """Return distance beyond the nearest crossed published boundary."""

    if candidate_rank == previous_rank:
        return 0

    if abs(
        candidate_rank
        - previous_rank
    ) > 1:
        return 999999

    if candidate_rank > previous_rank:
        boundary_index = (
            previous_rank - 1
        )

        if (
            boundary_index < 0
            or boundary_index
            >= len(
                thresholds
            )
        ):
            return 999999

        boundary = thresholds[
            boundary_index
        ]

        return max(
            0,
            int(power)
            - int(boundary),
        )

    boundary_index = (
        previous_rank - 2
    )

    if (
        boundary_index < 0
        or boundary_index
        >= len(
            thresholds
        )
    ):
        return 999999

    boundary = thresholds[
        boundary_index
    ]

    return max(
        0,
        (
            int(boundary)
            - 1
        )
        - int(power),
    )


def own_power_delta(
    identity: str,
    previous_rows: list[dict],
    current_rows: list[dict],
) -> int:
    """Return one mouse's permanent League Power change."""

    previous_map = rows_by_identity(
        previous_rows
    )

    current_map = rows_by_identity(
        current_rows
    )

    if (
        identity not in previous_map
        or identity not in current_map
    ):
        return 0

    return (
        int(
            current_map[
                identity
            ][
                "league_power"
            ]
        )
        - int(
            previous_map[
                identity
            ][
                "league_power"
            ]
        )
    )


def build_previous_individual_state(
    previous_snapshot: dict,
) -> dict[str, dict]:
    """
    Rebuild I6 state from the previous published JSON snapshot.

    Snapshot #001 has no transition provenance yet, so those values begin None.
    Later snapshots persist them explicitly.
    """

    state = {}

    for mouse in previous_snapshot[
        "mice"
    ]:
        identity = (
            str(
                mouse[
                    "token_id"
                ]
            )
            + "|"
            + str(
                mouse.get(
                    "collection"
                )
                or "genesis"
            )
        )

        movement = (
            mouse.get(
                "movement"
            )
            or {}
        )

        league = (
            mouse.get(
                "league"
            )
            or {}
        )

        power = (
            mouse.get(
                "power"
            )
            or {}
        )

        published_rank = int(
            movement.get(
                "published_rank"
            )
            or league.get(
                "published_rank"
            )
            or league.get(
                "rank"
            )
            or 0
        )

        if published_rank <= 0:
            raise RuntimeError(
                "REFUSED: previous mouse is missing published League rank: "
                + identity
            )

        state[
            identity
        ] = {
            "published_assignment": {
                "rank":
                    published_rank,
                "key":
                    str(
                        league.get(
                            "key"
                        )
                        or ""
                    ),
                "label":
                    str(
                        league.get(
                            "label"
                        )
                        or ""
                    ),
                "league_power":
                    int(
                        power.get(
                            "league_power"
                        )
                        or 0
                    ),
            },

            "pending_rank":
                movement.get(
                    "pending_rank"
                ),

            "pending_count":
                int(
                    movement.get(
                        "pending_count"
                    )
                    or 0
                ),

            "last_transition_from_rank":
                movement.get(
                    "last_transition_from_rank"
                ),

            "last_transition_to_rank":
                movement.get(
                    "last_transition_to_rank"
                ),
        }

    return state


def apply_i6_day(
    individual_state: dict,
    candidate_assignments: dict,
    previous_rows: list[dict],
    current_rows: list[dict],
    thresholds: list[int],
    dynamics: dict,
) -> dict:
    """Apply the frozen provenance-aware I6 decision policy."""

    published_assignments = {}

    delayed = []
    immediate = []
    confirmed = []

    structural_bypass = (
        is_structural_population_change(
            dynamics
        )
    )

    for identity, candidate in (
        candidate_assignments.items()
    ):
        if identity not in individual_state:
            individual_state[
                identity
            ] = {
                "published_assignment":
                    deepcopy(
                        candidate
                    ),

                "pending_rank":
                    None,

                "pending_count":
                    0,

                "last_transition_from_rank":
                    None,

                "last_transition_to_rank":
                    None,
            }

            published_assignments[
                identity
            ] = deepcopy(
                candidate
            )

            immediate.append(
                identity
            )

            continue

        record = individual_state[
            identity
        ]

        current_published = record[
            "published_assignment"
        ]

        previous_rank = int(
            current_published[
                "rank"
            ]
        )

        candidate_rank = int(
            candidate[
                "rank"
            ]
        )

        if candidate_rank == previous_rank:
            record[
                "published_assignment"
            ] = deepcopy(
                candidate
            )

            clear_pending(
                record
            )

            published_assignments[
                identity
            ] = deepcopy(
                record[
                    "published_assignment"
                ]
            )

            continue

        if structural_bypass:
            confirm_candidate(
                record,
                candidate,
            )

            published_assignments[
                identity
            ] = deepcopy(
                candidate
            )

            immediate.append(
                identity
            )

            continue

        power_delta = own_power_delta(
            identity,
            previous_rows,
            current_rows,
        )

        power = int(
            candidate[
                "league_power"
            ]
        )

        crossing_margin = crossed_boundary_margin(
            previous_rank,
            candidate_rank,
            power,
            thresholds,
        )

        promotion = (
            candidate_rank
            > previous_rank
        )

        relegation = (
            candidate_rank
            < previous_rank
        )

        threshold_only_change = (
            power_delta == 0
        )

        last_transition_reversal = (
            is_last_transition_reversal(
                record,
                candidate_rank,
            )
        )

        allow_immediate = False

        if not (
            threshold_only_change
            or last_transition_reversal
        ):
            small_bidirectional_noise = (
                is_small_bidirectional_noise(
                    dynamics
                )
            )

            if promotion:
                allow_immediate = (
                    (
                        power_delta > 0
                        and not small_bidirectional_noise
                    )
                    or crossing_margin
                    >= I6_POWER_MARGIN
                )

            elif relegation:
                allow_immediate = (
                    crossing_margin
                    >= I6_POWER_MARGIN
                )

        if allow_immediate:
            confirm_candidate(
                record,
                candidate,
            )

            published_assignments[
                identity
            ] = deepcopy(
                candidate
            )

            immediate.append(
                identity
            )

            continue

        record_pending(
            record,
            candidate_rank,
        )

        if (
            record[
                "pending_count"
            ]
            >= INDIVIDUAL_CONFIRMATION_SNAPSHOTS
        ):
            confirm_candidate(
                record,
                candidate,
            )

            published_assignments[
                identity
            ] = deepcopy(
                candidate
            )

            confirmed.append(
                identity
            )

        else:
            published_assignments[
                identity
            ] = deepcopy(
                record[
                    "published_assignment"
                ]
            )

            delayed.append(
                identity
            )

    return {
        "assignments":
            published_assignments,

        "individual_state":
            individual_state,

        "delayed":
            delayed,

        "immediate":
            immediate,

        "confirmed":
            confirmed,
    }


def build_ranges_from_thresholds(
    thresholds: list[int],
    leagues: list[dict],
) -> list[dict]:
    """Build nine public ranges from eight H2-published thresholds."""

    starts = [
        0,
        *thresholds,
    ]

    tiers = []

    for index, league in enumerate(
        leagues
    ):
        minimum_power = int(
            starts[
                index
            ]
        )

        maximum_power = (
            int(
                starts[
                    index + 1
                ]
            )
            - 1
            if index + 1
            < len(
                starts
            )
            else None
        )

        tiers.append(
            {
                "rank":
                    index + 1,
                "key":
                    league[
                        "key"
                    ],
                "label":
                    league[
                        "label"
                    ],
                "symbol":
                    league[
                        "symbol"
                    ],
                "target_share":
                    league[
                        "share"
                    ],
                "minimum_power":
                    minimum_power,
                "maximum_power":
                    maximum_power,
            }
        )

    return tiers


def movement_entry(
    mouse: dict,
    identity: str,
    previous_rank: int | None,
    candidate_rank: int,
    published_rank: int,
    status: str,
    resolution: str,
) -> dict:
    """Build one compact movement row for website/Discord consumers later."""

    return {
        "identity":
            identity,

        "token_id":
            mouse.get(
                "token_id"
            ),

        "collection":
            mouse.get(
                "collection"
            ),

        "custom_name":
            mouse.get(
                "custom_name"
            ),

        "owner":
            deepcopy(
                mouse.get(
                    "owner"
                )
                or {}
            ),

        "league_power":
            int(
                mouse.get(
                    "power",
                    {},
                ).get(
                    "league_power",
                    0,
                )
            ),

        "previous_rank":
            previous_rank,

        "candidate_rank":
            candidate_rank,

        "published_rank":
            published_rank,

        "status":
            status,

        "resolution":
            resolution,
    }


def main() -> None:
    """Create the next immutable V2 daily shadow snapshot artifact."""

    verify_input_evidence()

    previous_snapshot, previous_sequence = (
        discover_latest_snapshot()
    )

    next_sequence = (
        previous_sequence + 1
    )

    output = (
        SNAPSHOT_DIRECTORY
        / (
            f"{SNAPSHOT_PREFIX}"
            f"{next_sequence:03d}"
            f"{SNAPSHOT_SUFFIX}"
        )
    )

    if output.exists():
        raise RuntimeError(
            f"REFUSED: output already exists: {output}"
        )

    baseline = load_baseline_engine()

    configure_baseline_php_executable(
        baseline
    )

    previous = load_previous_snapshot(
        previous_snapshot,
        previous_sequence,
    )

    current_v1 = (
        baseline.load_v1_power_dataset()
    )

    if (
        current_v1.get(
            "contract",
            {},
        ).get(
            "read_only"
        )
        is not True
        or current_v1.get(
            "contract",
            {},
        ).get(
            "identity"
        )
        != "token_id_plus_collection"
    ):
        raise RuntimeError(
            "REFUSED: V1 League source lost the required read-only "
            "token_id + collection contract."
        )

    previous_rows = []

    for mouse in previous[
        "mice"
    ]:
        previous_rows.append(
            {
                "identity":
                    baseline.build_identity(
                        mouse
                    ),

                "league_power":
                    int(
                        mouse.get(
                            "power",
                            {},
                        ).get(
                            "league_power",
                            0,
                        )
                    ),
            }
        )

    current_rows = []

    current_mouse_by_identity = {}

    identities = set()

    for mouse in current_v1[
        "mice"
    ]:
        identity = baseline.build_identity(
            mouse
        )

        if identity in identities:
            raise RuntimeError(
                "REFUSED: duplicate current identity: "
                + identity
            )

        identities.add(
            identity
        )

        league_power = int(
            mouse.get(
                "power",
                {},
            ).get(
                "league_power",
                0,
            )
        )

        current_rows.append(
            {
                "identity":
                    identity,
                "league_power":
                    league_power,
            }
        )

        current_mouse_by_identity[
            identity
        ] = mouse

    powers = [
        row[
            "league_power"
        ]
        for row in current_rows
    ]

    raw_boundaries = (
        baseline.classify_distribution(
            powers,
            baseline.DISTANCE_WEIGHT,
        )
    )

    raw_thresholds = [
        int(
            boundary[
                "upper_power"
            ]
        )
        for boundary in raw_boundaries
    ]

    dynamics = calculate_dynamics(
        previous_rows,
        current_rows,
    )

    previous_published_thresholds = [
        int(value)
        for value in previous[
            "snapshot"
        ][
            "published_thresholds"
        ]
    ]

    published_thresholds, h2_reasons = publish_h2(
        previous_published_thresholds,
        {
            "thresholds":
                raw_thresholds,
            "boundaries":
                raw_boundaries,
        },
        dynamics,
    )

    candidate_assignments = assign_with_thresholds(
        current_rows,
        published_thresholds,
        baseline.LEAGUES,
    )

    individual_state = (
        build_previous_individual_state(
            previous
        )
    )

    i6_result = apply_i6_day(
        individual_state,
        candidate_assignments,
        previous_rows,
        current_rows,
        published_thresholds,
        dynamics,
    )

    published_assignments = (
        i6_result[
            "assignments"
        ]
    )

    tiers = build_ranges_from_thresholds(
        published_thresholds,
        baseline.LEAGUES,
    )

    previous_mouse_by_identity = {
        baseline.build_identity(mouse):
            mouse
        for mouse in previous[
            "mice"
        ]
    }

    tier_summaries = [
        {
            **deepcopy(tier),
            "named_mice": 0,
            "verified_current_owner_mice": 0,
            "fitness_unlocked_mice": 0,
            "default_mousefight_ready_mice": 0,
            "distinct_owner_ids": set(),
        }
        for tier in tiers
    ]

    output_mice = []

    total_owner_ids = set()
    total_ready = 0

    promotions = []
    relegations = []
    pending = []
    new_mice = []

    delayed_ids = set(
        i6_result[
            "delayed"
        ]
    )

    immediate_ids = set(
        i6_result[
            "immediate"
        ]
    )

    confirmed_ids = set(
        i6_result[
            "confirmed"
        ]
    )

    for identity, current_mouse in (
        current_mouse_by_identity.items()
    ):
        mouse = deepcopy(
            current_mouse
        )

        candidate = candidate_assignments[
            identity
        ]

        published = published_assignments[
            identity
        ]

        record = i6_result[
            "individual_state"
        ][
            identity
        ]

        candidate_rank = int(
            candidate[
                "rank"
            ]
        )

        published_rank = int(
            published[
                "rank"
            ]
        )

        previous_mouse = (
            previous_mouse_by_identity.get(
                identity
            )
        )

        previous_rank = (
            int(
                previous_mouse[
                    "league"
                ][
                    "rank"
                ]
            )
            if previous_mouse
            else None
        )

        tier = tiers[
            published_rank - 1
        ]

        current_power = int(
            mouse.get(
                "power",
                {},
            ).get(
                "league_power",
                0,
            )
        )

        progression = (
            baseline.calculate_progression(
                current_power,
                published_rank,
                tiers,
                published_thresholds,
            )
        )

        next_tier = (
            tiers[
                published_rank
            ]
            if published_rank
            < len(
                tiers
            )
            else None
        )

        next_threshold = (
            published_thresholds[
                published_rank - 1
            ]
            if published_rank
            < len(
                tiers
            )
            else None
        )

        mouse[
            "league"
        ] = {
            "rank":
                published_rank,

            "published_rank":
                published_rank,

            "candidate_rank":
                candidate_rank,

            "key":
                tier[
                    "key"
                ],

            "label":
                tier[
                    "label"
                ],

            "symbol":
                tier[
                    "symbol"
                ],

            "minimum_power":
                tier[
                    "minimum_power"
                ],

            "maximum_power":
                tier[
                    "maximum_power"
                ],

            "next_tier":
                (
                    {
                        "rank":
                            next_tier[
                                "rank"
                            ],

                        "key":
                            next_tier[
                                "key"
                            ],

                        "label":
                            next_tier[
                                "label"
                            ],

                        "symbol":
                            next_tier[
                                "symbol"
                            ],

                        "minimum_power":
                            next_threshold,

                        "power_needed":
                            max(
                                0,
                                int(
                                    next_threshold
                                )
                                - current_power,
                            ),
                    }
                    if next_tier
                    is not None
                    else None
                ),
        }

        mouse[
            "progression"
        ] = progression

        if previous_rank is None:
            status = "new_mouse"

        elif published_rank > previous_rank:
            status = "promotion"

        elif published_rank < previous_rank:
            status = "relegation"

        elif candidate_rank != published_rank:
            status = "pending"

        else:
            status = "stable"

        if identity in confirmed_ids:
            resolution = "confirmed"

        elif identity in delayed_ids:
            resolution = "delayed"

        elif identity in immediate_ids:
            resolution = "immediate"

        else:
            resolution = "stable"

        mouse[
            "movement"
        ] = {
            "status":
                status,

            "previous_rank":
                previous_rank,

            "candidate_rank":
                candidate_rank,

            "published_rank":
                published_rank,

            "pending_rank":
                record.get(
                    "pending_rank"
                ),

            "pending_count":
                int(
                    record.get(
                        "pending_count"
                    )
                    or 0
                ),

            "last_transition_from_rank":
                record.get(
                    "last_transition_from_rank"
                ),

            "last_transition_to_rank":
                record.get(
                    "last_transition_to_rank"
                ),

            "resolution":
                resolution,
        }

        row = movement_entry(
            mouse,
            identity,
            previous_rank,
            candidate_rank,
            published_rank,
            status,
            resolution,
        )

        if status == "promotion":
            promotions.append(
                row
            )

        elif status == "relegation":
            relegations.append(
                row
            )

        elif status == "pending":
            pending.append(
                row
            )

        elif status == "new_mouse":
            new_mice.append(
                row
            )

        output_mice.append(
            mouse
        )

        summary = tier_summaries[
            published_rank - 1
        ]

        summary[
            "named_mice"
        ] += 1

        eligibility = (
            mouse.get(
                "eligibility"
            )
            or {}
        )

        owner = (
            mouse.get(
                "owner"
            )
            or {}
        )

        owner_id = str(
            owner.get(
                "user_id"
            )
            or ""
        ).strip()

        if owner_id:
            summary[
                "distinct_owner_ids"
            ].add(
                owner_id
            )

            total_owner_ids.add(
                owner_id
            )

        if (
            eligibility.get(
                "verified_current_owner"
            )
            is True
        ):
            summary[
                "verified_current_owner_mice"
            ] += 1

        if (
            eligibility.get(
                "fitness_unlocked"
            )
            is True
        ):
            summary[
                "fitness_unlocked_mice"
            ] += 1

        if (
            eligibility.get(
                "default_mousefight_ready"
            )
            is True
        ):
            summary[
                "default_mousefight_ready_mice"
            ] += 1

            total_ready += 1

    public_tier_summaries = []

    for summary in tier_summaries:
        owner_ids = summary.pop(
            "distinct_owner_ids"
        )

        summary[
            "distinct_owners"
        ] = len(
            owner_ids
        )

        public_tier_summaries.append(
            summary
        )

    output_mice.sort(
        key=lambda mouse: (
            -int(
                mouse[
                    "league"
                ][
                    "rank"
                ]
            ),
            -int(
                mouse[
                    "power"
                ][
                    "league_power"
                ]
            ),
            str(
                mouse.get(
                    "custom_name"
                )
                or ""
            ).lower(),
        )
    )

    current_ids = set(
        current_mouse_by_identity
    )

    removed_mice = []

    for identity, old_mouse in (
        previous_mouse_by_identity.items()
    ):
        if identity in current_ids:
            continue

        removed_mice.append(
            {
                "identity":
                    identity,

                "token_id":
                    old_mouse.get(
                        "token_id"
                    ),

                "collection":
                    old_mouse.get(
                        "collection"
                    ),

                "custom_name":
                    old_mouse.get(
                        "custom_name"
                    ),

                "previous_rank":
                    old_mouse.get(
                        "league",
                        {},
                    ).get(
                        "rank"
                    ),
            }
        )

    generated_at = (
        datetime.now(
            timezone.utc
        )
        .replace(
            microsecond=0
        )
        .isoformat()
        .replace(
            "+00:00",
            "Z",
        )
    )

    payload = {
        "success":
            True,

        "generated_at":
            generated_at,

        "contract": {
            "version":
                "mousefight_genesis_leagues_v2_shadow_daily_v1",

            "read_only":
                True,

            "shadow_only":
                True,

            "identity":
                "token_id_plus_collection",

            "source_power_contract":
                "mousefight_genesis_leagues_v1",

            "source_v1_league_assignment_used":
                False,

            "league_count":
                9,

            "classifier": {
                "distance_weight":
                    baseline.DISTANCE_WEIGHT,

                "equal_power_split_allowed":
                    False,
            },

            "h2": {
                "population_change_trigger_percent":
                    H2_POPULATION_CHANGE_TRIGGER_PERCENT,

                "large_progression_max_gain_trigger":
                    H2_LARGE_PROGRESS_MAX_GAIN_TRIGGER,

                "noise_max_absolute_power_delta":
                    NOISE_MAX_ABSOLUTE_POWER_DELTA,

                "move_trigger":
                    H2_MOVE_TRIGGER,

                "gap_ratio_trigger":
                    H2_GAP_RATIO_TRIGGER,
            },

            "i6": {
                "strategy":
                    "I6",

                "power_margin":
                    I6_POWER_MARGIN,

                "confirmation_snapshots":
                    INDIVIDUAL_CONFIRMATION_SNAPSHOTS,
            },
        },

        "source": {
            "previous_snapshot":
                build_snapshot_reference(
                    previous_snapshot
                ),

            "previous_snapshot_sha256":
                sha256_file(
                    previous_snapshot
                ),

            "baseline_engine_sha256":
                sha256_file(
                    BASELINE_ENGINE
                ),
        },

        "snapshot": {
            "sequence":
                next_sequence,

            "baseline":
                False,

            "population":
                len(
                    output_mice
                ),

            "raw_thresholds":
                raw_thresholds,

            "published_thresholds":
                published_thresholds,

            "h2_reasons":
                h2_reasons,

            "dynamics":
                dynamics,

            "boundaries":
                raw_boundaries,
        },

        "tiers":
            tiers,

        "summary": {
            "named_mice":
                len(
                    output_mice
                ),

            "distinct_owners":
                len(
                    total_owner_ids
                ),

            "default_mousefight_ready_mice":
                total_ready,

            "power_min":
                min(
                    powers
                ),

            "power_max":
                max(
                    powers
                ),

            "tiers":
                public_tier_summaries,
        },

        "mice":
            output_mice,

        "movements": {
            "promotions":
                promotions,

            "relegations":
                relegations,

            "pending":
                pending,

            "new_mice":
                new_mice,

            "removed_mice":
                removed_mice,
        },
    }

    if len(
        payload[
            "mice"
        ]
    ) != len(
        set(
            baseline.build_identity(mouse)
            for mouse in payload[
                "mice"
            ]
        )
    ):
        raise RuntimeError(
            "REFUSED: duplicate identity in "
            f"Snapshot #{next_sequence:03d}."
        )

    if len(
        payload[
            "tiers"
        ]
    ) != 9:
        raise RuntimeError(
            "REFUSED: "
            f"Snapshot #{next_sequence:03d} does not contain nine tiers."
        )

    if (
        payload[
            "snapshot"
        ][
            "published_thresholds"
        ]
        != sorted(
            set(
                payload[
                    "snapshot"
                ][
                    "published_thresholds"
                ]
            )
        )
    ):
        raise RuntimeError(
            "REFUSED: published thresholds are not strictly increasing."
        )

    serialized_payload = (
        json.dumps(
            payload,
            ensure_ascii=False,
            indent=2,
        )
        + "\n"
    )

    temporary_output = output.with_name(
        output.name
        + ".tmp"
    )

    if temporary_output.exists():
        temporary_output.unlink()

    temporary_output.write_text(
        serialized_payload,
        encoding="utf-8",
    )

    temporary_output.replace(
        output
    )

    print(
        "========================================"
    )
    print(
        f" MOUSEFIGHT LEAGUES V2 — DAILY SHADOW #{next_sequence:03d}"
    )
    print(
        "========================================"
    )

    print(
        "STATUS: PASS"
    )

    print(
        "population:",
        payload[
            "summary"
        ][
            "named_mice"
        ],
    )

    print(
        "owners:",
        payload[
            "summary"
        ][
            "distinct_owners"
        ],
    )

    print(
        "ready:",
        payload[
            "summary"
        ][
            "default_mousefight_ready_mice"
        ],
    )

    print(
        "raw_thresholds:",
        raw_thresholds,
    )

    print(
        "published_thresholds:",
        published_thresholds,
    )

    print(
        "H2 reasons:",
        h2_reasons,
    )

    print(
        "dynamics:",
        dynamics,
    )

    print()
    print(
        "promotions:",
        len(
            promotions
        ),
    )

    print(
        "relegations:",
        len(
            relegations
        ),
    )

    print(
        "pending:",
        len(
            pending
        ),
    )

    print(
        "new mice:",
        len(
            new_mice
        ),
    )

    print(
        "removed mice:",
        len(
            removed_mice
        ),
    )

    print()
    print(
        "DB writes: NONE"
    )

    print(
        "Production V1 changes: NONE"
    )

    print(
        "OUTPUT:",
        output,
    )

    print(
        "SHA256:",
        sha256_file(
            output
        ),
    )


if __name__ == "__main__":
    main()
