import { test, expect } from '@playwright/test';

test('editor loads in under 3 seconds', async ({ page }) => {
  const start = Date.now();
  await page.goto('/wp-admin/post.php?post=1&action=edit');
  await page.click('[data-nexusbuilder-edit]');
  await page.waitForSelector('.nexus-canvas');
  const duration = Date.now() - start;
  expect(duration).toBeLessThan(3000);
});

test('built page scores 90+ on Lighthouse', async ({ page }) => {
  // Requires @playwright/lighthouse integration
  const result = await playAudit(page, { url: 'https://example.com/test-page' });
  expect(result.lhr.categories.performance.score * 100).toBeGreaterThan(90);
});
