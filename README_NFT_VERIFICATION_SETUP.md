# NFT Verification System Setup Guide

## 🎯 **Overview**

This guide covers the complete setup for the NFT verification system that allows users to verify ownership of:
- **Narrrfs World: Genesis Genetic** NFTs (🏆 Holder role)
- **Narrrf Genesis VIP Drop** NFTs (🎴 VIP Holder role)

## 🔧 **Prerequisites**

### 1. **Helius API Key** (Required)
- Visit: https://dev.helius.xyz/
- Sign up for a free account
- Create a new API key
- Copy the API key

### 2. **Database Tables** (Auto-created)
The system will automatically create required tables if they don't exist.

## 🚀 **Setup Steps**

### **Step 1: Configure Helius API Key**

#### **Option A: Local Development**
```bash
# Add to your .env file
HELIUS_API_KEY=your_actual_api_key_here
```

#### **Option B: Render Production**
1. Go to Render Dashboard → Your Service → Environment
2. Add Environment Variable:
   - **Key:** `HELIUS_API_KEY`
   - **Value:** `your_actual_api_key_here`
3. Redeploy the service

### **Step 2: Verify Database Tables**

Run the debug script to ensure tables exist:
```bash
curl https://narrrfs.world/api/debug/check-nft-tables.php
```

This will automatically create any missing tables:
- `tbl_holder_verifications` - Tracks verification attempts
- `tbl_nft_ownership` - Stores NFT ownership records
- `tbl_role_grants` - Audit trail for role assignments

### **Step 3: Test the System**

1. **Connect Wallet**: Users must connect their Phantom wallet
2. **Verify NFTs**: Click "Verify NFTs" button
3. **Sign Message**: Approve the signature request in wallet
4. **Get Role**: Automatic Discord role assignment

## 🎮 **User Flow**

### **For Users:**
1. **Login with Discord** - Required for role assignment
2. **Connect Phantom Wallet** - Click "Connect Wallet" button
3. **Verify NFTs** - Click "Verify NFTs" button
4. **Approve Signature** - Sign the verification message
5. **Get Role** - Automatic Discord role assignment

### **For Admins:**
- Monitor verifications: `/api/admin/get-holder-verifications.php`
- View statistics: `/api/admin/get-holder-verification-stats.php`
- Search NFT ownership: `/api/admin/search-nft-ownership.php`

## 🔍 **Collection Details**

### **Narrrfs World: Genesis Genetic**
- **Address:** `AtJCkW4as31C7cF4zQbZdvTt488ejUuacgynZpohVmML`
- **Role:** 🏆 Holder
- **Role ID:** `1402668301414563971`

### **Narrrf Genesis VIP Drop**
- **Address:** `CUJH8MV68154vS8wTW15vAKxN6KazNpraFZ1FP8CVojg`
- **Role:** 🎴 VIP Holder
- **Role ID:** `1332016526848692345`

## 🛠 **Troubleshooting**

### **Common Issues:**

1. **"Helius API key not configured"**
   - Solution: Configure `HELIUS_API_KEY` environment variable
   - Check: `/api/debug/check-nft-tables.php`

2. **"No NFTs found"**
   - Verify: User owns NFTs from the correct collections
   - Check: Collection addresses are correct
   - Debug: Check Helius API response

3. **"User not found in database"**
   - Solution: User must have played games to be in the system
   - Check: User exists in `tbl_user_scores`

4. **"Invalid signature"**
   - Solution: User must approve signature request in wallet
   - Check: Phantom wallet is connected and working

### **Debug Tools:**

- **Check Tables:** `/api/debug/check-nft-tables.php`
- **Test API:** `/api/wallet/get-nfts.php?wallet=WALLET_ADDRESS&collection=COLLECTION_ADDRESS`
- **View Logs:** Check server error logs for detailed messages

## 🔒 **Security Features**

- **Cryptographic Signature**: Required for wallet ownership proof
- **Rate Limiting**: Built-in protection against abuse
- **Audit Trail**: All verifications and role grants logged
- **Secure Storage**: Sensitive data encrypted and protected

## 📊 **Monitoring**

### **Key Metrics:**
- Total verifications
- Successful role grants
- Collection-specific statistics
- Daily verification counts

### **Admin Interface:**
- Real-time verification dashboard
- User search and management
- Role assignment tracking
- System health monitoring

## 🎯 **Success Criteria**

✅ **System Working When:**
- Users can connect Phantom wallet
- NFT verification returns correct results
- Discord roles are assigned automatically
- All verifications are logged
- Admin interface shows statistics

## 📞 **Support**

For issues:
1. Check this documentation
2. Run debug scripts
3. Review server logs
4. Contact system administrator

---

*Last updated: January 2025*
