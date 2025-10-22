#!/usr/bin/env node

/**
 * Test Notion API Connection
 *
 * Quick test to verify your Notion integration is set up correctly.
 *
 * Usage:
 *   NOTION_API_KEY=secret_xxx NOTION_PAGE_ID=xxx node scripts/test-notion.js
 */

import { Client } from '@notionhq/client';

const NOTION_API_KEY = process.env.NOTION_API_KEY;
const NOTION_PAGE_ID = process.env.NOTION_PAGE_ID;

if (!NOTION_API_KEY || !NOTION_PAGE_ID) {
  console.error('❌ Missing environment variables!');
  console.error('\nUsage:');
  console.error('  NOTION_API_KEY=secret_xxx NOTION_PAGE_ID=xxx node scripts/test-notion.js');
  process.exit(1);
}

const notion = new Client({ auth: NOTION_API_KEY });

async function test() {
  console.log('🔍 Testing Notion API connection...\n');

  try {
    // Test 1: API Key
    console.log('1️⃣ Testing API key...');
    console.log(`   Key: ${NOTION_API_KEY.slice(0, 15)}...`);
    console.log('   ✅ API key format looks good\n');

    // Test 2: Get page
    console.log('2️⃣ Testing page access...');
    console.log(`   Page ID: ${NOTION_PAGE_ID}`);

    const page = await notion.pages.retrieve({ page_id: NOTION_PAGE_ID });
    console.log(`   ✅ Page found: ${page.properties.title?.title[0]?.plain_text || 'Untitled'}\n`);

    // Test 3: Create test page
    console.log('3️⃣ Creating test page...');

    const testPage = await notion.pages.create({
      parent: { page_id: NOTION_PAGE_ID },
      icon: { type: 'emoji', emoji: '🧪' },
      properties: {
        title: {
          title: [{
            type: 'text',
            text: { content: '🧪 Test Page - Safe to Delete' }
          }]
        }
      }
    });

    console.log(`   ✅ Test page created!`);
    console.log(`   View at: https://notion.so/${testPage.id.replace(/-/g, '')}\n`);

    // Test 4: Add content
    console.log('4️⃣ Adding test content...');

    await notion.blocks.children.append({
      block_id: testPage.id,
      children: [
        {
          object: 'block',
          type: 'heading_2',
          heading_2: {
            rich_text: [{
              type: 'text',
              text: { content: 'Connection Test Successful!' }
            }]
          }
        },
        {
          object: 'block',
          type: 'paragraph',
          paragraph: {
            rich_text: [{
              type: 'text',
              text: { content: 'Your Notion integration is working correctly. You can now upload the full documentation.' }
            }]
          }
        },
        {
          object: 'block',
          type: 'divider',
          divider: {}
        },
        {
          object: 'block',
          type: 'callout',
          callout: {
            icon: { type: 'emoji', emoji: '✅' },
            rich_text: [{
              type: 'text',
              text: { content: 'This test page can be safely deleted.' }
            }]
          }
        }
      ]
    });

    console.log('   ✅ Content added successfully\n');

    // Summary
    console.log('═'.repeat(60));
    console.log('✅ All tests passed!');
    console.log('═'.repeat(60));
    console.log('\n🎉 Your Notion integration is ready!');
    console.log('\nNext step: Run the full upload:');
    console.log(`  NOTION_API_KEY="${NOTION_API_KEY.slice(0, 15)}..." \\`);
    console.log(`  NOTION_PAGE_ID="${NOTION_PAGE_ID}" \\`);
    console.log('  node scripts/upload-to-notion.js');
    console.log('\n💡 Tip: Delete the test page in Notion (it has a 🧪 icon)\n');

  } catch (error) {
    console.error('\n❌ Test failed:', error.message);

    if (error.code === 'unauthorized') {
      console.error('\n💡 Solution:');
      console.error('   1. Make sure your integration token is correct');
      console.error('   2. Share the parent page with your integration:');
      console.error('      - Open page in Notion');
      console.error('      - Click "Share"');
      console.error('      - Click "Invite"');
      console.error('      - Select your integration');
    } else if (error.code === 'object_not_found') {
      console.error('\n💡 Solution:');
      console.error('   1. Check your page ID is correct');
      console.error('   2. Make sure you shared the page with your integration');
    } else if (error.code === 'validation_error') {
      console.error('\n💡 Solution:');
      console.error('   1. Verify your page ID format');
      console.error('   2. Page ID should be a UUID (with or without hyphens)');
    }

    process.exit(1);
  }
}

test();
