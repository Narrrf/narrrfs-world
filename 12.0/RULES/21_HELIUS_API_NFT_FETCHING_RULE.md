# 🔗 HELIUS API NFT FETCHING RULE - SOLANA NFT INTEGRATION

**STATUS:** ✅ **ACTIVE - CRITICAL PRODUCTION RULE**  
**CREATED:** December 29, 2025  
**PURPOSE:** Standardized method for fetching Solana NFTs and traits using Helius API  
**PRIORITY:** 🚨 **CRITICAL - MANDATORY FOR ALL NFT OPERATIONS**  

---

## 🎯 **RULE OVERVIEW**

### **CORE PRINCIPLE:**
**All Solana NFT fetching operations MUST use the Helius API with proper collection filtering, pagination, and metadata fetching. This rule ensures consistent NFT display and role granting across all pages.**

### **RULE SCOPE:**
- **NFT Fetching** - Using Helius API to get wallet NFTs
- **Collection Filtering** - Matching NFTs to specific collections
- **Metadata Fetching** - Getting images and traits from metadataUri
- **Role Granting** - Using NFT ownership to grant Discord roles
- **Local Development** - Config file support for API keys

---

## 🔧 **MANDATORY API ENDPOINT**

### **Primary Endpoint: `/api/wallet/get-nfts.php`**

**Purpose:** Fetch NFTs from a Solana wallet for a specific collection

**Request Parameters:**
- `wallet` (required) - Solana wallet address
- `collection` (required) - Collection address (e.g., `AtJCkW4as31C7cF4zQbZdvTt488ejUuacgynZpohVmML`)

**Response Structure:**
```json
{
  "success": true,
  "nfts": [
    {
      "name": "NFT Name",
      "mint": "mint_address",
      "image": "image_url",
      "collection": "collection_address",
      "collectionAddress": "collection_address",
      "attributes": [
        {
          "trait_type": "Trait Name",
          "value": "Trait Value"
        }
      ],
      "metadataUri": "https://arweave.net/..."
    }
  ],
  "count": 14,
  "has_assets": true,
  "method": "helius_enhanced_api",
  "wallet": "wallet_address",
  "collection": "collection_address"
}
```

**Usage:**
```javascript
const response = await fetch(`/api/wallet/get-nfts.php?wallet=${walletAddress}&collection=${collectionAddress}`);
const data = await response.json();
if (data.success && data.nfts && Array.isArray(data.nfts) && data.nfts.length > 0) {
  // Process NFTs
}
```

---

## 🎯 **COLLECTION ADDRESSES**

### **VIP Collection:**
- **Address:** `CUJH8MV68154vS8wTW15vAKxN6KazNpraFZ1FP8CVojg`
- **Role:** `🎴 VIP Holder`
- **Role ID:** `1332016526848692345`
- **Theme:** Golden (yellow/gold colors)

### **Genesis Collection:**
- **Address:** `AtJCkW4as31C7cF4zQbZdvTt488ejUuacgynZpohVmML`
- **Role:** `🏆 Holder`
- **Role ID:** `1402668301414563971`
- **Theme:** Blue (blue/cyan colors)

---

## 🔧 **HELIUS API INTEGRATION PATTERN**

### **✅ CORRECT PATTERN: getAssetsByOwner (DAS API)**

```php
// Use getAssetsByOwner (DAS API) with pagination
$url = "https://api.helius.xyz/v0/addresses/{$walletAddress}/nfts?api-key={$apiKey}";

// For collection filtering, use grouping parameter
$url = "https://api.helius.xyz/v0/addresses/{$walletAddress}/nfts?api-key={$apiKey}&collection={$collectionAddress}";

// With pagination
$page = 1;
$limit = 1000;
$allNFTs = [];

do {
    $url = "https://api.helius.xyz/v0/addresses/{$walletAddress}/nfts?api-key={$apiKey}&collection={$collectionAddress}&page={$page}&limit={$limit}";
    $response = fetchHeliusAPI($url);
    $nfts = $response['result'] ?? [];
    $allNFTs = array_merge($allNFTs, $nfts);
    $page++;
} while (count($nfts) === $limit);
```

### **❌ WRONG PATTERNS (DO NOT USE):**
- ❌ `getTokenAccountsByOwner` (RPC) - Doesn't provide collection filtering or metadata
- ❌ Direct Helius Enhanced API without pagination - May miss NFTs
- ❌ Hardcoded collection names - Always use collection addresses

---

## 📊 **COLLECTION FILTERING LOGIC**

### **✅ CORRECT FILTERING PATTERN:**

```php
// 1. Primary: Filter by collectionKey from grouping field
$collectionKey = $nft['grouping'][0]['group_value'] ?? null;
if ($collectionKey === $collectionAddress) {
    // NFT belongs to requested collection
}

// 2. Fallback: Filter by name containing "Narrrf" or "Narrrfs"
$nftName = $nft['content']['metadata']['name'] ?? '';
if (stripos($nftName, 'Narrrf') !== false || stripos($nftName, 'Narrrfs') !== false) {
    // NFT is from Narrrf collection
}

// 3. Final check: Verify collection address matches
$nftCollection = $nft['grouping'][0]['group_value'] ?? 
                  $nft['collection'] ?? 
                  $nft['collectionAddress'] ?? '';
if ($nftCollection === $collectionAddress) {
    // Confirmed match
}
```

### **Collection Matching Priority:**
1. **CollectionKey from grouping field** (most reliable)
2. **Collection address from NFT metadata** (fallback)
3. **Name-based matching** (last resort for edge cases)

---

## 🖼️ **METADATA FETCHING PATTERN**

### **✅ CORRECT METADATA FETCHING:**

```javascript
// Frontend: Fetch metadata from metadataUri for images and traits
async function fetchNFTMetadata(metadataUri) {
  try {
    const response = await fetch(metadataUri);
    const metadata = await response.json();
    return {
      image: metadata.image || metadata.imageUri || null,
      attributes: metadata.attributes || metadata.properties?.attributes || []
    };
  } catch (error) {
    console.error('Failed to fetch metadata:', error);
    return { image: null, attributes: [] };
  }
}

// Use Promise.all for parallel fetching
const metadataPromises = nfts.map(nft => {
  if (!nft.image && nft.metadataUri) {
    return fetchNFTMetadata(nft.metadataUri).then(metadata => ({
      ...nft,
      image: metadata.image || nft.image,
      attributes: metadata.attributes || nft.attributes || []
    }));
  }
  return Promise.resolve(nft);
});

const nftsWithMetadata = await Promise.all(metadataPromises);
```

### **Image URL Priority:**
1. `nft.image` (direct image URL)
2. `metadata.image` (from metadataUri)
3. `metadata.imageUri` (alternative field)
4. Placeholder image (fallback)

---

## 🎨 **VISUAL DIFFERENTIATION PATTERN**

### **✅ CORRECT VIP vs GENESIS DISPLAY:**

```javascript
// Determine if NFT is VIP or Genesis by collection address
const vipCollectionAddress = 'CUJH8MV68154vS8wTW15vAKxN6KazNpraFZ1FP8CVojg';
const genesisCollectionAddress = 'AtJCkW4as31C7cF4zQbZdvTt488ejUuacgynZpohVmML';

const nftCollection = nft.collection || nft.collectionAddress || '';
const isVIP = nftCollection === vipCollectionAddress || 
              (nft.collectionRole && nft.collectionRole.includes('VIP')) ||
              (nft.collectionName && nft.collectionName.includes('VIP'));
const isGenesis = !isVIP && (nftCollection === genesisCollectionAddress || 
                             (nft.collectionRole && nft.collectionRole.includes('Holder')) ||
                             (nft.collectionName && nft.collectionName.includes('Genesis')));

// Apply styling based on collection type
const cardClasses = isVIP 
  ? 'border-yellow-400/30 hover:border-yellow-400/60 shadow-yellow-500/20'
  : 'border-blue-400/30 hover:border-blue-400/60 shadow-blue-500/20';

const textColor = isVIP ? 'text-yellow-100' : 'text-blue-100';
const badgeBg = isVIP ? 'bg-yellow-500/20' : 'bg-blue-500/20';
const badgeText = isVIP ? 'text-yellow-300' : 'text-blue-300';
```

---

## 🔐 **ROLE GRANTING PATTERN**

### **✅ CORRECT ROLE GRANTING:**

```php
// Use get-nfts.php API for consistent collection matching
function fetchCollectionNFTCountViaAPI(string $walletAddress, string $collectionAddress): int
{
    $isProduction = strpos($_SERVER['HTTP_HOST'] ?? '', 'narrrfs.world') !== false;
    $apiBaseUrl = $isProduction ? 'https://narrrfs.world' : 'http://localhost';
    $apiUrl = "{$apiBaseUrl}/api/wallet/get-nfts.php?wallet=" . urlencode($walletAddress) . "&collection=" . urlencode($collectionAddress);
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data['success'] && isset($data['nfts'])) {
            return count($data['nfts']);
        }
    }
    return 0;
}

// Collection to role mapping
$collectionsConfig = [
    'AtJCkW4as31C7cF4zQbZdvTt488ejUuacgynZpohVmML' => [
        'name' => 'Narrrfs World: Genesis Genetic',
        'role_id' => '1402668301414563971',
        'role_name' => '🏆 Holder'
    ],
    'CUJH8MV68154vS8wTW15vAKxN6KazNpraFZ1FP8CVojg' => [
        'name' => 'Narrrf Genesis VIP Drop',
        'role_id' => '1332016526848692345',
        'role_name' => '🎴 VIP Holder'
    ]
];

// Grant role based on collection address
foreach ($collectionsConfig as $collectionAddress => $info) {
    $nftCount = fetchCollectionNFTCountViaAPI($walletAddress, $collectionAddress);
    if ($nftCount > 0) {
        grantDiscordRole($userId, $username, $info['role_id'], $info['role_name']);
    }
}
```

### **CRITICAL RULES:**
1. **ALWAYS use collection address** (not role names) for API calls
2. **ALWAYS use get-nfts.php** for role granting (ensures consistency)
3. **NEVER grant roles based on role names** - use collection address mapping
4. **VERIFY collection address** matches before granting role

---

## 🛠️ **LOCAL DEVELOPMENT SETUP**

### **✅ CORRECT LOCAL CONFIG:**

**File:** `api/config/helius-api-key.php`
```php
<?php
// Local development Helius API key
// This file is excluded from git (.gitignore)
// Production uses environment variable HELIUS_API_KEY
$HELIUS_API_KEY = 'your-helius-api-key-here';
?>
```

**Usage in API files:**
```php
// Check for local config file
$isLocalhost = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
               strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;

$apiKey = '';
if ($isLocalhost) {
    // Try local config file first
    $localConfigPath = __DIR__ . '/../config/helius-api-key.php';
    if (file_exists($localConfigPath)) {
        include $localConfigPath;
        $apiKey = $HELIUS_API_KEY ?? '';
    }
}

// Fallback to environment variable
if (!$apiKey) {
    $apiKey = getenv('HELIUS_API_KEY') ?: ($_ENV['HELIUS_API_KEY'] ?? $_SERVER['HELIUS_API_KEY'] ?? '');
}
```

---

## 📋 **IMPLEMENTATION CHECKLIST**

### **When Fetching NFTs:**
- [ ] Use `getAssetsByOwner` (DAS API) with pagination
- [ ] Filter by collection address using `collectionKey` from grouping field
- [ ] Implement name-based fallback if collection filtering fails
- [ ] Fetch metadata from `metadataUri` for images and traits
- [ ] Handle missing images with placeholder
- [ ] Use collection address (not role names) for all operations

### **When Displaying NFTs:**
- [ ] Determine VIP vs Genesis by collection address
- [ ] Apply visual differentiation (golden for VIP, blue for Genesis)
- [ ] Display traits from metadata
- [ ] Show collection badges
- [ ] Handle loading states and errors

### **When Granting Roles:**
- [ ] Use `get-nfts.php` API for consistency
- [ ] Map collection address to role (not role names)
- [ ] Verify NFT count > 0 before granting
- [ ] Log role grants for audit trail
- [ ] Handle errors gracefully

---

## 🚨 **CRITICAL RULES TO NEVER VIOLATE**

### **❌ NEVER DO:**
- **Use role names for API calls** - Always use collection addresses
- **Skip pagination** - May miss NFTs in large wallets
- **Hardcode collection names** - Always use collection addresses
- **Grant roles based on role names** - Use collection address mapping
- **Skip metadata fetching** - Images and traits come from metadataUri
- **Use RPC fallback without metadata** - Always fetch full metadata

### **✅ ALWAYS DO:**
- **Use collection addresses** for all NFT operations
- **Implement pagination** for getAssetsByOwner
- **Filter by collectionKey** from grouping field
- **Fetch metadata from metadataUri** for images and traits
- **Use get-nfts.php** for role granting (consistency)
- **Map collection address to role** (not role names)
- **Visual differentiation** for VIP vs Genesis NFTs
- **Local config support** for development

---

## 📚 **FILE REFERENCES**

### **API Files:**
- `api/wallet/get-nfts.php` - Primary NFT fetching endpoint
- `api/user/verify-nft-holder.php` - Role granting endpoint (uses get-nfts.php)
- `api/config/helius-api-key.php` - Local development config

### **Frontend Files:**
- `public/stake-lab.html` - NFT display with VIP/Genesis differentiation
- `public/profile.html` - NFT verification and role granting

### **Documentation:**
- `12.0/RULES/21_HELIUS_API_NFT_FETCHING_RULE.md` - This rule document

---

## 🎯 **QUICK REFERENCE**

### **Fetch NFTs:**
```javascript
const response = await fetch(`/api/wallet/get-nfts.php?wallet=${wallet}&collection=${collectionAddress}`);
const data = await response.json();
```

### **Determine Collection Type:**
```javascript
const isVIP = nft.collection === 'CUJH8MV68154vS8wTW15vAKxN6KazNpraFZ1FP8CVojg';
const isGenesis = nft.collection === 'AtJCkW4as31C7cF4zQbZdvTt488ejUuacgynZpohVmML';
```

### **Grant Role:**
```php
$nftCount = fetchCollectionNFTCountViaAPI($wallet, $collectionAddress);
if ($nftCount > 0) {
    grantDiscordRole($userId, $username, $roleId, $roleName);
}
```

---

## 🧀 **FINAL MANDATE**

### **THIS RULE IS NON-NEGOTIABLE:**
- **Every NFT operation** MUST use Helius API with proper collection filtering
- **Every role grant** MUST use collection address mapping (not role names)
- **Every NFT display** MUST show visual differentiation for VIP vs Genesis
- **Every metadata fetch** MUST handle metadataUri for images and traits

### **THE ULTIMATE GOAL:**
**Ensure all NFT operations are consistent, reliable, and correctly match collections to roles for decades of reliable NFT integration.**

---

**RULE CREATED:** December 29, 2025  
**STATUS:** ✅ **ACTIVE - CRITICAL PRODUCTION RULE**  
**PURPOSE:** Standardized Helius API NFT Fetching  
**SCOPE:** All NFT operations, all role granting, all NFT displays  

**🔗 THIS RULE ENSURES DECADES OF RELIABLE SOLANA NFT INTEGRATION! 🔗**

