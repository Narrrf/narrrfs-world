# 🌍 Environment Configuration Guide

## 🔑 Required Environment Variables

### Helius API Key (Required for NFT Verification)

The NFT verification system requires a Helius API key to function properly.

#### 🎯 **What is Helius?**
- Helius is a Solana RPC provider that offers enhanced APIs for NFT data
- Used for efficient NFT collection verification and ownership checks
- Provides `searchAssets` API for direct collection queries

#### 📋 **Setup Instructions**

##### **Option 1: Local Development (.env file)**

1. **Get your Helius API key:**
   - Visit: https://dev.helius.xyz/
   - Sign up for a free account
   - Create a new API key
   - Copy the API key

2. **Configure local environment:**
   ```bash
   # Copy the example file
   cp env.example .env
   
   # Edit .env file and add your API key
   HELIUS_API_KEY=your_actual_api_key_here
   ```

##### **Option 2: Render Production (Environment Variables)**

1. **Access Render Dashboard:**
   - Go to your Render dashboard
   - Select your `narrrfs-world` service
   - Click on "Environment" tab

2. **Add Environment Variable:**
   - **Key:** `HELIUS_API_KEY`
   - **Value:** `your_actual_api_key_here`
   - **Environment:** `Production`

3. **Redeploy:**
   - Save the environment variable
   - Trigger a new deployment

#### 🔒 **Security Best Practices**

- ✅ **Never commit API keys** to version control
- ✅ **Use environment variables** for production
- ✅ **Rotate keys regularly** for security
- ✅ **Monitor API usage** to avoid rate limits

#### 🚨 **Error Handling**

If the Helius API key is not configured, the system will:
- Return a clear error message
- Log the issue for debugging
- Gracefully handle the failure

#### 📊 **API Usage**

The Helius API is used for:
- **NFT Collection Verification:** Check if user owns NFTs from specific collections
- **Efficient Queries:** Use `searchAssets` for direct collection searches
- **Role Assignment:** Automatically grant Discord roles based on NFT ownership

#### 🔧 **Troubleshooting**

**Common Issues:**
1. **"Helius API key not configured"**
   - Check if `HELIUS_API_KEY` is set in environment
   - Verify the key is valid and active

2. **"Rate limit exceeded"**
   - Helius has rate limits on free tier
   - Consider upgrading for higher limits

3. **"Invalid API key"**
   - Verify the key format and validity
   - Check if the key is active in Helius dashboard

#### 📞 **Support**

For issues with:
- **Helius API:** Contact Helius support
- **Environment setup:** Check this documentation
- **System integration:** Review the code comments

---

## 🎯 **Quick Setup Checklist**

- [ ] Get Helius API key from https://dev.helius.xyz/
- [ ] Add `HELIUS_API_KEY` to Render environment variables
- [ ] Test NFT verification functionality
- [ ] Monitor API usage and limits
- [ ] Document any custom configurations

---

*Last updated: August 2025* 