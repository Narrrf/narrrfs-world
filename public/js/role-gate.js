// 🛡️ Role-Based Access Control System
// Restricts access to admin/moderator pages based on Discord roles
// Version: 12.0

const ROLE_GATE_CONFIG = {
    adminRoles: [
        'Admin', 'Administrator', 'Moderator', 'Mod',
        'Server Admin', 'Server Moderator', 'Owner', 'Founder'
    ],
    protectedPages: [
        'admin.html', 'moderator.html', 'admin-panel.html',
        'mod-panel.html', 'admin-interface.html'
    ],
    redirectUrl: '/index.html',
    
    // Check if current page is protected
    isProtectedPage() {
        const currentPage = window.location.pathname.split('/').pop() || 'index.html';
        return this.protectedPages.includes(currentPage);
    },
    
    // Get Discord ID from session or localStorage (same as profile.html)
    getDiscordId() {
        // Try from global variable first (set by profile.html)
        if (window.sessionDiscordId) return window.sessionDiscordId;
        
        // Fallback to localStorage
        return localStorage.getItem('discord_id') || null;
    },
    
    // Check if user has admin role using the same API as profile.html
    async checkAdminRole() {
        const discordId = this.getDiscordId();
        
        if (!discordId) {
            console.log('Role Gate: No Discord ID found');
            return false;
        }
        
        try {
            // Use the same API endpoint as profile.html
            const response = await fetch('https://narrrfs.world/api/user/profile.php', {
                credentials: 'include',
                headers: { 'Accept': 'application/json' }
            });
            
            if (!response.ok) {
                console.log('Role Gate: Profile API not accessible');
                return false;
            }
            
            const userData = await response.json();
            
            if (!userData.roles || !Array.isArray(userData.roles)) {
                console.log('Role Gate: No roles found in user data');
                return false;
            }
            
            // Check if user has any admin role
            const hasAdminRole = userData.roles.some(role => 
                this.adminRoles.some(adminRole => 
                    role.toLowerCase().includes(adminRole.toLowerCase())
                )
            );
            
            console.log('Role Gate: User roles:', userData.roles);
            console.log('Role Gate: Has admin role:', hasAdminRole);
            
            return hasAdminRole;
            
        } catch (error) {
            console.error('Role Gate: Error checking admin role:', error);
            return false;
        }
    },
    
    // Block access and show custom message
    blockAccess() {
        // Clear the page content
        document.body.innerHTML = `
            <div class="min-h-screen bg-gray-900 flex items-center justify-center p-4">
                <div class="bg-gray-800 border-4 border-red-500 rounded-2xl p-8 max-w-md w-full text-center shadow-2xl">
                    <div class="text-6xl mb-4">🚫</div>
                    <h1 class="text-2xl font-bold text-red-400 mb-4">Access Denied</h1>
                    <p class="text-gray-300 mb-6">
                        This page requires Admin or Moderator privileges.
                    </p>
                    <div class="space-y-3">
                        <a href="https://discord.gg/rHc4Jg5Q" 
                           class="block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl transition">
                            🔗 Join Discord & Get Roles
                        </a>
                        <a href="/index.html" 
                           class="block bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded-xl transition">
                            🏠 Go Home
                        </a>
                    </div>
                    <p class="text-xs text-gray-500 mt-4">
                        Need help? Contact an admin in Discord.
                    </p>
                </div>
            </div>
        `;
        
        // Prevent back navigation
        history.pushState(null, null, this.redirectUrl);
        window.addEventListener('popstate', () => {
            window.location.href = this.redirectUrl;
        });
    },
    
    // Initialize role-based protection
    async init() {
        // Only protect specified pages
        if (!this.isProtectedPage()) {
            console.log('Role Gate: Page not protected, allowing access');
            return;
        }
        
        console.log('Role Gate: Checking access for protected page');
        
        // Show loading message
        const loadingDiv = document.createElement('div');
        loadingDiv.id = 'role-gate-loading';
        loadingDiv.innerHTML = `
            <div class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
                <div class="bg-gray-800 border-2 border-yellow-400 rounded-xl p-6 text-center">
                    <div class="text-4xl mb-4">🔐</div>
                    <p class="text-yellow-400 font-bold">Checking Access...</p>
                    <p class="text-gray-400 text-sm mt-2">Verifying Discord roles</p>
                </div>
            </div>
        `;
        document.body.appendChild(loadingDiv);
        
        try {
            const hasAccess = await this.checkAdminRole();
            
            // Remove loading message
            const loadingElement = document.getElementById('role-gate-loading');
            if (loadingElement) {
                loadingElement.remove();
            }
            
            if (!hasAccess) {
                console.log('Role Gate: Access denied, blocking page');
                this.blockAccess();
            } else {
                console.log('Role Gate: Access granted');
            }
            
        } catch (error) {
            console.error('Role Gate: Error during initialization:', error);
            
            // Remove loading message
            const loadingElement = document.getElementById('role-gate-loading');
            if (loadingElement) {
                loadingElement.remove();
            }
            
            // On error, block access for security
            this.blockAccess();
        }
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    ROLE_GATE_CONFIG.init();
}); 