<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>🧠 Your Narrrf Profile</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <meta name="theme-color" content="#fcd34d">
  <link rel="icon" href="img/cheese-egg.png" type="image/png">
</head>

<body class="bg-[#2f3136] text-white font-sans flex flex-col items-center py-10 px-4">
<header class="w-full bg-black py-4 px-6 text-yellow-400 text-center shadow-md">
  <h1 class="text-xl font-bold tracking-wide">🧠 Narrrf's Lab – Phase 2 Portal</h1>
  <p class="text-xs text-gray-400 mt-1">Profile Synced via Discord OAuth · Powered by Cheese Architect 5.0</p>
</header>

<div id="goldenCheeseGate" class="hidden mt-4 p-4 bg-yellow-100 text-yellow-800 font-bold rounded-xl shadow-lg animate-pulse">
  <img src="img/big-cheese.svg" class="w-8 h-8 inline mr-2" alt="Big Cheese" />
  Welcome, VIP! You found the Golden Cheese 🧀
</div>

<div id="noCheeseJoke" class="hidden mt-4 text-sm italic text-gray-500">
  😅 You’re logged in, but... no VIP pass? That’s nacho cheese!
</div>

<!-- Role Scroll Message (auto-fades) -->
<div id="role-scroll-message" class="hidden mt-4 text-yellow-200 italic text-sm p-2 bg-gray-700 rounded shadow transition-opacity duration-500 ease-in-out"></div>

<main class="flex-grow flex items-center justify-center px-4 py-12">
  <div class="bg-gray-800 rounded-2xl shadow-2xl p-8 max-w-lg w-full text-center border-4 border-yellow-400">

    <p id="welcome-msg" class="text-yellow-300 text-sm italic mb-2">Welcome back, <span id="welcome-name">...</span></p>
    <img id="avatar" class="w-24 h-24 mx-auto rounded-full border-4 border-yellow-500 shadow mb-4 animate-bounce" src="" alt="User Avatar" />
    <h1 id="username" class="text-2xl font-bold text-yellow-300">Loading...</h1>
    <p id="email" class="text-sm text-gray-400">...</p>
    <p id="guilds" class="mt-2 text-sm text-yellow-100 italic">Guilds loading...</p>

    <div class="mt-6 bg-yellow-100 text-gray-900 rounded-lg p-4 shadow-inner">
      <h2 class="font-semibold text-lg mb-2">🎒 Wallet & Traits</h2>
      <p id="wallet-address">🔗 Linked wallet: <em class="text-sm">coming soon</em></p>
      <p>🧬 Traits: <span id="traits">pending sync</span></p>
      <p id="user-roles" class="mt-3 text-sm text-yellow-900 italic">Roles: loading...</p>
    </div>

    <div id="role-legend" class="mt-6 hidden">
      <h3 class="text-yellow-300 text-lg font-semibold mb-2">🧠 Role Legend</h3>
      <ul id="role-list" class="text-sm text-yellow-100 space-y-1 list-disc list-inside text-left"></ul>
    </div>

    <div class="mt-4 text-xs text-gray-300 italic">OAuth session live · Verified via Discord</div>
    <div class="mt-6 text-yellow-400 text-sm">🧠 Profile powered by Cheese Architect 5.0 · Phase 2 Bridge Online</div>
  </div>
</main>

<footer class="text-center text-xs text-yellow-600 py-6 mt-16">
  🧠 Powered by Cheese Architect 5.0 — HTML Brain of Narrrf’s World<br>
  🧀 Brain Sync 5.0 Confirmed · Profile UX Finalized · Guided by Coreforge + SQL Junior
</footer>

<script>
  async function loadProfile() {
    try {
      const res = await fetch('/api/user.php', { credentials: 'include' });
      if (!res.ok) {
        document.getElementById('username').innerText = 'Not Logged In';
        return;
      }
      const user = await res.json();
      const fullUsername = `${user.username}#${user.discriminator}`;
      document.getElementById('avatar').src = user.avatarUrl || '';
      document.getElementById('username').innerText = fullUsername;
      document.getElementById('welcome-name').innerText = fullUsername;
      document.getElementById('email').innerText = user.email || 'No email provided';
      document.getElementById('guilds').innerText = `🎯 Guilds Connected: ${user.guilds?.length || 0}`;
      document.getElementById('wallet-address').innerText = `🔗 Linked wallet: ${localStorage.getItem('walletAddress') || 'not connected'}`;
    } catch (e) {
      console.error('Profile fetch failed', e);
      document.getElementById('username').innerText = 'Login Required';
    }
  }

  document.addEventListener("DOMContentLoaded", async () => {
    loadProfile();
    try {
      const res = await fetch('/api/user/roles.php');
      const data = await res.json();
      const roles = data.roles || [];

      if (roles.includes("VIP Holder") || roles.includes("VIP_pass")) {
        document.getElementById('goldenCheeseGate').classList.remove('hidden');
      } else {
        document.getElementById('noCheeseJoke').classList.remove('hidden');
      }

      const roleMessages = {
        "Champion": "🏆 Welcome, Champion! Let the tournaments begin.",
        "Rabbit Friends": "🐇 Hop in, friend of the fluffy.",
        "Crypto Corn Friends": "🌽 Blockchain-ready brain detected!",
        "PokerOG": "🃏 Poker legend, your table is set.",
        "VIP Holder": "🧀 You are blessed by the Golden Cheese.",
        "Rumble": "🎮 The arena stirs... Welcome, Rumble master!",
        "Engage": "✨ Builder spotted. Let's engage the lab.",
        "Community Member": "🌍 Narrrf’s world salutes you!"
      };

      const scrollMessage = roles
        .filter(role => roleMessages[role])
        .map(role => roleMessages[role])
        .join(" ");

      if (scrollMessage) {
        const scroll = document.getElementById("role-scroll-message");
        scroll.innerText = scrollMessage;
        scroll.classList.remove("hidden");
        setTimeout(() => scroll.classList.add("opacity-0"), 5000);
      }

      const icons = {
        "Champion": "🏆", "Rabbit Friends": "🐇", "Crypto Corn Friends": "🌽", "PokerOG": "🃏",
        "VIP Holder": "🧀", "Rumble": "🎮", "Engage": "✨", "Community Member": "🌍"
      };

      const list = document.getElementById("role-list");
      roles.forEach(role => {
        const li = document.createElement("li");
        li.innerText = `${icons[role] || "🔸"} ${role}`;
        list.appendChild(li);
      });

      if (roles.length > 0) {
        document.getElementById("role-legend").classList.remove("hidden");
        document.getElementById("user-roles").innerText = `🧠 Roles: ${roles.join(', ')}`;
      }

    } catch (err) {
      console.error("Role check failed:", err);
    }
  });
</script>

</body>
</html>
