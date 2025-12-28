# 🎨 NFT Display Feature Plan for stake-lab.html

**Created:** December 29, 2025  
**Status:** 📋 **PLANNING**  
**Purpose:** Add NFT collection display feature to stake-lab.html page  

---

## 🎯 **FEATURE OVERVIEW**

Add an NFT collection display section at the bottom of `stake-lab.html` that:
1. Allows users to connect their Phantom wallet
2. Fetches NFTs from the two Narrrfs World collections using Helius API
3. Displays NFT images in a grid layout (similar to realmkins website)
4. Shows NFT names, traits, and collection information

---

## 📋 **IMPLEMENTATION PLAN**

### **PHASE 1: API ENHANCEMENT (Backend)**

#### **1.1 Update `/api/wallet/get-nfts.php` to Return Full NFT Data**

**Current State:**
- API returns NFT count and basic data
- Does NOT include image URLs or full metadata

**Required Changes:**
- Return full NFT objects from Helius API response
- Include image URLs (from `content.links.image` or `content.files[0].uri`)
- Include metadata (name, description, attributes/traits)
- Include mint address for each NFT

**Response Structure:**
```json
{
  "success": true,
  "nfts": [
    {
      "mint": "mint_address",
      "name": "NFT Name",
      "image": "https://...",
      "collection": "collection_name",
      "collectionAddress": "collection_address",
      "attributes": [
        {"trait_type": "Background", "value": "Blue"},
        {"trait_type": "Hat", "value": "Crown"}
      ],
      "description": "NFT description"
    }
  ],
  "count": 5,
  "method": "helius_enhanced_api"
}
```

**OR** create new endpoint: `/api/wallet/get-nft-details.php` for full NFT data

---

### **PHASE 2: Frontend Implementation (stake-lab.html)**

#### **2.1 Add Wallet Connection Section**

**Location:** Add new section before footer (after Stakes Tab System)

**HTML Structure:**
```html
<!-- 🎴 NFT Collection Display Section -->
<section class="relative z-10 px-4 md:px-6 pb-16">
  <div class="max-w-7xl mx-auto">
    <div class="bg-black/50 backdrop-blur-md rounded-xl p-6 border border-purple-400/30">
      <h2 class="text-2xl font-bold text-purple-400 mb-6">🎴 My NFT Collection</h2>
      
      <!-- Wallet Connection Status -->
      <div id="nft-wallet-status" class="hidden bg-gradient-to-br from-purple-900/30 via-purple-800/20 to-purple-900/30 rounded-lg p-4 mb-4 border border-purple-400/30">
        <div class="flex justify-between items-center">
          <div>
            <span class="text-purple-300">✅ Wallet Connected</span>
            <div id="nft-wallet-address" class="text-purple-200 text-sm mt-1"></div>
          </div>
          <button id="nft-wallet-disconnect" onclick="disconnectNFTWallet()" class="text-sm bg-red-500 hover:bg-red-600 text-white rounded-full px-3 py-1 shadow">
            Disconnect
          </button>
        </div>
      </div>
      
      <!-- Connect Wallet Button -->
      <button id="connect-nft-wallet-btn" onclick="connectNFTWallet()" 
              class="bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white px-6 py-3 rounded-lg font-semibold flex items-center justify-center gap-2 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 mb-6">
        <span>🔗</span> Connect Phantom Wallet to View NFTs
      </button>
      
      <!-- Loading State -->
      <div id="nft-loading" class="hidden text-center py-8">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-400 mx-auto"></div>
        <p class="mt-4 text-purple-300">Loading your NFTs...</p>
      </div>
      
      <!-- NFT Gallery Grid -->
      <div id="nft-gallery" class="hidden">
        <div id="nft-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <!-- NFT cards will be dynamically inserted here -->
        </div>
        <p id="no-nfts" class="text-center text-gray-400 py-8 hidden">
          No NFTs found in your wallet from our collections.
        </p>
      </div>
    </div>
  </div>
</section>
```

---

#### **2.2 JavaScript Implementation**

**Wallet Connection Function:**
```javascript
// NFT Wallet Connection State
let nftConnectedWallet = null;
let nftConnectedWalletAddress = null;
let userNFTs = [];

// Connect to Phantom wallet for NFT display
async function connectNFTWallet() {
  try {
    // Check if Phantom is installed
    if (!window.solana || !window.solana.isPhantom) {
      alert('Phantom wallet is not installed. Please install it from https://phantom.app/');
      return;
    }

    // Connect to wallet
    const response = await window.solana.connect();
    nftConnectedWallet = window.solana;
    nftConnectedWalletAddress = response.publicKey.toString();
    
    // Update UI
    document.getElementById('nft-wallet-status').classList.remove('hidden');
    document.getElementById('nft-wallet-address').textContent = `Address: ${nftConnectedWalletAddress.substring(0, 4)}...${nftConnectedWalletAddress.substring(nftConnectedWalletAddress.length - 4)}`;
    document.getElementById('connect-nft-wallet-btn').classList.add('hidden');
    
    // Load NFTs
    await loadUserNFTs();
    
  } catch (error) {
    console.error('NFT Wallet connection error:', error);
    alert('Failed to connect wallet: ' + error.message);
  }
}

// Disconnect NFT wallet
function disconnectNFTWallet() {
  if (nftConnectedWallet) {
    nftConnectedWallet.disconnect();
    nftConnectedWallet = null;
    nftConnectedWalletAddress = null;
    userNFTs = [];
    
    // Update UI
    document.getElementById('nft-wallet-status').classList.add('hidden');
    document.getElementById('connect-nft-wallet-btn').classList.remove('hidden');
    document.getElementById('nft-gallery').classList.add('hidden');
  }
}

// Load user NFTs from both collections
async function loadUserNFTs() {
  if (!nftConnectedWalletAddress) {
    console.warn('No wallet connected for NFT loading');
    return;
  }
  
  try {
    document.getElementById('nft-loading').classList.remove('hidden');
    document.getElementById('nft-gallery').classList.add('hidden');
    
    const collections = {
      'AtJCkW4as31C7cF4zQbZdvTt488ejUuacgynZpohVmML': {
        name: 'Narrrfs World: Genesis Genetic',
        role: '🏆 Holder'
      },
      'CUJH8MV68154vS8wTW15vAKxN6KazNpraFZ1FP8CVojg': {
        name: 'Narrrf Genesis VIP Drop',
        role: '🎴 VIP Holder'
      }
    };
    
    const allNFTs = [];
    
    // Fetch NFTs from each collection
    for (const [collectionAddress, collectionInfo] of Object.entries(collections)) {
      try {
        const response = await fetch(`${API_BASE_URL}/api/wallet/get-nfts.php?wallet=${nftConnectedWalletAddress}&collection=${collectionAddress}`);
        
        if (!response.ok) continue;
        
        const data = await response.json();
        
        if (data.success && data.nfts && Array.isArray(data.nfts)) {
          // Process each NFT to extract image and metadata
          data.nfts.forEach(nft => {
            // Extract image URL (try multiple possible fields)
            const imageUrl = nft.content?.links?.image || 
                           nft.content?.files?.[0]?.uri || 
                           nft.image || 
                           nft.content_uri ||
                           nft.offChainMetadata?.metadata?.image ||
                           'https://via.placeholder.com/300?text=NFT';
            
            // Extract name
            const name = nft.content?.metadata?.name || 
                        nft.name || 
                        nft.content?.metadata?.title ||
                        'Unknown NFT';
            
            // Extract attributes/traits
            const attributes = nft.content?.metadata?.attributes || 
                             nft.attributes || 
                             nft.offChainMetadata?.metadata?.attributes ||
                             [];
            
            allNFTs.push({
              mint: nft.mint || nft.id,
              name: name,
              image: imageUrl,
              collection: collectionInfo.name,
              collectionAddress: collectionAddress,
              role: collectionInfo.role,
              attributes: attributes,
              description: nft.content?.metadata?.description || ''
            });
          });
        }
      } catch (error) {
        console.warn(`Error fetching NFTs from ${collectionInfo.name}:`, error);
      }
    }
    
    userNFTs = allNFTs;
    displayNFTs(allNFTs);
    
  } catch (error) {
    console.error('Error loading NFTs:', error);
    alert('Failed to load NFTs: ' + error.message);
  } finally {
    document.getElementById('nft-loading').classList.add('hidden');
  }
}

// Display NFTs in grid
function displayNFTs(nfts) {
  const grid = document.getElementById('nft-grid');
  const gallery = document.getElementById('nft-gallery');
  const noNFTs = document.getElementById('no-nfts');
  
  if (!nfts || nfts.length === 0) {
    gallery.classList.remove('hidden');
    grid.innerHTML = '';
    noNFTs.classList.remove('hidden');
    return;
  }
  
  noNFTs.classList.add('hidden');
  gallery.classList.remove('hidden');
  
  grid.innerHTML = nfts.map(nft => `
    <div class="nft-card bg-gray-800/50 rounded-xl p-4 border border-purple-400/30 hover:border-purple-400 transition-all cursor-pointer transform hover:scale-105">
      <div class="relative">
        <img src="${nft.image}" 
             alt="${nft.name}" 
             class="w-full h-64 object-cover rounded-lg mb-3"
             onerror="this.src='https://via.placeholder.com/300?text=NFT+Image'">
        <div class="absolute top-2 right-2 bg-purple-600/80 text-white text-xs px-2 py-1 rounded-full">
          ${nft.role}
        </div>
      </div>
      <h3 class="text-white font-semibold text-sm mb-1 truncate">${nft.name}</h3>
      <p class="text-purple-300 text-xs mb-2">${nft.collection}</p>
      ${nft.attributes && nft.attributes.length > 0 ? `
        <div class="text-xs text-gray-400">
          ${nft.attributes.slice(0, 3).map(attr => `
            <div>${attr.trait_type || attr.name}: <span class="text-purple-300">${attr.value}</span></div>
          `).join('')}
          ${nft.attributes.length > 3 ? `<div class="text-purple-400 mt-1">+${nft.attributes.length - 3} more traits</div>` : ''}
        </div>
      ` : ''}
    </div>
  `).join('');
}

// Check for existing wallet connection on page load
function checkExistingNFTWalletConnection() {
  if (window.solana && window.solana.isPhantom && window.solana.isConnected) {
    // Wallet already connected
    nftConnectedWallet = window.solana;
    nftConnectedWalletAddress = window.solana.publicKey.toString();
    document.getElementById('nft-wallet-status').classList.remove('hidden');
    document.getElementById('nft-wallet-address').textContent = `Address: ${nftConnectedWalletAddress.substring(0, 4)}...${nftConnectedWalletAddress.substring(nftConnectedWalletAddress.length - 4)}`;
    document.getElementById('connect-nft-wallet-btn').classList.add('hidden');
    loadUserNFTs();
  }
}
```

---

#### **2.3 CSS Styling**

Add to existing `<style>` section:
```css
.nft-card {
  transition: all 0.3s ease;
  background: linear-gradient(135deg, rgba(147, 51, 234, 0.1), rgba(147, 51, 234, 0.05));
}

.nft-card:hover {
  transform: translateY(-5px) scale(1.02);
  box-shadow: 0 20px 40px rgba(147, 51, 234, 0.3);
  border-color: rgba(147, 51, 234, 0.5);
}

.nft-card img {
  border-radius: 0.5rem;
  object-fit: cover;
}
```

---

### **PHASE 3: API Enhancement Details**

#### **3.1 Helius API Response Structure**

Based on Helius DAS API documentation, NFTs are returned with this structure:
```json
{
  "id": "mint_address",
  "content": {
    "metadata": {
      "name": "NFT Name",
      "description": "NFT Description",
      "attributes": [
        {"trait_type": "Background", "value": "Blue"}
      ]
    },
    "links": {
      "image": "https://..."
    },
    "files": [
      {"uri": "https://...", "mime": "image/png"}
    ]
  },
  "grouping": [
    {
      "group_key": "collection",
      "group_value": "collection_address"
    }
  ]
}
```

**Current API Issue:**
- `/api/wallet/get-nfts.php` filters NFTs by collection but returns the FULL NFT object
- We just need to ensure the response includes the full NFT data (which it should)

**Verification Needed:**
- Test the current API response to see if it includes `content.links.image`
- If not, we may need to modify the API to preserve full NFT structure

---

### **PHASE 4: Integration with Existing Code**

#### **4.1 Preserve Existing Functionality**
- ✅ All existing staking features remain unchanged
- ✅ NFT section is ADDITIVE (doesn't modify existing code)
- ✅ Wallet connection is separate from staking wallet (if needed in future)

#### **4.2 Placement**
- NFT section added BEFORE footer
- Section is self-contained and doesn't affect other sections

---

## 🎯 **COLLECTION ADDRESSES**

1. **Narrrfs World: Genesis Genetic**
   - Address: `AtJCkW4as31C7cF4zQbZdvTt488ejUuacgynZpohVmML`
   - Role: 🏆 Holder

2. **Narrrf Genesis VIP Drop**
   - Address: `CUJH8MV68154vS8wTW15vAKxN6KazNpraFZ1FP8CVojg`
   - Role: 🎴 VIP Holder

---

## ✅ **IMPLEMENTATION CHECKLIST**

### **Backend (API):**
- [ ] Verify `/api/wallet/get-nfts.php` returns full NFT objects with images
- [ ] Test API response structure includes `content.links.image`
- [ ] If needed, modify API to ensure image URLs are included
- [ ] Test with both collection addresses

### **Frontend (stake-lab.html):**
- [ ] Add NFT Collection Display section HTML
- [ ] Add wallet connection JavaScript functions
- [ ] Add NFT loading and display functions
- [ ] Add CSS styling for NFT cards
- [ ] Add check for existing wallet connection on page load
- [ ] Test wallet connection flow
- [ ] Test NFT loading and display
- [ ] Test with wallets that have NFTs
- [ ] Test with wallets that have no NFTs
- [ ] Test responsive design (mobile/tablet/desktop)

### **Testing:**
- [ ] Test on localhost with test wallet
- [ ] Test on production with real wallets
- [ ] Verify NFT images load correctly
- [ ] Verify NFT metadata displays correctly
- [ ] Verify error handling (no wallet, no NFTs, API errors)

---

## 📝 **NOTES**

1. **Wallet Connection:**
   - Uses same Phantom wallet connection pattern as profile.html
   - Can be separate from any future staking wallet connection

2. **NFT Display:**
   - Grid layout responsive (2 cols mobile, 3 cols tablet, 4 cols desktop)
   - Shows NFT image, name, collection, role badge, and traits
   - Similar style to realmkins website (grid of NFT cards)

3. **API Considerations:**
   - Current API may already return full NFT data
   - Need to verify image URL field path
   - May need to handle different NFT metadata formats

4. **Future Enhancements:**
   - Click NFT card to view full details modal
   - Filter NFTs by collection
   - Search NFTs by name
   - Sort NFTs by trait rarity

---

## 🚀 **NEXT STEPS**

1. **Verify API Response Structure** - Check if current API returns image URLs
2. **Implement Frontend Section** - Add HTML and JavaScript to stake-lab.html
3. **Test with Real Wallet** - Connect wallet and verify NFT display
4. **Polish Styling** - Match design to page theme (purple/blue gradient)

---

**Status:** 📋 **READY FOR IMPLEMENTATION**  
**Priority:** Medium  
**Estimated Time:** 2-3 hours  

