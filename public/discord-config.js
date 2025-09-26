// Discord Configuration - Token of Render System
// This file manages Discord invite links dynamically across all pages
// Environment Variable Support: DISCORD_INVITE_CODE
// Fallback: 'dSJDkDhPKZ' (current Discord invite code)

let DISCORD_CONFIG = {
    // 🔧 ENVIRONMENT VARIABLE SUPPORT
    // Will be loaded from server-side PHP endpoint
    inviteCode: 'dSJDkDhPKZ', // Fallback Discord invite code
    
    // Base Discord URL
    baseUrl: 'https://discord.gg/',
    
    // Full Discord URL
    get fullUrl() {
        return this.baseUrl + this.inviteCode;
    },
    
    // Version tracking
    version: '12.0',
    lastUpdated: '2025-01-28',
    
    // Enhanced Discord link detection patterns
    discordPatterns: [
        /discord\.gg\/[a-zA-Z0-9]+/g,
        /https:\/\/discord\.gg\/[a-zA-Z0-9]+/g,
        /window\.location\.href=['"]https:\/\/discord\.gg\/[a-zA-Z0-9]+['"]/g,
        /window\.open\(['"]https:\/\/discord\.gg\/[a-zA-Z0-9]+['"]/g
    ],
    
    // Load server configuration
    async loadServerConfig() {
        try {
            console.log('🔄 Loading Discord config from server...');
            const response = await fetch('/api/config/get-discord-config.php');
            if (response.ok) {
                const config = await response.json();
                // Update the local DISCORD_CONFIG object with the server values
                this.inviteCode = config.inviteCode;
                console.log('✅ Discord config loaded from server:', this.inviteCode);
                
                // Log debug information if available
                if (config.debug) {
                    console.log('🔍 Debug info:', config.debug);
                }
                
                return true;
            } else {
                console.warn('⚠️ Failed to load Discord config from server, using fallback: dSJDkDhPKZ');
                return false;
            }
        } catch (error) {
            console.warn('⚠️ Error loading Discord config:', error);
            return false;
        }
    },
    
    // Update all Discord links on page load
    updateAllLinks() {
        console.log(`🔄 Discord Token of Render System: Updating all Discord links to ${this.inviteCode}`);
        
        // Update href attributes
        const links = document.querySelectorAll('a[href*="discord.gg"]');
        links.forEach(link => {
            const currentHref = link.getAttribute('href');
            if (currentHref && currentHref.includes('discord.gg/')) {
                const newHref = currentHref.replace(
                    /discord\.gg\/[a-zA-Z0-9]+/g, 
                    `discord.gg/${this.inviteCode}`
                );
                if (newHref !== currentHref) {
                    link.setAttribute('href', newHref);
                    console.log(`✅ Updated Discord link: ${currentHref} → ${newHref}`);
                }
            }
        });
        
        // Update onclick handlers
        const elementsWithOnclick = document.querySelectorAll('[onclick*="discord.gg"]');
        elementsWithOnclick.forEach(element => {
            const onclick = element.getAttribute('onclick');
            if (onclick && onclick.includes('discord.gg/')) {
                const updatedOnclick = onclick.replace(
                    /discord\.gg\/[a-zA-Z0-9]+/g,
                    `discord.gg/${this.inviteCode}`
                );
                if (updatedOnclick !== onclick) {
                    element.setAttribute('onclick', updatedOnclick);
                    console.log(`✅ Updated Discord onclick: ${onclick} → ${updatedOnclick}`);
                }
            }
        });
        
        // Update script content
        const scripts = document.querySelectorAll('script');
        scripts.forEach(script => {
            if (script.textContent && script.textContent.includes('discord.gg/')) {
                let updatedContent = script.textContent;
                this.discordPatterns.forEach(pattern => {
                    updatedContent = updatedContent.replace(pattern, (match) => {
                        return match.replace(/discord\.gg\/[a-zA-Z0-9]+/, `discord.gg/${this.inviteCode}`);
                    });
                });
                if (updatedContent !== script.textContent) {
                    script.textContent = updatedContent;
                    console.log(`✅ Updated Discord script content`);
                }
            }
        });
        
        // Update inline text content (for cases where Discord links are in text)
        const walker = document.createTreeWalker(
            document.body,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );
        
        const textNodes = [];
        let node;
        while (node = walker.nextNode()) {
            if (node.textContent.includes('discord.gg/')) {
                textNodes.push(node);
            }
        }
        
        textNodes.forEach(textNode => {
            const updatedText = textNode.textContent.replace(
                /discord\.gg\/[a-zA-Z0-9]+/g,
                `discord.gg/${this.inviteCode}`
            );
            if (updatedText !== textNode.textContent) {
                textNode.textContent = updatedText;
                console.log(`✅ Updated Discord text: ${textNode.textContent.substring(0, 50)}...`);
            }
        });
        
        console.log(`🎯 Discord Token of Render System: Update complete!`);
    },
    
    // Get current Discord URL
    getCurrentUrl() {
        return this.fullUrl;
    },
    
    // Update invite code and refresh all links
    updateInviteCode(newCode) {
        this.inviteCode = newCode;
        this.updateAllLinks();
        console.log(`✅ Discord invite code updated to: ${newCode}`);
    },
    
    // Get environment variable (for debugging)
    getEnvironmentInfo() {
        return {
            currentInviteCode: this.inviteCode,
            environmentVariable: (typeof process !== 'undefined' && process.env && process.env.DISCORD_INVITE_CODE) 
                ? process.env.DISCORD_INVITE_CODE 
                : 'Not set (using fallback)',
            fullUrl: this.fullUrl,
            version: this.version
        };
    }
};

// Auto-update all Discord links when page loads
document.addEventListener('DOMContentLoaded', async function() {
    console.log('🔄 DOM Content Loaded - Loading Discord config...');
    const configLoaded = await DISCORD_CONFIG.loadServerConfig();
    if (configLoaded) {
        console.log('✅ Config loaded successfully, updating links...');
        DISCORD_CONFIG.updateAllLinks();
    } else {
        console.log('⚠️ Using fallback config, updating links...');
        DISCORD_CONFIG.updateAllLinks();
    }
    console.log(`🎯 Discord Token of Render System v${DISCORD_CONFIG.version} loaded`);
    console.log(`🔗 Current Discord URL: ${DISCORD_CONFIG.getCurrentUrl()}`);
    console.log(`🌍 Environment Info:`, DISCORD_CONFIG.getEnvironmentInfo());
});

// Also update on window load for dynamic content
window.addEventListener('load', async function() {
    console.log('🔄 Window Loaded - Checking Discord config...');
    if (!DISCORD_CONFIG.inviteCode || DISCORD_CONFIG.inviteCode === 'CvstbUQ5yX') {
        console.log('🔄 Config not loaded or using fallback, loading from server...');
        await DISCORD_CONFIG.loadServerConfig();
    }
    DISCORD_CONFIG.updateAllLinks();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DISCORD_CONFIG;
} 
