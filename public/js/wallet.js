/**
 * 🔐 Narrrf's World - Standardized Wallet Implementation
 * Version: 1.2
 * Last Updated: 2025
 */

// Function to fetch and display user scores
async function updateUserScores() {
  try {
    const response = await fetch('/api/user/score-total.php', {
      credentials: 'include'
    });
    
    // Check if response is ok before trying to parse JSON
    if (!response.ok) {
      if (response.status === 401) {
        // User not authenticated - this is normal for guests
        console.log('User not authenticated - showing guest mode');
        updateGuestMode();
        return;
      } else {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
    }
    
    const data = await response.json();
    
    // Check if data is valid before accessing properties
    if (!data || typeof data !== 'object') {
      console.warn('Invalid data received from score-total.php');
      updateGuestMode();
      return;
    }
    
    const spoincBalance = document.getElementById('spoinc-balance');
    const dspoincBalance = document.getElementById('dspoinc-balance');
    const discordName = document.getElementById('wallet-discord-name');
    
    // Update balances with safe property access
    if (spoincBalance && dspoincBalance) {
      const totalSpoinc = data.total_spoinc || 0;
      const totalDspoinc = data.total_dspoinc || 0;
      spoincBalance.textContent = totalSpoinc.toLocaleString();
      dspoincBalance.textContent = totalDspoinc.toLocaleString();
    }

    // Update Discord name
    if (discordName) {
      const discordNameValue = data.discord_name || 'Not logged in';
      discordName.textContent = discordNameValue;
      if (discordNameValue !== 'Guest' && discordNameValue !== 'Not logged in') {
        discordName.classList.add('font-semibold', 'text-purple-800');
      } else {
        discordName.classList.remove('font-semibold', 'text-purple-800');
      }
    }

    // Update stats with safe property access
    const statsAdjustments = document.getElementById('stats-adjustments');
    const statsSources = document.getElementById('stats-sources');
    const statsRoles = document.getElementById('stats-roles');
    const statsMemberSince = document.getElementById('stats-member-since');

    if (statsAdjustments) {
      const adjustmentsCount = data.stats?.adjustments_count || 0;
      statsAdjustments.textContent = adjustmentsCount.toLocaleString();
    }
    if (statsSources) {
      const sourceCount = data.stats?.source_count || 0;
      statsSources.textContent = sourceCount.toLocaleString();
    }
    if (statsRoles) {
      const roleCount = data.stats?.role_count || 0;
      statsRoles.textContent = roleCount.toLocaleString();
    }
    if (statsMemberSince && data.stats?.first_score_date) {
      const date = new Date(data.stats.first_score_date);
      statsMemberSince.textContent = date.toLocaleDateString();
    }
  } catch (err) {
    console.error('Failed to fetch scores:', err);
    // Fallback to guest mode on any error
    updateGuestMode();
  }
}

// Helper function to update UI for guest mode
function updateGuestMode() {
  const spoincBalance = document.getElementById('spoinc-balance');
  const dspoincBalance = document.getElementById('dspoinc-balance');
  const discordName = document.getElementById('wallet-discord-name');
  const statsAdjustments = document.getElementById('stats-adjustments');
  const statsSources = document.getElementById('stats-sources');
  const statsRoles = document.getElementById('stats-roles');
  const statsMemberSince = document.getElementById('stats-member-since');

  // Set default values for guest mode
  if (spoincBalance) spoincBalance.textContent = '0';
  if (dspoincBalance) dspoincBalance.textContent = '0';
  if (discordName) {
    discordName.textContent = 'Not logged in';
    discordName.classList.remove('font-semibold', 'text-purple-800');
  }
  if (statsAdjustments) statsAdjustments.textContent = '0';
  if (statsSources) statsSources.textContent = '0';
  if (statsRoles) statsRoles.textContent = '0';
  if (statsMemberSince) statsMemberSince.textContent = 'N/A';
}

// Helper function to update all connect buttons on the page
function updateAllConnectButtons(walletAddress, isConnected) {
  const connectButtons = document.querySelectorAll('[id$="connect-wallet-btn"]');
  connectButtons.forEach(button => {
    if (isConnected) {
      button.innerText = `🟢 ${walletAddress.slice(0, 4)}...${walletAddress.slice(-4)}`;
      button.disabled = true;
    } else {
      button.innerText = '🔌 Connect';
      button.disabled = false;
    }
  });
}

async function connectWallet() {
  const connectButtons = document.querySelectorAll('[id$="connect-wallet-btn"]');
  const mintButton = document.getElementById('mint-now-btn');
  const vipMessage = document.getElementById('vip-message');
  const avatar = document.getElementById('user-avatar');
  const disconnectButton = document.getElementById('wallet-disconnect');

  if (!('solana' in window)) {
    alert("Phantom Wallet not found! Install it at https://phantom.app");
    return;
  }

  const provider = window.solana;
  if (!provider.isPhantom) {
    alert("Phantom Wallet not detected.");
    return;
  }

  try {
    const response = await provider.connect();
    const walletAddress = response.publicKey.toString();

    localStorage.setItem('walletAddress', walletAddress);

    // Update UI
    updateAllConnectButtons(walletAddress, true);
    if (avatar) avatar.classList.remove('hidden');
    if (disconnectButton) disconnectButton.classList.remove('hidden');

    // Log to Sheety
    try {
      await fetch('https://api.sheety.co/8ec14c6cea31d5316dc44a6d2e45be03/narrrfWalletLog/2025', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          2025: {
            wallet: walletAddress,
            timestamp: new Date().toISOString()
          }
        })
      });
    } catch (err) {
      console.error("Failed to log wallet:", err);
    }

    // Check VIP List and Discord Role if on mint page
    if (window.location.pathname.includes('mint.html') && vipMessage && mintButton) {
      try {
        const vipRes = await fetch('https://api.sheety.co/8ec14c6cea31d5316dc44a6d2e45be03/vipMintList/wallets');
        const vipJson = await vipRes.json();
        const vipList = vipJson.wallets.map(entry => entry.wallet.toLowerCase());

        const isVIP = vipList.includes(walletAddress.toLowerCase());
        
        // Check Discord role access
        let hasDiscordRole = false;
        if (!isVIP) {
          try {
            const discordId = localStorage.getItem('discord_id');
            if (discordId) {
              const roleRes = await fetch('/api/user/roles.php', {
                credentials: 'include'
              });
              
              // Check if response is ok before trying to parse JSON
              if (roleRes.ok) {
                const roleData = await roleRes.json();
                // Check for WL role by name instead of hardcoded ID
                hasDiscordRole = roleData.roles && roleData.roles.some(role => 
                  role.toLowerCase().includes('wl') || 
                  role.toLowerCase().includes('whitelist') ||
                  role === '1332108350518857842' // Keep the original ID as fallback
                );
              } else if (roleRes.status === 401) {
                // User not authenticated - this is normal for guests
                console.log('User not authenticated for roles check');
                hasDiscordRole = false;
              } else {
                console.warn('Roles API returned error:', roleRes.status);
                hasDiscordRole = false;
              }
            }
          } catch (roleErr) {
            console.error("Failed to check Discord role:", roleErr);
            hasDiscordRole = false;
          }
        }

        const mintInfo = document.getElementById('mint-info');
        
        if (isVIP) {
          vipMessage.innerText = "✅ VIP Wallet Verified. You may mint!";
          vipMessage.className = "mt-6 text-green-600 font-semibold text-lg";
          mintButton.classList.remove("hidden");
          if (mintInfo) mintInfo.classList.remove("hidden");
        } else if (hasDiscordRole) {
          vipMessage.innerText = "✅ Discord Role Verified. You may mint! (No wallet in WL)";
          vipMessage.className = "mt-6 text-blue-600 font-semibold text-lg";
          mintButton.classList.remove("hidden");
          if (mintInfo) mintInfo.classList.remove("hidden");
        } else {
          // Check if user has Discord role access before showing denied message
          const discordRoleAccess = await checkDiscordRoleAccess();
          if (!discordRoleAccess) {
            vipMessage.innerText = "❌ Access Denied. This wallet is not on the VIP list and no Discord role access.";
            vipMessage.className = "mt-6 text-red-600 font-semibold text-lg";
            mintButton.classList.add("hidden");
            if (mintInfo) mintInfo.classList.add("hidden");
          }
        }
      } catch (err) {
        console.error("Failed to check VIP status:", err);
        vipMessage.innerText = "⚠️ Failed to verify access status. Please try again.";
        vipMessage.className = "mt-6 text-red-600 font-semibold text-lg";
      }
    }

  } catch (err) {
    console.error("❌ Connection failed:", err);
    if (vipMessage) {
      vipMessage.innerText = "⚠️ Failed to connect. Please try again.";
      vipMessage.className = "mt-6 text-red-600 font-semibold text-lg";
    }
  }
}

function disconnectWallet() {
  localStorage.removeItem('walletAddress');
  const avatar = document.getElementById('user-avatar');
  const disconnectButton = document.getElementById('wallet-disconnect');
  const vipMessage = document.getElementById('vip-message');
  const mintButton = document.getElementById('mint-now-btn');
  const mintInfo = document.getElementById('mint-info');

  updateAllConnectButtons('', false);
  if (avatar) avatar.classList.add('hidden');
  if (disconnectButton) disconnectButton.classList.add('hidden');
  if (vipMessage) vipMessage.innerText = '';
  if (mintButton) mintButton.classList.add('hidden');
  if (mintInfo) mintInfo.classList.add('hidden');
}

// Function to check Discord role access for mint page
async function checkDiscordRoleAccess() {
  const vipMessage = document.getElementById('vip-message');
  const mintButton = document.getElementById('mint-now-btn');
  const mintInfo = document.getElementById('mint-info');
  
  if (!vipMessage || !mintButton) {
    console.log('Mint page elements not found');
    return false;
  }
  
  try {
    const discordId = localStorage.getItem('discord_id');
    console.log('Checking Discord role access for user:', discordId);
    
    if (!discordId) {
      console.log('No Discord ID found in localStorage');
      vipMessage.innerText = "⚠️ Please login with Discord first";
      vipMessage.className = "mt-6 text-yellow-600 font-semibold text-lg";
      return false;
    }
    
    console.log('Fetching roles from API...');
    const roleRes = await fetch('/api/user/roles.php', {
      credentials: 'include'
    });
    
    // Check if response is ok before trying to parse JSON
    if (!roleRes.ok) {
      if (roleRes.status === 401) {
        // User not authenticated - this is normal for guests
        console.log('User not authenticated for roles check');
        vipMessage.innerText = "⚠️ Please login with Discord first";
        vipMessage.className = "mt-6 text-yellow-600 font-semibold text-lg";
        return false;
      } else {
        console.warn('Roles API returned error:', roleRes.status);
        vipMessage.innerText = "⚠️ Failed to check Discord roles. Please try again.";
        vipMessage.className = "mt-6 text-red-600 font-semibold text-lg";
        return false;
      }
    }
    
    const roleData = await roleRes.json();
    console.log('Role data received:', roleData);
    
    // Check if roleData is valid before accessing properties
    if (!roleData || !roleData.roles) {
      console.warn('Invalid role data received');
      vipMessage.innerText = "⚠️ Failed to check Discord roles. Please try again.";
      vipMessage.className = "mt-6 text-red-600 font-semibold text-lg";
      return false;
    }
    
    // Check for WL role by name instead of hardcoded ID
    const hasWLRole = roleData.roles.some(role => 
      role.toLowerCase().includes('wl') || 
      role.toLowerCase().includes('whitelist') ||
      role === '1332108350518857842' // Keep the original ID as fallback
    );
     
     if (hasWLRole) {
       console.log('User has WL role!');
       vipMessage.innerText = "✅ Discord WL Role Verified. You may mint!";
       vipMessage.className = "mt-6 text-blue-600 font-semibold text-lg";
       mintButton.classList.remove("hidden");
       if (mintInfo) mintInfo.classList.remove("hidden");
       return true;
     } else {
       console.log('User does not have WL role. Available roles:', roleData.roles);
       console.log('Looking for WL role (by name or ID: 1332108350518857842)');
       console.log('User roles:', JSON.stringify(roleData.roles, null, 2));
       vipMessage.innerText = "❌ Discord WL Role not found. Please check your Discord roles.";
       vipMessage.className = "mt-6 text-red-600 font-semibold text-lg";
       return false;
     }
  } catch (roleErr) {
    console.error("Failed to check Discord role:", roleErr);
    vipMessage.innerText = "⚠️ Failed to check Discord roles. Please try again.";
    vipMessage.className = "mt-6 text-red-600 font-semibold text-lg";
    return false;
  }
}

// Check for stored wallet on page load
window.addEventListener('DOMContentLoaded', () => {
  const storedWallet = localStorage.getItem('walletAddress');
  if (storedWallet) {
    const avatar = document.getElementById('user-avatar');
    const disconnectButton = document.getElementById('wallet-disconnect');

    updateAllConnectButtons(storedWallet, true);
    if (avatar) avatar.classList.remove('hidden');
    if (disconnectButton) disconnectButton.classList.remove('hidden');
  }
  
  // Check Discord role access on mint page
  if (window.location.pathname.includes('mint.html')) {
    checkDiscordRoleAccess();
  }
  
  // Update scores on page load
  updateUserScores();
  
  // Update scores every 30 seconds
  setInterval(updateUserScores, 30000);
});

// Function to clear cache and reload page (for favicon issues)
function clearCacheAndReload() {
  if ('caches' in window) {
    caches.keys().then(function(names) {
      for (let name of names) {
        caches.delete(name);
      }
    });
  }
  
  // Clear localStorage for Discord and wallet data
  localStorage.removeItem('discord_id');
  localStorage.removeItem('discord_name');
  localStorage.removeItem('walletAddress');
  
  // Force reload without cache
  window.location.reload(true);
}