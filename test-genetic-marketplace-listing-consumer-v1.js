const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const labPath = path.join(
  __dirname,
  'public',
  'lab.html'
);

const source = fs.readFileSync(
  labPath,
  'utf8'
);

/*
 * DEVS FOR DECADES:
 * This test protects both Genetic Marketplace presentation lanes:
 *
 * 1. Anonymous/public users receive metadata-only listing previews.
 * 2. Authenticated users receive the protected trading context needed for
 *    create/cancel/buy without reopening /api/admin/*.
 *
 * The backend remains authoritative for identity, price, DSPOINC, listing
 * state, Genetic Item ownership and transfer.
 */

const inlineScripts = [
  ...source.matchAll(
    /<script(?![^>]*\bsrc=)[^>]*>([\s\S]*?)<\/script>/gi
  )
];

assert.ok(
  inlineScripts.length > 0,
  'Expected inline Lab scripts'
);

inlineScripts.forEach((match, index) => {
  new vm.Script(
    match[1],
    {
      filename:
        `public/lab.html:inline-script-${index + 1}`
    }
  );
});

function sliceBetween(startMarker, endMarker) {
  const start = source.indexOf(startMarker);

  const end = source.indexOf(
    endMarker,
    start + startMarker.length
  );

  assert.notStrictEqual(
    start,
    -1,
    `Missing start marker: ${startMarker}`
  );

  assert.notStrictEqual(
    end,
    -1,
    `Missing end marker: ${endMarker}`
  );

  return source.slice(
    start,
    end
  );
}

/* -------------------------------------------------------
 * Endpoint contract
 * ----------------------------------------------------- */

assert.match(
  source,
  /geneticMarketplaceListingsEndpoint:\s*'\/api\/marketplace\/v1\/genetic-listings\.php'/
);

assert.match(
  source,
  /geneticMarketplacePlayerContextEndpoint:\s*'\/api\/marketplace\/v1\/player-context\.php'/
);

assert.match(
  source,
  /geneticMarketplaceCreateEndpoint:\s*'\/api\/marketplace\/v1\/create-listing\.php'/
);

assert.match(
  source,
  /geneticMarketplaceCancelEndpoint:\s*'\/api\/marketplace\/v1\/cancel-listing\.php'/
);

assert.match(
  source,
  /geneticMarketplaceBuyEndpoint:\s*'\/api\/marketplace\/v1\/buy-listing\.php'/
);

assert.match(
  source,
  /geneticMarketplaceHistoryEndpoint:\s*'\/api\/user\/get-genetic-marketplace-history\.php'/
);

/*
 * Emergency /api/admin Marketplace routes must stay absent.
 */
assert.doesNotMatch(
  source,
  /\/api\/admin\/(?:get|create|cancel|buy)-genetic-marketplace/
);

/* -------------------------------------------------------
 * Dual-lane loader
 * ----------------------------------------------------- */

const loader = sliceBetween(
  'async function refreshGeneticMarketplaceData()',
  'function buildGeneticInventoryRuntimeSummary'
);

assert.match(
  loader,
  /const fetchMarketplaceJson = async/
);

assert.match(
  loader,
  /fetch\(endpoint,\s*\{/
);

assert.match(
  loader,
  /method:\s*'GET'/
);

assert.match(
  loader,
  /cache:\s*'no-store'/
);

/*
 * Authenticated Marketplace context deliberately carries
 * the trusted PHP session.
 */
assert.match(
  loader,
  /fetchMarketplaceJson\(\s*LAB_CONFIG\.geneticMarketplacePlayerContextEndpoint,\s*true\s*\)/s
);

/*
 * Public metadata preview deliberately omits credentials.
 */
assert.match(
  loader,
  /fetchMarketplaceJson\(\s*LAB_CONFIG\.geneticMarketplaceListingsEndpoint,\s*false\s*\)/s
);

assert.match(
  loader,
  /credentials:\s*includeCredentials\s*\?\s*'include'\s*:\s*'omit'/
);

assert.match(
  loader,
  /state\.geneticMarketplaceCsrfToken\s*=\s*csrfToken/
);

assert.match(
  loader,
  /state\.geneticMarketplaceTradingEnabled\s*=\s*true/
);

assert.doesNotMatch(
  loader,
  /\/api\/admin\//
);

/* -------------------------------------------------------
 * Anonymous metadata-only presentation
 * ----------------------------------------------------- */

const previewTableRow = sliceBetween(
  'function renderGeneticMarketplacePreviewTableRow(item)',
  'function renderGeneticMarketplaceTradingTableRow(item)'
);

const previewCard = sliceBetween(
  'function renderGeneticMarketplacePreviewCard(item)',
  'function renderGeneticMarketplaceTradingCard(item)'
);

const previewListingView = sliceBetween(
  'function renderGeneticMarketplacePreview()',
  'function renderGeneticMarketplaceTrading()'
);

const previewSidePanel = sliceBetween(
  'function renderGeneticMarketplacePreviewSidePanel()',
  'function renderGeneticMarketplaceTradingSidePanel()'
);

for (
  const [label, consumer]
  of Object.entries({
    previewTableRow,
    previewCard,
    previewListingView
  })
) {
  for (
    const forbidden
    of [
      'price_dspoinc',
      'seller_user_id',
      'seller_username',
      'genetic_item_id',
      'data-buy-genetic-marketplace-listing',
      'renderGeneticMarketplaceBuyAction',
      'Buy Now',
      '>Price<',
      '>Seller<',
      '>Actions<'
    ]
  ) {
    assert.ok(
      !consumer.includes(forbidden),
      `${label} must not expose or render ${forbidden}`
    );
  }
}

for (
  const allowed
  of [
    'listing_ref',
    'display_title',
    'trait_type',
    'trait_value',
    'rarity_tier'
  ]
) {
  assert.ok(
    previewTableRow.includes(allowed) ||
      previewCard.includes(allowed),
    `Missing public metadata field: ${allowed}`
  );
}

assert.ok(
  previewCard.includes('preview_path') &&
    previewCard.includes('image_path'),
  'Public card must use allowlisted preview/image paths'
);

assert.match(
  previewSidePanel,
  /metadata-only/
);

assert.match(
  previewSidePanel,
  /Create, cancel, and buy remain unavailable/
);

/* -------------------------------------------------------
 * Authenticated trading presentation
 * ----------------------------------------------------- */

const tradingTableRow = sliceBetween(
  'function renderGeneticMarketplaceTradingTableRow(item)',
  'function renderGeneticMarketplaceTableRow(item)'
);

const tradingCard = sliceBetween(
  'function renderGeneticMarketplaceTradingCard(item)',
  'function renderGeneticMarketplaceCard(item)'
);

const buyAction = sliceBetween(
  'function renderGeneticMarketplaceBuyAction(item)',
  'async function handleBuyGeneticMarketplaceListing(listingId)'
);

assert.match(
  tradingTableRow,
  /price_dspoinc/
);

assert.match(
  tradingTableRow,
  /seller_username/
);

assert.match(
  tradingTableRow,
  /renderGeneticMarketplaceBuyAction/
);

assert.match(
  tradingCard,
  /price_dspoinc/
);

assert.match(
  tradingCard,
  /seller_username/
);

assert.match(
  tradingCard,
  /renderGeneticMarketplaceBuyAction/
);

assert.match(
  buyAction,
  /is_own_listing/
);

assert.match(
  buyAction,
  /data-buy-genetic-marketplace-listing/
);

assert.match(
  buyAction,
  /Buy Now/
);

/* -------------------------------------------------------
 * Protected mutation client contract
 * ----------------------------------------------------- */

const mutationHeaders = sliceBetween(
  'function getGeneticMarketplaceMutationHeaders()',
  'async function createGeneticMarketplaceListing'
);

assert.match(
  mutationHeaders,
  /geneticMarketplaceCsrfToken/
);

assert.match(
  mutationHeaders,
  /geneticMarketplaceTradingEnabled/
);

assert.match(
  mutationHeaders,
  /'X-CSRF-Token':\s*csrfToken/
);

const createListing = sliceBetween(
  'async function createGeneticMarketplaceListing',
  'async function cancelGeneticMarketplaceListing'
);

assert.match(
  createListing,
  /LAB_CONFIG\.geneticMarketplaceCreateEndpoint/
);

assert.match(
  createListing,
  /getGeneticMarketplaceMutationHeaders\(\)/
);

const cancelListing = sliceBetween(
  'async function cancelGeneticMarketplaceListing',
  'async function refreshGeneticMarketplaceData'
);

assert.match(
  cancelListing,
  /LAB_CONFIG\.geneticMarketplaceCancelEndpoint/
);

assert.match(
  cancelListing,
  /getGeneticMarketplaceMutationHeaders\(\)/
);

const buyListing = sliceBetween(
  'async function handleBuyGeneticMarketplaceListing(listingId)',
  'function renderGeneticMarketplacePreviewCard(item)'
);

assert.match(
  buyListing,
  /LAB_CONFIG\.geneticMarketplaceBuyEndpoint/
);

assert.match(
  buyListing,
  /getGeneticMarketplaceMutationHeaders\(\)/
);

assert.match(
  buyListing,
  /is_own_listing/
);

/* -------------------------------------------------------
 * Final boundary checks
 * ----------------------------------------------------- */

assert.doesNotMatch(
  createListing,
  /\/api\/admin\//
);

assert.doesNotMatch(
  cancelListing,
  /\/api\/admin\//
);

assert.doesNotMatch(
  buyListing,
  /\/api\/admin\//
);

console.log(
  'PASS: Genetic Marketplace dual-lane consumer contract — ' +
  'anonymous metadata preview + authenticated protected trading'
);