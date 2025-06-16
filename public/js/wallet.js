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
    const data = await response.json();
    
    const spoincBalance = document.getElementById('spoinc-balance');
    const dspoincBalance = document.getElementById('dspoinc-balance');
    const discordName = document.getElementById('wallet-discord-name');
    
    if (spoincBalance && dspoincBalance) {
      spoincBalance.textContent = data.total_spoinc.toLocaleString();
      dspoincBalance.textContent = data.total_dspoinc.toLocaleString();
    }

    if (discordName) {
      discordName.textContent = data.discord_name || 'Not logged in';
      if (data.discord_name !== 'Guest') {
        discordName.classList.add('font-semibold', 'text-purple-800');
      } else {
        discordName.classList.remove('font-semibold', 'text-purple-800');
      }
    }
  } catch (err) {
    console.error('Failed to fetch scores:', err);
  }
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

    // Check VIP List if on mint page
    if (window.location.pathname.includes('mint.html') && vipMessage && mintButton) {
      try {
        const vipRes = await fetch('https://api.sheety.co/8ec14c6cea31d5316dc44a6d2e45be03/vipMintList/wallets');
        const vipJson = await vipRes.json();
        const vipList = vipJson.wallets.map(entry => entry.wallet.toLowerCase());

        const isVIP = vipList.includes(walletAddress.toLowerCase());

        if (isVIP) {
          vipMessage.innerText = "✅ VIP Wallet Verified. You may mint!";
          vipMessage.className = "mt-6 text-green-600 font-semibold text-lg";
          mintButton.classList.remove("hidden");
        } else {
          vipMessage.innerText = "❌ This wallet is not on the VIP list.";
          vipMessage.className = "mt-6 text-red-600 font-semibold text-lg";
          mintButton.classList.add("hidden");
        }
      } catch (err) {
        console.error("Failed to check VIP status:", err);
        vipMessage.innerText = "⚠️ Failed to verify VIP status. Please try again.";
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

  updateAllConnectButtons('', false);
  if (avatar) avatar.classList.add('hidden');
  if (disconnectButton) disconnectButton.classList.add('hidden');
  if (vipMessage) vipMessage.innerText = '';
  if (mintButton) mintButton.classList.add('hidden');
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
  
  // Update scores on page load
  updateUserScores();
  
  // Update scores every 30 seconds
  setInterval(updateUserScores, 30000);
}); 