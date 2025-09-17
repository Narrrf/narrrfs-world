/**
 * SEASON TESTER ROLE POPUP SYSTEM
 * 
 * This code should be added to profile.html to show the Season Tester
 * role notification popup when users log in during a new season.
 */

// Check for Season Tester role and show popup
async function checkSeasonTesterRole() {
    try {
        const discordId = localStorage.getItem("discord_id");
        
        if (!discordId) {
            return; // User not logged in
        }
        
        const response = await fetch(`${API_BASE_URL}/api/user/check-season-tester-role.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ discord_id: discordId })
        });
        
        const data = await response.json();
        
        if (data.success && data.data.should_show_popup) {
            showSeasonTesterPopup(data.data.contribution_stats);
        }
        
    } catch (error) {
        console.error('Error checking Season Tester role:', error);
    }
}

// Show Season Tester popup
function showSeasonTesterPopup(contributionStats) {
    // Create popup overlay
    const overlay = document.createElement('div');
    overlay.className = 'season-tester-overlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 10000;
        display: flex;
        justify-content: center;
        align-items: center;
        animation: fadeIn 0.5s ease-in-out;
    `;
    
    // Create popup content
    const popup = document.createElement('div');
    popup.className = 'season-tester-popup';
    popup.style.cssText = `
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        padding: 40px;
        max-width: 500px;
        width: 90%;
        text-align: center;
        color: white;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        animation: slideIn 0.5s ease-out;
        position: relative;
    `;
    
    // Create close button
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '✕';
    closeBtn.style.cssText = `
        position: absolute;
        top: 15px;
        right: 20px;
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        opacity: 0.7;
        transition: opacity 0.3s;
    `;
    closeBtn.onmouseover = () => closeBtn.style.opacity = '1';
    closeBtn.onmouseout = () => closeBtn.style.opacity = '0.7';
    closeBtn.onclick = () => closeSeasonTesterPopup();
    
    // Create popup content
    popup.innerHTML = `
        <div style="margin-bottom: 30px;">
            <div style="font-size: 60px; margin-bottom: 20px;">🎉</div>
            <h2 style="margin: 0 0 10px 0; font-size: 28px; font-weight: bold;">Congratulations!</h2>
            <h3 style="margin: 0 0 20px 0; font-size: 20px; opacity: 0.9;">You're a Season Tester!</h3>
        </div>
        
        <div style="background: rgba(255, 255, 255, 0.1); border-radius: 15px; padding: 20px; margin-bottom: 30px;">
            <p style="margin: 0 0 15px 0; font-size: 16px; opacity: 0.9;">
                Thank you for being an active member of Narrrf's World!
            </p>
            <p style="margin: 0 0 20px 0; font-size: 16px; opacity: 0.9;">
                You've been granted the <strong>Season Tester</strong> role for your contributions:
            </p>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div style="background: rgba(255, 255, 255, 0.1); padding: 15px; border-radius: 10px;">
                    <div style="font-size: 24px; margin-bottom: 5px;">🎮</div>
                    <div style="font-size: 18px; font-weight: bold;">${contributionStats.tetris_snake_space_games}</div>
                    <div style="font-size: 12px; opacity: 0.8;">Games Played</div>
                </div>
                <div style="background: rgba(255, 255, 255, 0.1); padding: 15px; border-radius: 10px;">
                    <div style="font-size: 24px; margin-bottom: 5px;">🧀</div>
                    <div style="font-size: 18px; font-weight: bold;">${contributionStats.cheese_hunt_clicks}</div>
                    <div style="font-size: 12px; opacity: 0.8;">Cheese Clicks</div>
                </div>
                <div style="background: rgba(255, 255, 255, 0.1); padding: 15px; border-radius: 10px;">
                    <div style="font-size: 24px; margin-bottom: 5px;">🏁</div>
                    <div style="font-size: 18px; font-weight: bold;">${contributionStats.discord_races}</div>
                    <div style="font-size: 12px; opacity: 0.8;">Races Joined</div>
                </div>
                <div style="background: rgba(255, 255, 255, 0.2); padding: 15px; border-radius: 10px;">
                    <div style="font-size: 24px; margin-bottom: 5px;">🏆</div>
                    <div style="font-size: 18px; font-weight: bold;">${contributionStats.total_contributions}</div>
                    <div style="font-size: 12px; opacity: 0.8;">Total Contributions</div>
                </div>
            </div>
            
            <div style="background: rgba(255, 255, 255, 0.1); padding: 15px; border-radius: 10px;">
                <div style="font-size: 16px; font-weight: bold; margin-bottom: 5px;">Welcome to the Season Tester community!</div>
                <div style="font-size: 14px; opacity: 0.8;">You now have special access and recognition in our Discord server.</div>
            </div>
        </div>
        
        <button onclick="closeSeasonTesterPopup()" style="
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            border: none;
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(0, 0, 0, 0.3)'" 
           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 5px 15px rgba(0, 0, 0, 0.2)'">
            Awesome! Let's Go! 🚀
        </button>
    `;
    
    // Add close button
    popup.appendChild(closeBtn);
    
    // Add CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { 
                transform: translateY(-50px) scale(0.9);
                opacity: 0;
            }
            to { 
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }
        
        .season-tester-popup button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
    `;
    document.head.appendChild(style);
    
    // Add to page
    overlay.appendChild(popup);
    document.body.appendChild(overlay);
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
    
    // Auto-close after 30 seconds
    setTimeout(() => {
        if (document.body.contains(overlay)) {
            closeSeasonTesterPopup();
        }
    }, 30000);
}

// Close Season Tester popup
function closeSeasonTesterPopup() {
    const overlay = document.querySelector('.season-tester-overlay');
    if (overlay) {
        overlay.style.animation = 'fadeOut 0.5s ease-in-out';
        setTimeout(() => {
            if (document.body.contains(overlay)) {
                document.body.removeChild(overlay);
                document.body.style.overflow = '';
            }
        }, 500);
    }
}

// Add fadeOut animation
const fadeOutStyle = document.createElement('style');
fadeOutStyle.textContent = `
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
`;
document.head.appendChild(fadeOutStyle);

// Call the function when profile page loads
document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit for the page to fully load
    setTimeout(checkSeasonTesterRole, 2000);
});
