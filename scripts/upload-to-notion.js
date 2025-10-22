#!/usr/bin/env node

/**
 * Upload Documentation to Notion
 *
 * This script uploads all markdown documentation files to Notion.
 *
 * Setup:
 * 1. Create a Notion integration: https://www.notion.so/my-integrations
 * 2. Share a Notion page with your integration
 * 3. Get the page ID from the URL: https://notion.so/PAGE_ID
 * 4. Set environment variables:
 *    - NOTION_API_KEY: Your integration token
 *    - NOTION_PAGE_ID: Parent page ID where docs will be created
 *
 * Usage:
 *   node scripts/upload-to-notion.js
 *
 *   # Or with env vars inline
 *   NOTION_API_KEY=secret_xxx NOTION_PAGE_ID=xxx node scripts/upload-to-notion.js
 */

import { Client } from '@notionhq/client';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Configuration
const NOTION_API_KEY = process.env.NOTION_API_KEY;
const NOTION_PAGE_ID = process.env.NOTION_PAGE_ID;

if (!NOTION_API_KEY || !NOTION_PAGE_ID) {
  console.error('❌ Missing required environment variables:');
  console.error('   NOTION_API_KEY - Your Notion integration token');
  console.error('   NOTION_PAGE_ID - Parent page ID for documentation');
  console.error('\nGet these from:');
  console.error('   Integration: https://www.notion.so/my-integrations');
  console.error('   Page ID: Copy from your Notion page URL');
  process.exit(1);
}

// Initialize Notion client
const notion = new Client({ auth: NOTION_API_KEY });

// Documentation files to upload
const DOCS = [
  {
    file: 'docs/API_DOCUMENTATION.md',
    title: '📖 API Documentation',
    icon: '📖'
  },
  {
    file: 'docs/DEVELOPER_GUIDE.md',
    title: '📚 Developer Guide',
    icon: '📚'
  },
  {
    file: 'docs/MIGRATION_GUIDE.md',
    title: '🔄 Migration Guide',
    icon: '🔄'
  },
  {
    file: 'docs/FILE_UPLOAD_GUIDE.md',
    title: '📤 File Upload Guide',
    icon: '📤'
  },
  {
    file: 'README.md',
    title: '🏠 README',
    icon: '🏠'
  }
];

/**
 * Convert markdown to Notion blocks
 */
function markdownToNotionBlocks(markdown) {
  const blocks = [];
  const lines = markdown.split('\n');
  let currentCodeBlock = null;
  let currentList = [];
  let listType = null;

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const trimmed = line.trim();

    // Skip empty lines at start
    if (!trimmed && blocks.length === 0) continue;

    // Handle code blocks
    if (trimmed.startsWith('```')) {
      if (currentCodeBlock) {
        // End code block
        blocks.push({
          object: 'block',
          type: 'code',
          code: {
            rich_text: [{
              type: 'text',
              text: { content: currentCodeBlock.content }
            }],
            language: currentCodeBlock.language || 'plain text'
          }
        });
        currentCodeBlock = null;
      } else {
        // Start code block
        let language = trimmed.slice(3).trim() || 'plain text';

        // Map unsupported languages to supported ones
        const languageMap = {
          'env': 'bash',
          'dotenv': 'bash',
          'ini': 'plain text',
          'vue': 'javascript',
          'jsx': 'javascript',
          'tsx': 'typescript',
          'sh': 'bash',
          'zsh': 'bash',
        };

        language = languageMap[language.toLowerCase()] || language;
        currentCodeBlock = { language, content: '' };
      }
      continue;
    }

    // Inside code block
    if (currentCodeBlock) {
      currentCodeBlock.content += line + '\n';
      continue;
    }

    // Headings
    if (trimmed.startsWith('# ')) {
      flushList();
      blocks.push({
        object: 'block',
        type: 'heading_1',
        heading_1: {
          rich_text: [{ type: 'text', text: { content: trimmed.slice(2) } }]
        }
      });
      continue;
    }

    if (trimmed.startsWith('## ')) {
      flushList();
      blocks.push({
        object: 'block',
        type: 'heading_2',
        heading_2: {
          rich_text: [{ type: 'text', text: { content: trimmed.slice(3) } }]
        }
      });
      continue;
    }

    if (trimmed.startsWith('### ')) {
      flushList();
      blocks.push({
        object: 'block',
        type: 'heading_3',
        heading_3: {
          rich_text: [{ type: 'text', text: { content: trimmed.slice(4) } }]
        }
      });
      continue;
    }

    // Bullet lists
    if (trimmed.startsWith('- ') || trimmed.startsWith('* ')) {
      if (listType !== 'bulleted') {
        flushList();
        listType = 'bulleted';
      }
      currentList.push(trimmed.slice(2));
      continue;
    }

    // Numbered lists
    if (/^\d+\.\s/.test(trimmed)) {
      if (listType !== 'numbered') {
        flushList();
        listType = 'numbered';
      }
      currentList.push(trimmed.replace(/^\d+\.\s/, ''));
      continue;
    }

    // Flush list if we hit non-list content
    if (currentList.length > 0 && !trimmed.startsWith('  ')) {
      flushList();
    }

    // Blockquotes
    if (trimmed.startsWith('> ')) {
      blocks.push({
        object: 'block',
        type: 'quote',
        quote: {
          rich_text: [{ type: 'text', text: { content: trimmed.slice(2) } }]
        }
      });
      continue;
    }

    // Dividers
    if (trimmed === '---' || trimmed === '***') {
      blocks.push({
        object: 'block',
        type: 'divider',
        divider: {}
      });
      continue;
    }

    // Regular paragraph
    if (trimmed) {
      blocks.push({
        object: 'block',
        type: 'paragraph',
        paragraph: {
          rich_text: parseRichText(trimmed)
        }
      });
    } else {
      // Empty line
      blocks.push({
        object: 'block',
        type: 'paragraph',
        paragraph: { rich_text: [] }
      });
    }
  }

  flushList();

  function flushList() {
    if (currentList.length > 0) {
      currentList.forEach(item => {
        blocks.push({
          object: 'block',
          type: listType === 'numbered' ? 'numbered_list_item' : 'bulleted_list_item',
          [listType === 'numbered' ? 'numbered_list_item' : 'bulleted_list_item']: {
            rich_text: parseRichText(item)
          }
        });
      });
      currentList = [];
      listType = null;
    }
  }

  return blocks;
}

/**
 * Parse inline markdown formatting
 */
function parseRichText(text) {
  const richText = [];
  let current = '';
  let i = 0;

  while (i < text.length) {
    // Bold **text**
    if (text[i] === '*' && text[i + 1] === '*') {
      if (current) {
        richText.push({ type: 'text', text: { content: current } });
        current = '';
      }
      i += 2;
      let bold = '';
      while (i < text.length && !(text[i] === '*' && text[i + 1] === '*')) {
        bold += text[i++];
      }
      if (bold) {
        richText.push({
          type: 'text',
          text: { content: bold },
          annotations: { bold: true }
        });
      }
      i += 2;
      continue;
    }

    // Code `text`
    if (text[i] === '`') {
      if (current) {
        richText.push({ type: 'text', text: { content: current } });
        current = '';
      }
      i++;
      let code = '';
      while (i < text.length && text[i] !== '`') {
        code += text[i++];
      }
      if (code) {
        richText.push({
          type: 'text',
          text: { content: code },
          annotations: { code: true }
        });
      }
      i++;
      continue;
    }

    current += text[i++];
  }

  if (current) {
    richText.push({ type: 'text', text: { content: current } });
  }

  return richText.length > 0 ? richText : [{ type: 'text', text: { content: text } }];
}

/**
 * Upload a single document to Notion
 */
async function uploadDocument(doc) {
  try {
    console.log(`\n📄 Processing: ${doc.title}`);

    // Read markdown file
    const filePath = path.join(__dirname, '..', doc.file);
    if (!fs.existsSync(filePath)) {
      console.log(`   ⚠️  File not found: ${doc.file}`);
      return null;
    }

    const markdown = fs.readFileSync(filePath, 'utf-8');
    console.log(`   ✓ Read ${markdown.length} characters`);

    // Convert to Notion blocks
    const blocks = markdownToNotionBlocks(markdown);
    console.log(`   ✓ Converted to ${blocks.length} blocks`);

    // Create page in Notion
    const page = await notion.pages.create({
      parent: { page_id: NOTION_PAGE_ID },
      icon: { type: 'emoji', emoji: doc.icon },
      properties: {
        title: {
          title: [
            {
              type: 'text',
              text: { content: doc.title }
            }
          ]
        }
      }
    });

    console.log(`   ✓ Created page: ${page.id}`);

    // Add content blocks (Notion limits to 100 blocks per request)
    const batchSize = 100;
    for (let i = 0; i < blocks.length; i += batchSize) {
      const batch = blocks.slice(i, i + batchSize);
      await notion.blocks.children.append({
        block_id: page.id,
        children: batch
      });
      console.log(`   ✓ Uploaded blocks ${i + 1}-${Math.min(i + batchSize, blocks.length)}/${blocks.length}`);
    }

    console.log(`   ✅ Done! View at: https://notion.so/${page.id.replace(/-/g, '')}`);
    return page;

  } catch (error) {
    console.error(`   ❌ Error: ${error.message}`);
    if (error.code === 'unauthorized') {
      console.error('      Make sure your integration has access to the parent page');
    }
    return null;
  }
}

/**
 * Main execution
 */
async function main() {
  console.log('🚀 Starting documentation upload to Notion...\n');
  console.log(`   API Key: ${NOTION_API_KEY.slice(0, 10)}...`);
  console.log(`   Page ID: ${NOTION_PAGE_ID}`);

  const results = [];

  for (const doc of DOCS) {
    const result = await uploadDocument(doc);
    results.push({ doc, success: !!result });

    // Add delay between uploads to avoid rate limits
    await new Promise(resolve => setTimeout(resolve, 1000));
  }

  // Summary
  console.log('\n' + '='.repeat(60));
  console.log('📊 Upload Summary:');
  console.log('='.repeat(60));

  results.forEach(({ doc, success }) => {
    console.log(`${success ? '✅' : '❌'} ${doc.title}`);
  });

  const successful = results.filter(r => r.success).length;
  console.log(`\n${successful}/${results.length} documents uploaded successfully`);

  if (successful === results.length) {
    console.log('\n🎉 All documentation uploaded to Notion!');
  } else {
    console.log('\n⚠️  Some documents failed to upload. Check errors above.');
  }
}

// Run
main().catch(error => {
  console.error('Fatal error:', error);
  process.exit(1);
});
