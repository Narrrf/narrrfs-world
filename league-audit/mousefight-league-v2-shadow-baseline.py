"""
MouseFight Genesis Leagues V2 — Phase 1B shadow baseline publisher.

Plain language for DEVS FOR DECADES:

This script creates the FIRST isolated V2 League snapshot from the current
read-only V1 League API output.

The existing V1 endpoint is used only as the already-verified loader for:
- named Genesis identity;
- current owner presentation;
- permanent Trait Power;
- permanent Ability Power;
- named-mouse bonus;
- Fitness bonus;
- MouseFight readiness.

The V1 league assignment is explicitly ignored.

This script then applies the frozen V2 percentile + natural-gap classifier
using the approved Phase 1 candidate distance weight 2.25.

Snapshot #1 is a baseline:
- H2 has no previous published thresholds to compare against;
- I6 has no previous individual published state;
- therefore raw V2 classification becomes the initial published state;
- no promotion/relegation history is invented during V1 -> V2 initialization.

This script:
- does NOT write SQLite;
- does NOT mutate Genesis or Lab state;
- does NOT touch DSPOINC / SPOINC;
- does NOT touch PVP escrow / settlement;
- does NOT touch event burns / refunds;
- does NOT touch Fight Recovery;
- does NOT change Genetic Items;
- does NOT change staking;
- does NOT call Discord;
- does NOT change production V1.

Research authorities are hash-verified before calculation.
"""

from __future__ import annotations

from bisect import bisect_right
from collections import Counter
from copy import deepcopy
from datetime import datetime, timezone
from pathlib import Path

import hashlib
import json
import math
import statistics
import subprocess


ROOT = Path(__file__).resolve().parents[1]

PHP_EXE = Path(
    r"C:\xampp-server\php\php.exe"
)

V1_API = (
    ROOT
    / "api"
    / "leaderboard"
    / "get-mousefight-leagues.php"
)

OUTPUT = (
    ROOT
    / "league-audit"
    / "mousefight-leagues-v2-shadow-snapshot-001.json"
)


# ---------------------------------------------------------------------
# FROZEN RESEARCH AUTHORITIES
# ---------------------------------------------------------------------

FROZEN_SOURCE_HASHES = {
    "mousefight-league-v2b-classifier-simulator.py":
        "00b330bb231d7c90749024fa75cff7050cecd0d19e1c19e412b1f3d9f284ad59",

    "mousefight-league-v2b-hysteresis-policy-simulator2.py":
        "0b0eef48eef08f790593108feb11063b0a2b0cc68ee57e7e95eab1d3de2e776a",

    "mousefight-league-v2c-i6-hysteresis-refinement.py":
        "94983007a657a96122ddfc9fefed61588626712676d5536e4fbcbb309470e344",
}


# ---------------------------------------------------------------------
# V2 PHASE 1 CONTRACT CANDIDATES
#
# These values come from the accepted V2 research phase.
# Phase 1B Snapshot #1 exercises only the classifier.
#
# H2/I6 values are recorded in the contract for provenance but their
# cross-snapshot behavior is intentionally not needed for day-0 baseline.
# ---------------------------------------------------------------------

DISTANCE_WEIGHT = 2.25

H2_MOVE_TRIGGER = 2
H2_GAP_RATIO_TRIGGER = 1.5

I6_POWER_MARGIN = 3
INDIVIDUAL_CONFIRMATION_SNAPSHOTS = 2


LEAGUES = [
    {
        "key": "crumb",
        "label": "Crumb",
        "symbol": "▫️",
        "share": 6.0,
    },
    {
        "key": "cheese",
        "label": "Cheese",
        "symbol": "🧀",
        "share": 24.0,
    },
    {
        "key": "bronze",
        "label": "Bronze",
        "symbol": "🥉",
        "share": 22.0,
    },
    {
        "key": "silver",
        "label": "Silver",
        "symbol": "🥈",
        "share": 18.0,
    },
    {
        "key": "golden",
        "label": "Golden",
        "symbol": "🥇",
        "share": 15.0,
    },
    {
        "key": "diamond",
        "label": "Diamond",
        "symbol": "💎",
        "share": 8.0,
    },
    {
        "key": "genesis_master",
        "label": "Genesis Master",
        "symbol": "🧬",
        "share": 4.0,
    },
    {
        "key": "mouseverse_champion",
        "label": "Mouseverse Champion",
        "symbol": "👑",
        "share": 2.0,
    },
    {
        "key": "ultra_champion",
        "label": "Ultra Champion",
        "symbol": "🌌",
        "share": 1.0,
    },
]


LOCAL_GAP_RADIUS = 4

MIN_SEARCH_WINDOW_PP = 0.50
MAX_SEARCH_WINDOW_PP = 2.00

ADJACENT_SHARE_WINDOW_FACTOR = 0.25


def sha256_file(path: Path) -> str:
    """Return the SHA256 hash for one local file."""

    digest = hashlib.sha256()

    with path.open("rb") as handle:
        for chunk in iter(
            lambda: handle.read(1024 * 1024),
            b"",
        ):
            digest.update(chunk)

    return digest.hexdigest()


def verify_frozen_research_sources() -> None:
    """
    Refuse to calculate from unexpected research authorities.

    The production shadow extraction must remain traceable to the exact
    research files that passed V2-2C.
    """

    for relative_path, expected_hash in (
        FROZEN_SOURCE_HASHES.items()
    ):
        path = ROOT / relative_path

        if not path.exists():
            raise RuntimeError(
                f"REFUSED: missing frozen research source: {path}"
            )

        actual_hash = sha256_file(path)

        if actual_hash != expected_hash:
            raise RuntimeError(
                "REFUSED: frozen research source hash mismatch.\n"
                f"path={path}\n"
                f"expected={expected_hash}\n"
                f"actual={actual_hash}"
            )


def clamp(
    value: float,
    minimum: float,
    maximum: float,
) -> float:
    """Clamp one numeric value into the documented classifier range."""

    return max(
        minimum,
        min(
            maximum,
            value,
        ),
    )


def build_boundary_targets() -> list[dict]:
    """
    Build cumulative percentile targets between adjacent V2 Leagues.

    Example:
    Crumb share 6% creates the first cumulative boundary at 6%.
    Cheese then extends the next cumulative boundary to 30%.
    """

    targets = []
    cumulative = 0.0

    for index in range(
        len(LEAGUES) - 1
    ):
        cumulative += LEAGUES[index]["share"]

        lower_share = LEAGUES[index]["share"]
        upper_share = LEAGUES[index + 1]["share"]

        adaptive_window = clamp(
            min(
                lower_share,
                upper_share,
            )
            * ADJACENT_SHARE_WINDOW_FACTOR,
            MIN_SEARCH_WINDOW_PP,
            MAX_SEARCH_WINDOW_PP,
        )

        targets.append(
            {
                "boundary_index": index,
                "target_percentile":
                    cumulative,
                "lower_league":
                    LEAGUES[index],
                "upper_league":
                    LEAGUES[index + 1],
                "search_window_pp":
                    adaptive_window,
            }
        )

    return targets


BOUNDARY_TARGETS = build_boundary_targets()


def build_power_groups(
    powers: list[int],
) -> list[dict]:
    """
    Group identical League Power values.

    DEVS FOR DECADES:
    Boundaries are considered only BETWEEN distinct Power groups.
    Equal-Power mice can therefore never be split across Leagues.
    """

    counts = Counter(
        int(power)
        for power in powers
    )

    return [
        {
            "power": power,
            "count": counts[power],
        }
        for power in sorted(counts)
    ]


def build_candidate_boundaries(
    powers: list[int],
) -> list[dict]:
    """
    Build every legal tie-safe natural-gap boundary candidate.
    """

    groups = build_power_groups(
        powers
    )

    if len(groups) < len(LEAGUES):
        raise RuntimeError(
            "REFUSED: population does not contain enough distinct "
            "Power groups for nine V2 Leagues."
        )

    population = len(powers)

    candidates = []

    cumulative_count = 0

    distinct_gaps = [
        groups[index + 1]["power"]
        - groups[index]["power"]
        for index in range(
            len(groups) - 1
        )
    ]

    for index in range(
        len(groups) - 1
    ):
        lower = groups[index]
        upper = groups[index + 1]

        cumulative_count += lower["count"]

        cumulative_percent = (
            cumulative_count
            * 100.0
            / population
        )

        gap = (
            upper["power"]
            - lower["power"]
        )

        neighboring_gaps = []

        start_index = max(
            0,
            index - LOCAL_GAP_RADIUS,
        )

        end_index = min(
            len(distinct_gaps),
            index
            + LOCAL_GAP_RADIUS
            + 1,
        )

        for neighbor_index in range(
            start_index,
            end_index,
        ):
            if neighbor_index == index:
                continue

            neighboring_gaps.append(
                distinct_gaps[
                    neighbor_index
                ]
            )

        local_median_gap = (
            statistics.median(
                neighboring_gaps
            )
            if neighboring_gaps
            else 1.0
        )

        safe_local_gap = max(
            1.0,
            float(local_median_gap),
        )

        gap_ratio = (
            float(gap)
            / safe_local_gap
        )

        gap_strength = math.log1p(
            gap_ratio
        )

        candidates.append(
            {
                "lower_power":
                    lower["power"],
                "upper_power":
                    upper["power"],
                "gap":
                    gap,
                "lower_count":
                    cumulative_count,
                "cumulative_percent":
                    cumulative_percent,
                "local_median_gap":
                    local_median_gap,
                "gap_ratio":
                    gap_ratio,
                "gap_strength":
                    gap_strength,
            }
        )

    return candidates


def choose_boundary(
    candidates: list[dict],
    boundary_target: dict,
    distance_weight: float,
) -> dict:
    """
    Choose one natural-gap boundary near its target percentile.

    Natural gaps receive positive weight.
    Distance from the target percentile receives a quadratic penalty.
    """

    target = boundary_target[
        "target_percentile"
    ]

    search_window = boundary_target[
        "search_window_pp"
    ]

    in_window = [
        candidate
        for candidate in candidates
        if abs(
            candidate[
                "cumulative_percent"
            ]
            - target
        )
        <= search_window
    ]

    if not in_window:
        nearest = min(
            candidates,
            key=lambda candidate: abs(
                candidate[
                    "cumulative_percent"
                ]
                - target
            ),
        )

        in_window = [
            nearest
        ]

    scored = []

    for candidate in in_window:
        percentile_error = abs(
            candidate[
                "cumulative_percent"
            ]
            - target
        )

        normalized_distance = (
            percentile_error
            / max(
                search_window,
                0.000001,
            )
        )

        distance_penalty = (
            distance_weight
            * (
                normalized_distance
                ** 2
            )
        )

        score = (
            candidate[
                "gap_strength"
            ]
            - distance_penalty
        )

        scored.append(
            {
                **candidate,
                "target_percentile":
                    target,
                "search_window_pp":
                    search_window,
                "percentile_error_pp":
                    percentile_error,
                "normalized_distance":
                    normalized_distance,
                "distance_penalty":
                    distance_penalty,
                "score":
                    score,
            }
        )

    scored.sort(
        key=lambda candidate: (
            -candidate["score"],
            candidate[
                "percentile_error_pp"
            ],
            -candidate["gap"],
            candidate["upper_power"],
        )
    )

    return scored[0]


def classify_distribution(
    powers: list[int],
    distance_weight: float,
) -> list[dict]:
    """
    Return the eight V2 natural-gap boundaries for nine Leagues.
    """

    candidates = (
        build_candidate_boundaries(
            powers
        )
    )

    boundaries = [
        choose_boundary(
            candidates,
            target,
            distance_weight,
        )
        for target in BOUNDARY_TARGETS
    ]

    thresholds = [
        boundary["upper_power"]
        for boundary in boundaries
    ]

    if thresholds != sorted(
        set(thresholds)
    ):
        raise RuntimeError(
            "REFUSED: invalid non-monotonic V2 League thresholds."
        )

    return boundaries


def build_ranges(
    boundaries: list[dict],
) -> list[dict]:
    """
    Convert eight V2 boundaries into nine public League ranges.
    """

    starts = [
        0
    ] + [
        boundary[
            "upper_power"
        ]
        for boundary in boundaries
    ]

    ranges = []

    for index, league in enumerate(
        LEAGUES
    ):
        minimum_power = starts[index]

        maximum_power = (
            starts[index + 1] - 1
            if index + 1
            < len(starts)
            else None
        )

        ranges.append(
            {
                "rank": index + 1,
                "key": league["key"],
                "label": league["label"],
                "symbol": league["symbol"],
                "target_share":
                    league["share"],
                "minimum_power":
                    minimum_power,
                "maximum_power":
                    maximum_power,
            }
        )

    return ranges


def load_v1_power_dataset() -> dict:
    """
    Execute the verified local V1 read-only League endpoint.

    V1 League classifications are ignored later.
    """

    if not PHP_EXE.exists():
        raise RuntimeError(
            f"REFUSED: PHP executable not found: {PHP_EXE}"
        )

    if not V1_API.exists():
        raise RuntimeError(
            f"REFUSED: V1 League API not found: {V1_API}"
        )

    result = subprocess.run(
        [
            str(PHP_EXE),
            str(V1_API),
        ],
        cwd=str(ROOT),
        capture_output=True,
        text=True,
        encoding="utf-8",
        errors="replace",
        check=False,
    )

    if result.returncode != 0:
        raise RuntimeError(
            "REFUSED: V1 League API execution failed.\n"
            f"exit={result.returncode}\n"
            f"stderr={result.stderr}"
        )

    try:
        payload = json.loads(
            result.stdout
        )
    except json.JSONDecodeError as error:
        raise RuntimeError(
            "REFUSED: V1 League API did not return valid JSON.\n"
            f"error={error}"
        ) from error

    if payload.get("success") is not True:
        raise RuntimeError(
            "REFUSED: V1 League API returned success != true."
        )

    if (
        payload.get("contract", {}).get("version")
        != "mousefight_genesis_leagues_v1"
    ):
        raise RuntimeError(
            "REFUSED: unexpected V1 League API contract."
        )

    mice = payload.get("mice")

    if not isinstance(
        mice,
        list,
    ) or not mice:
        raise RuntimeError(
            "REFUSED: V1 League API contains no mouse population."
        )

    return payload


def build_identity(
    mouse: dict,
) -> str:
    """Build canonical token_id + collection League identity."""

    token_id = str(
        mouse.get("token_id")
        or ""
    ).strip()

    collection = str(
        mouse.get("collection")
        or "genesis"
    ).strip()

    if not token_id:
        raise RuntimeError(
            "REFUSED: mouse row is missing token_id."
        )

    return (
        token_id
        + "|"
        + collection
    )


def calculate_progression(
    league_power: int,
    rank: int,
    tiers: list[dict],
    thresholds: list[int],
) -> dict:
    """
    Calculate one canonical progression meter.

    Frontend and Discord must consume this value rather than inventing
    independent progression formulas.
    """

    tier = tiers[
        rank - 1
    ]

    minimum_power = int(
        tier[
            "minimum_power"
        ]
    )

    if rank >= len(tiers):
        return {
            "current_power":
                league_power,
            "league_minimum":
                minimum_power,
            "league_maximum":
                None,
            "next_rank":
                None,
            "next_key":
                None,
            "next_label":
                None,
            "next_symbol":
                None,
            "next_threshold":
                None,
            "power_needed":
                0,
            "progress_percent":
                100.0,
        }

    next_tier = tiers[
        rank
    ]

    next_threshold = int(
        thresholds[
            rank - 1
        ]
    )

    span = max(
        1,
        next_threshold
        - minimum_power,
    )

    progress = (
        (
            league_power
            - minimum_power
        )
        * 100.0
        / span
    )

    progress = clamp(
        progress,
        0.0,
        100.0,
    )

    return {
        "current_power":
            league_power,
        "league_minimum":
            minimum_power,
        "league_maximum":
            tier[
                "maximum_power"
            ],
        "next_rank":
            next_tier[
                "rank"
            ],
        "next_key":
            next_tier[
                "key"
            ],
        "next_label":
            next_tier[
                "label"
            ],
        "next_symbol":
            next_tier[
                "symbol"
            ],
        "next_threshold":
            next_threshold,
        "power_needed":
            max(
                0,
                next_threshold
                - league_power,
            ),
        "progress_percent":
            round(
                progress,
                2,
            ),
    }


def build_snapshot(
    v1_payload: dict,
) -> dict:
    """
    Build first V2 baseline snapshot from verified permanent Power data.
    """

    source_mice = v1_payload[
        "mice"
    ]

    identity_rows = []
    identities = set()

    for mouse in source_mice:
        identity = build_identity(
            mouse
        )

        if identity in identities:
            raise RuntimeError(
                "REFUSED: duplicate token_id + collection identity: "
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

        identity_rows.append(
            {
                "identity":
                    identity,
                "league_power":
                    league_power,
            }
        )

    powers = [
        row[
            "league_power"
        ]
        for row in identity_rows
    ]

    boundaries = classify_distribution(
        powers,
        DISTANCE_WEIGHT,
    )

    thresholds = [
        int(
            boundary[
                "upper_power"
            ]
        )
        for boundary in boundaries
    ]

    tiers = build_ranges(
        boundaries
    )

    tier_summaries = []

    for tier in tiers:
        tier_summaries.append(
            {
                **deepcopy(
                    tier
                ),
                "named_mice": 0,
                "verified_current_owner_mice": 0,
                "fitness_unlocked_mice": 0,
                "default_mousefight_ready_mice": 0,
                "distinct_owner_ids": set(),
            }
        )

    output_mice = []

    total_owner_ids = set()
    total_ready = 0

    for source_mouse in source_mice:
        mouse = deepcopy(
            source_mouse
        )

        identity = build_identity(
            mouse
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

        rank = (
            bisect_right(
                thresholds,
                league_power,
            )
            + 1
        )

        tier = tiers[
            rank - 1
        ]

        next_tier = (
            tiers[
                rank
            ]
            if rank < len(
                tiers
            )
            else None
        )

        next_threshold = (
            thresholds[
                rank - 1
            ]
            if rank < len(
                tiers
            )
            else None
        )

        progression = calculate_progression(
            league_power,
            rank,
            tiers,
            thresholds,
        )

        mouse[
            "league"
        ] = {
            "rank":
                rank,
            "published_rank":
                rank,
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
                                - league_power,
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

        mouse[
            "movement"
        ] = {
            "status":
                "baseline",
            "previous_rank":
                None,
            "candidate_rank":
                rank,
            "published_rank":
                rank,
            "pending_rank":
                None,
            "pending_count":
                0,
        }

        output_mice.append(
            mouse
        )

        summary = tier_summaries[
            rank - 1
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

    threshold_power_counts = {
        str(threshold):
            powers.count(
                threshold
            )
        for threshold in thresholds
    }

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

    return {
        "success": True,

        "generated_at":
            generated_at,

        "contract": {
            "version":
                "mousefight_genesis_leagues_v2_shadow_baseline_v1",

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
                len(
                    LEAGUES
                ),

            "classifier": {
                "version":
                    "v2_percentile_natural_gap",
                "distance_weight":
                    DISTANCE_WEIGHT,
                "local_gap_radius":
                    LOCAL_GAP_RADIUS,
                "minimum_search_window_pp":
                    MIN_SEARCH_WINDOW_PP,
                "maximum_search_window_pp":
                    MAX_SEARCH_WINDOW_PP,
                "adjacent_share_window_factor":
                    ADJACENT_SHARE_WINDOW_FACTOR,
                "equal_power_split_allowed":
                    False,
            },

            "h2": {
                "status":
                    "configured_not_exercised_on_baseline",
                "move_trigger":
                    H2_MOVE_TRIGGER,
                "gap_ratio_trigger":
                    H2_GAP_RATIO_TRIGGER,
            },

            "i6": {
                "status":
                    "configured_not_exercised_on_baseline",
                "strategy":
                    "I6",
                "power_margin":
                    I6_POWER_MARGIN,
                "confirmation_snapshots":
                    INDIVIDUAL_CONFIRMATION_SNAPSHOTS,
            },

            "movement_semantics":
                "snapshot_001_is_baseline_no_v1_to_v2_movements",

            "genetic_items_included":
                False,

            "temporary_battle_mode_power_included":
                False,

            "fight_results_included_in_tier":
                False,
        },

        "source": {
            "v1_generated_at":
                v1_payload.get(
                    "generated_at"
                ),

            "research_source_hashes":
                deepcopy(
                    FROZEN_SOURCE_HASHES
                ),
        },

        "snapshot": {
            "sequence":
                1,

            "baseline":
                True,

            "population":
                len(
                    output_mice
                ),

            "raw_thresholds":
                thresholds,

            "published_thresholds":
                thresholds,

            "threshold_power_counts":
                threshold_power_counts,

            "boundaries":
                boundaries,
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
                [],
            "relegations":
                [],
            "pending":
                [],
            "baseline":
                len(
                    output_mice
                ),
        },
    }


def main() -> None:
    """
    Verify sources, build V2 baseline, validate, and write one new artifact.
    """

    if OUTPUT.exists():
        raise RuntimeError(
            f"REFUSED: output already exists: {OUTPUT}"
        )

    verify_frozen_research_sources()

    v1_payload = load_v1_power_dataset()

    snapshot = build_snapshot(
        v1_payload
    )

    if (
        snapshot[
            "summary"
        ][
            "named_mice"
        ]
        != len(
            snapshot[
                "mice"
            ]
        )
    ):
        raise RuntimeError(
            "REFUSED: summary population does not match mouse rows."
        )

    if len(
        snapshot[
            "tiers"
        ]
    ) != 9:
        raise RuntimeError(
            "REFUSED: V2 baseline does not contain exactly nine tiers."
        )

    thresholds = snapshot[
        "snapshot"
    ][
        "published_thresholds"
    ]

    if len(
        thresholds
    ) != 8:
        raise RuntimeError(
            "REFUSED: V2 baseline does not contain eight boundaries."
        )

    OUTPUT.write_text(
        json.dumps(
            snapshot,
            ensure_ascii=False,
            indent=2,
        )
        + "\n",
        encoding="utf-8",
    )

    print(
        "========================================"
    )
    print(
        " MOUSEFIGHT LEAGUES V2 — SHADOW BASELINE"
    )
    print(
        "========================================"
    )

    print(
        "STATUS: PASS"
    )

    print(
        "OUTPUT:",
        OUTPUT,
    )

    print(
        "population:",
        snapshot[
            "summary"
        ][
            "named_mice"
        ],
    )

    print(
        "owners:",
        snapshot[
            "summary"
        ][
            "distinct_owners"
        ],
    )

    print(
        "ready:",
        snapshot[
            "summary"
        ][
            "default_mousefight_ready_mice"
        ],
    )

    print(
        "power_range:",
        snapshot[
            "summary"
        ][
            "power_min"
        ],
        "-",
        snapshot[
            "summary"
        ][
            "power_max"
        ],
    )

    print(
        "thresholds:",
        snapshot[
            "snapshot"
        ][
            "published_thresholds"
        ],
    )

    print()
    print(
        "V2 TIER POPULATION"
    )

    for tier in snapshot[
        "summary"
    ][
        "tiers"
    ]:
        print(
            f"rank={tier['rank']} "
            f"key={tier['key']} "
            f"power={tier['minimum_power']}-"
            f"{tier['maximum_power']} "
            f"mice={tier['named_mice']} "
            f"owners={tier['distinct_owners']} "
            f"ready={tier['default_mousefight_ready_mice']}"
        )

    print()
    print(
        "DB writes: NONE"
    )

    print(
        "Production V1 changes: NONE"
    )

    print(
        "H2/I6 daily movement: NOT EXERCISED ON SNAPSHOT 001"
    )

    print(
        "SHA256:",
        sha256_file(
            OUTPUT
        ),
    )


if __name__ == "__main__":
    main()
