# Upload Documentation to Notion

This guide explains how to upload all project documentation to Notion.

## Prerequisites

1. **Notion Account** - You need a Notion workspace
2. **Node.js** - Already installed (v18+)
3. **Dependencies** - Already installed (`@notionhq/client`)

## Setup Steps

### 1. Create Notion Integration

1. Go to https://www.notion.so/my-integrations
2. Click **"+ New integration"**
3. Name it: `Inki Documentation Sync`
4. Select your workspace
5. Click **"Submit"**
6. Copy the **"Internal Integration Token"** (starts with `secret_`)

### 2. Create Parent Page in Notion

1. Open Notion and create a new page
2. Name it: `Inki API Documentation`
3. **Share the page** with your integration:
   - Click "Share" button
   - Click "Invite"
   - Search for "Inki Documentation Sync"
   - Click "Invite"

### 3. Get Page ID

Copy the page ID from the URL:

```
https://www.notion.so/My-Page-Title-abc123def456...
                                    └─────────────┘
                                      This is the page ID
```

The page ID is the part after the last dash, or the full UUID.

## Upload Documentation

### Method 1: Using Environment Variables

```bash
# Set environment variables
export NOTION_API_KEY="secret_your_integration_token"
export NOTION_PAGE_ID="your_page_id_here"

# Run upload script
node scripts/upload-to-notion.js
```

### Method 2: Inline Environment Variables

```bash
NOTION_API_KEY="secret_xxx" NOTION_PAGE_ID="abc123" node scripts/upload-to-notion.js
```

### Method 3: Using .env File

Create `.env.notion` in project root:

```env
NOTION_API_KEY=secret_your_integration_token
NOTION_PAGE_ID=your_page_id_here
```

Then run:

```bash
export $(cat .env.notion | xargs) && node scripts/upload-to-notion.js
```

## What Gets Uploaded

The script uploads these documents:

1. 📚 **Developer Guide** (`docs/DEVELOPER_GUIDE.md`)
2. 🔄 **Migration Guide** (`docs/MIGRATION_GUIDE.md`)
3. 📤 **File Upload Guide** (`docs/FILE_UPLOAD_GUIDE.md`)
4. 🗺️ **Location Search API** (`docs/LOCATION_SEARCH_API.md`)
5. 🏠 **README** (`README.md`)

## Expected Output

```
🚀 Starting documentation upload to Notion...

   API Key: secret_abc...
   Page ID: abc123...

📄 Processing: 📚 Developer Guide
   ✓ Read 25000 characters
   ✓ Converted to 250 blocks
   ✓ Created page: abc123...
   ✓ Uploaded blocks 1-100/250
   ✓ Uploaded blocks 101-200/250
   ✓ Uploaded blocks 201-250/250
   ✅ Done! View at: https://notion.so/abc123...

📄 Processing: 🔄 Migration Guide
   ...

============================================================
📊 Upload Summary:
============================================================
✅ 📚 Developer Guide
✅ 🔄 Migration Guide
✅ 📤 File Upload Guide
✅ 🗺️ Location Search API
✅ 🏠 README

5/5 documents uploaded successfully

🎉 All documentation uploaded to Notion!
```

## Features

### Markdown Conversion

The script converts markdown to Notion blocks:

- ✅ Headings (H1, H2, H3)
- ✅ Paragraphs
- ✅ Code blocks with syntax highlighting
- ✅ Bullet lists
- ✅ Numbered lists
- ✅ Blockquotes
- ✅ Dividers (`---`)
- ✅ Inline formatting:
  - **Bold** (`**text**`)
  - `Code` (`` `text` ``)
- ✅ Icons (emojis for each page)

### Rate Limiting

The script includes:
- 1-second delay between uploads
- Batch processing (100 blocks per API call)
- Error handling and retry logic

## Troubleshooting

### Error: `unauthorized`

**Solution:** Make sure you shared the parent page with your integration:
1. Open the parent page in Notion
2. Click "Share" → "Invite"
3. Select your integration

### Error: `object_not_found`

**Solution:** Check your page ID is correct:
- Copy from URL bar
- Remove hyphens if needed
- Ensure it's the full UUID

### Error: `validation_error`

**Solution:** This usually means:
- Markdown has formatting issues
- Try simplifying complex markdown
- Check for special characters

### Rate Limiting

If you hit rate limits:
- Wait 1 minute and try again
- Increase delay in script (line 343): `setTimeout(resolve, 2000)`

## Updating Documentation

To update existing docs in Notion:

### Option 1: Delete and Re-upload

1. Delete old pages in Notion
2. Run upload script again

### Option 2: Sync Script (Advanced)

For continuous syncing, you can modify the script to:
- Check if pages exist by title
- Update existing pages instead of creating new ones
- Track page IDs in a JSON file

## Security

**⚠️ Important:**
- Never commit `.env.notion` to git
- Keep your integration token secret
- Integration tokens can access all pages they're invited to

Add to `.gitignore`:
```
.env.notion
```

## Notion Workspace Organization

Recommended page structure:

```
📁 Inki API Documentation (Parent)
  ├── 📚 Developer Guide
  ├── 🔄 Migration Guide
  ├── 📤 File Upload Guide
  ├── 🗺️ Location Search API
  └── 🏠 README
```

You can organize these pages further:
- Add to databases
- Create sub-pages
- Add tags/properties
- Link between pages

## Alternative Methods

### Manual Import

Notion also supports manual markdown import:
1. Create new page
2. Click "..." menu
3. Select "Import"
4. Choose "Markdown" format
5. Select `.md` files

### Third-Party Tools

Other tools you can use:
- **md2notion** (Python): `pip install md2notion`
- **notion-cli** (Python): `pip install notion-cli`
- **Notion API** (REST): Direct API calls

## Resources

- [Notion API Documentation](https://developers.notion.com/)
- [Notion JavaScript SDK](https://github.com/makenotion/notion-sdk-js)
- [Creating Integrations Guide](https://developers.notion.com/docs/create-a-notion-integration)

## Support

If you encounter issues:
1. Check Notion's status: https://status.notion.so/
2. Review API limits: https://developers.notion.com/reference/request-limits
3. Check integration permissions in Notion settings

---

**Script Location:** `scripts/upload-to-notion.js`
**Last Updated:** January 2025